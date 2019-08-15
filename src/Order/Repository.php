<?php

declare(strict_types=1);

namespace SakuraDibi\Order;

use Sakura\Exceptions;
use Sakura\Order\IRepository;
use Sakura\Order\Table;
use Sakura\Order\INode;
use Sakura\Order\NodeList;
use Dibi\Connection;
use Dibi\Result;
use Dibi\Row;

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

    public function rollbackTransaction(): void
    {
        $this->connection->rollback();
    }

    public function delete(int $id): int
    {
        $this->connection->query(
            "DELETE FROM %n WHERE %n = ?",
            $this->table->getName(),
            $this->table->getIdColumn(),
            $id);
        return $this->connection->getAffectedRows();
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

    /**
     * @throws Exceptions\NoExpectedNodeException
     */
    public function getEndOrder(int $startOrder, int $startDepth): int
    {
        $row = $this->connection->fetch(
            "SELECT
                (SELECT MIN(`x`.%n) FROM %n AS x WHERE `x`.%n > ? AND `x`.%n <= ?) AS min,
                (SELECT MAX(`y`.%n) FROM %n AS y WHERE `y`.%n >= ? AND `y`.%n >= ?) AS max",
            $orderCol = $this->table->getOrderColumn(),
            $name = $this->table->getName(),
            $orderCol,
            $startOrder,
            $depth = $this->table->getDepthColumn(),
            $startDepth,
            $orderCol,
            $name,
            $orderCol,
            $startOrder,
            $depth,
            $startDepth);

        if (\is_null($row)) {
            throw new Exceptions\NoExpectedNodeException;
        } elseif (\is_null($row['min']) && \is_null($row['max'])) {
            throw new Exceptions\NoExpectedNodeException;
        } elseif (\is_null($row['min'])) {
          return $row['max'];
        } else {
          return $row['min'] -1;
        }
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

    public function updateByOrder(int $fromOrder, ?int $toOrder, int $orderMovement, int $depthMovement): int
    {
        $args = [
            '',
            $this->table->getName(),
            $orderCol = $this->table->getOrderColumn(),
            $orderCol,
            $orderMovement,
        ];

        if ($orderMovement < 0) {
            $orderSign = "";
            $orderBy = "ASC";
        } else {
            $orderSign = "+";
            $orderBy = "DESC";
        }

        if ($depthMovement === 0) {
            $depthSql = "";
        } else {
            if ($depthMovement < 0) {
                $depthSql = ", %n = %n ?";
            } else {
                $depthSql = ", %n = %n + ?";
            }

            $args[] = $depthCol = $this->table->getDepthColumn();
            $args[] = $depthCol;
            $args[] = $depthMovement;
        }

        $args[] = $orderCol;
        $args[] = $fromOrder;

        if (\is_null($toOrder))
        {
            $toOrderSql = "";
        } else {
            $toOrderSql = " AND %n <= ?";
            $args[] = $orderCol;
            $args[] = $toOrder;
        }

        $sql = "UPDATE %n SET %n = %n $orderSign ? $depthSql WHERE %n >= ? $toOrderSql ORDER BY %n $orderBy";
        $args[0] = $sql;
        $args[] = $orderCol;
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
