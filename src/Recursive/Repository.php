<?php

declare(strict_types=1);

namespace SakuraDibi\Recursive;

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
        $result = $this->connection->query(
            "INSERT INTO %n %v",
            $this->table->getName(),
            $data);
        $result->getRowCount();
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

    public function getChildsByParent(int $id): array {
        return $this->connection->fetchAll(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $id);
    }

    public function getNodeById(int $id): INode
    {
        $row = $this->connection->fetch(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
        $node = $this->factory->createNode($row, $this->table);
        return $node;
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
    
    private function getNode(?int $id): INode
    {
        $row = $this->connection->fetch(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
        $node = $this->factory->createNode($row, $this->table);
        return $node;
    }

    public function getParentNode(int $id): INode
    {
        return $this->getNode($id);
    }

    public function getRoot(): INode
    {
        return $this->getNode(\null);
    }

    public function updateNode(int $setParent, int ...$whereId): void
    {
        $this->connection->query("UPDATE %n SET %n = ? WHERE %n IN (?)",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $setParent,
            $this->table->getIdColumn(),
            $whereId);
    }

}
