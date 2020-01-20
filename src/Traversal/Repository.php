<?php

declare(strict_types=1);

namespace SakuraDibi\Traversal;

use Sakura\Exceptions;
use Sakura\Traversal\IRepository;
use Sakura\Traversal\Table;
use Sakura\Traversal\INode;
use Sakura\Traversal\NodeList;
use Dibi\Connection;
use Dibi\Result;

final class Repository implements IRepository
{

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

    public function getBranch(INode $node): NodeList
    {
        $result = $this->connection->query(
            "SELECT * FROM %n WHERE %n >= ? AND %n <= ? ORDER BY %n ASC",
            $this->table->getName(),
            $this->table->getLeftColumn(),
            $node->getLeft(),
            $this->table->getRightColumn(),
            $node->getRight(),
            $this->table->getLeftColumn());
                
        return $this->getNodeList($result);
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

    public function getNodeByLeft(int $left): ?INode
    {
        $row = $this->connection->fetch(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getLeftColumn(),
            $left);

        if (\is_null($row)) {
            return \null;
        } else {
            return $this->factory->createNode($row, $this->table);
        }
    }

    public function getNodesByParent(int $parent): NodeList {
    }

    public function getLevel(INode $node): ?int
    {
        $row = $this->connection->fetch(
            "SELECT COUNT(*) AS level FROM %n WHERE %n <= ? AND %n >= ?",
            $this->table->getName(),
            $this->table->getLeftColumn(),
            $node->getLeft(),
            $this->table->getRightColumn(),
            $node->getRight());

        if (\is_null($row)) {
            return \null;
        } else {
            return $row['level'];
        }
    }

    public function getNumberOfChilds(int $id): int
    {
        $row = $this->connection->fetch(
            "SELECT COUNT(*) AS n FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $id);
        
        if (\is_null($row)) {
            return \null;
        } else {
            return $row['n'];
        }    
    }

    public function getPath(INode $node, bool $isAscending): NodeList
    {
        $sql = "SELECT * FROM %n WHERE %n <= ? AND %n >= ? ORDER BY %n ";
        
        if ($isAscending) {
            $sql .= "ASC";
        } else {
            $sql .= "DESC";
        }
        
        $result = $this->connection->query(
            $sql,
            $this->table->getName(),
            $this->table->getLeftColumn(),
            $node->getLeft(),
            $this->table->getRightColumn(),
            $node->getRight(),
            $this->table->getLeftColumn());
        
        return $this->getNodeList($result);
    }

    public function updateById(int $whereId, ?int $setParent): int
    {
        $this->connection->query(
            "UPDATE %n SET %n = ? WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $setParent,
            $this->table->getIdColumn(),
            $whereId);
        return $this->connection->getAffectedRows();    
    }

    public function updateByParent(int $whereParent, ?int $setParent): int
    {
        $this->connection->query(
            "UPDATE %n SET %n = ? WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $setParent,
            $this->table->getParentColumn(),
            $whereParent);
        return $this->connection->getAffectedRows();    
    }

    public function updateByLeft(int $from, ?int $to, int $movement): int
    {
        return $this->updateByLeftOrRight($from, $to, $movement, $this->table->getLeftColumn());
    }

    public function updateByRight(int $from, ?int $to, int $movement): int
    {
        return $this->updateByLeftOrRight($from, $to, $movement, $this->table->getRightColumn());
    }

    private function updateByLeftOrRight(int $from, ?int $to, int $movement, $column): int
    {
        $args = [
            "",
            $this->table->getName(),
            $column,
            $column,
            $movement,
            $column,
            $from,
        ];

        if ($movement < 0) {
            $sign = "";
            $orderBy = "ASC";
        } else {
            $sign = "+";
            $orderBy = "DESC";
        }

        if (\is_null($to))
        {
            $toSql = "";
        } else {
            $toSql = " AND %n <= ?";
            $args[] = $column;
            $args[] = $to;
        }

        $args[0] = "UPDATE %n SET %n = %n $sign ? WHERE %n >= ? $toSql ORDER BY %n $orderBy";
        $args[] = $column;
        $this->connection->query($args);
        return $this->connection->getAffectedRows();
    }
  
    private function getNodeList(Result $result): NodeList
    {
        $list = [];
        foreach ($result as $row)
        {
            $list[] = $this->factory->createNode($row, $this->table);
        }
        
        $nodeList = new NodeList($list);
        return $nodeList;
    }

}
