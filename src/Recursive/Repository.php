<?php

declare(strict_types=1);

namespace SakuraDibi\Recursive;

use Sakura\Exceptions;
use Sakura\Recursive\IRepository;
use Sakura\Recursive\Table;
use Sakura\Recursive\INode;
use Dibi\Connection;

final class Repository implements IRepository  {

    /** @var IFactory */
    private $factory;

    /** @var Table */
    private $table;

    /** @var Connection */
    private $connection;


    public function __construct(
        IFactory $factory,
        Table $table,
        Connection $connection)
    {
        $this->factory = $factory;
        $this->table = $table;
        $this->connection = $connection;
    }

    public function addData(array $data): int
    {
        $this->connection->query(
            "INSERT INTO %n %v",
            $this->table->getName(),
            $data);
        return $this->connection->getInsertId();
    }

    public function beginTransaction(): void
    {
        $this->connection->begin();
    }

    public function commitTransaction(): void
    {
        $this->connection->commit();
    }

    public function delete(int $id): void
    {
        $this->connection->query(
            "DELETE FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
    }

    public function getNodesByParent(int $parent): array
    {
        $list = [];
        $result = $this->connection->query(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $parent);

        if ($result->getRowCount() === 0) {
            return $list;
        } else {
            foreach ($result as $row) {
                $list[] = $this->factory->createNode($row, $this->table);
            }

            return $list;
        }
    }

    public function getIdsByParent(int $parent): array
    {
        $result = $this->connection->query(
            "SELECT %n FROM %n WHERE %n = ?",
            $this->table->getIdColumn(),
            $this->table->getName(),
            $this->table->getParentColumn(),
            $parent);
        $list = [];
        
        if ($result->getRowCount() === 0) {
            return $list;
        } else {
            foreach ($result as $row) {
                $list[] = $row->{$this->table->getIdColumn()};
            }

            return $list;
        }
    }

    public function getNodeById(int $id): ?INode
    {
        $row = $this->connection->fetch(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);

        if (\is_null($row)) {
            return \null;
        } else {
            return $this->factory->createNode($row, $this->table);
        }
    }

    public function getNumberOfChilds(int $nodeId): int
    {
        $row = $this->connection->fetch(
            "SELECT COUNT(*) AS `childs` FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $nodeId);
        return (int) $row['childs'];
    }

    public function getParentById(int $id): int
    {
        $p = $this->table->getParentColumn();
        $row = $this->connection->fetch(
            "SELECT %n FROM %n WHERE %n = ?",
            $p,
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
        return (int) $row[$p];
    }

    /**
     * @throws Exceptions\NoRootException
     */
    public function getRoot(): INode
    {
        $list = $this->connection->fetchAll(
            "SELECT * FROM %n WHERE %n IS NULL",
            $this->table->getName(),
            $this->table->getParentColumn());
        $count = \count($list);

        if ($count === 0) {
            throw new Exceptions\NoRootException('No root found in ' . $this->table->getName() . ' table.');
        } elseif ($count > 1) {
            throw new Exceptions\NoRootException('There is broken root or whole tree in ' . $this->table->getName() . ' table.');
        } else {
            return $this->factory->createNode($list[0], $this->table);
        }
    }

    public function updateParentByIdList(array $whereIdList, ?int $setParent): int
    {
        $this->connection->query(
            "UPDATE %n SET %n = ? WHERE %n IN (?)",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $setParent,
            $this->table->getIdColumn(),
            $whereIdList);
        return $this->connection->getAffectedRows();
    }

}
