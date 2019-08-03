<?php

declare(strict_types=1);

namespace SakuraDibi\Order;

use Sakura\Order\IRepository;
use Sakura\Order\Table;
use Sakura\Order\INode;
use Dibi\Connection;
use Dibi\Result;
use Dibi\Row;
use Sakura\Order\NodeList;

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

    public function delete(int $id)
    {
        $this->connection->query(
            "DELETE FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
    }

    public function getBranch(int $fromOrder, int $toOrder, ?int $maxDepth): NodeList
    {
        $sql = "SELECT * FROM %n WHERE %n >= ? AND %n <= ?";
        $sqlOrderBy = " ORDER BY %n ASC";
        $orderCol = $this->table->getOrderColumn();
        
        if (\is_null($maxDepth))
        {
            $sql = $sql . $sqlOrderBy;
            $result = $this->connection->query(
                $sql,
                $this->table->getName(),
                $orderCol,
                $fromOrder,
                $orderCol,
                $toOrder,
                $orderCol);
        } else {
            $sql = $sql . " AND %n <= ?" . $sqlOrderBy;
            $result = $this->connection->query(
                $sql,
                $this->table->getName(),
                $orderCol,
                $fromOrder,
                $orderCol,
                $toOrder,
                $this->table->getDepthColumn(),
                $maxDepth,
                $orderCol);
        }
        
        return $this->getNodeList($result);
    }

    public function getDepthById(int $id): int
    {
        return $this->connection->fetchSingle(
            "SELECT %n FROM %n WHERE %n = ?",
            $this->table->getDepthColumn(),
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
    }

    public function getEndNode(int $startOrder, int $minDepth): int
    {
        $orderCol = $this->table->getOrderColumn();
        return $this->connection->fetchSingle(
            "SELECT MAX(%n) FROM %n WHERE %n >= ? AND %n >= ?",
            $orderCol,
            $this->table->getName(),
            $orderCol,
            $startOrder,
            $this->table->getDepthColumn(),
            $minDepth);
    }

    public function getNodeById(int $id): ?INode
    {
        $row = $this->connection->fetch(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
        return $this->getNode($row);
    }

    public function getNodeByOrder(int $order): ?INode
    {
        $row = $this->connection->fetch(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getOrderColumn(),
            $order);
        return $this->getNode($row);
    }

    private function getNode(?Row $row): ?INode
    {
        if (\is_null($row)) {
            return \null;
        } else {
            return $this->factory->createNode($row, $this->table);
        }
    }

    public function getNodesByParent(int $parent): NodeList
    {
        $result = $this->connection->query(
            "SELECT * FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $parent);
        return $this->getNodeList($result);
    }

    public function getNumberOfChilds(int $nodeId): int
    {
        return $this->connection->fetchSingle(
            "SELECT COUNT(*) FROM %n WHERE %n =",
            $this->table->getName(),
            $this->table->getParentColumn(),
            $nodeId);
    }

    public function updateByIdList(array $whereIdList, ?int $setParent): int
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

    public function updateByOrder(int $fromOrder, ?int $toOrder, int $orderMovement, int $depthMovement): int
    {
        if ($orderMovement < 0)
        {
            $orderSign = "-";
        } else {
            $orderSign = "+";
        }
        
        if ($depthMovement < 0)
        {
            $depthSign = "-";
        } else {
            $depthSign = "+";
        }
        
        $orderCol = $this->table->getOrderColumn();
        $depthCol = $this->table->getDepthColumn();
        $this->connection->query(
            "UPDATE %n SET %n = %n $orderSign ?, %n = %n $depthSign ? WHERE %n >= ? AND %n <= ?",
            $this->table->getName(),
            $orderCol,
            $orderCol,
            $orderMovement,
            $depthCol,
            $depthCol,
            $depthMovement,
            $orderCol,
            $fromOrder,
            $orderCol,
            $toOrder);
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
