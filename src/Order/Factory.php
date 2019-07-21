<?php

declare(strict_types=1);

namespace SakuraDibi\Order;

use Sakura\Order\Table;
use Sakura\Order\Node;

final class Factory implements IFactory
{

    public function createNode(\stdClass $row, Table $table): Node
    {
        $id = $table->getIdColumn();
        $depth = $table->getDepthColumn();
        $order = $table->getOrderColumn();
        $parent = $table->getParentColumn();
        
        if (
            property_exists($row, $id)
            && property_exists($row, $depth)
            && property_exists($row, $order)
            && property_exists($row, $parent))
        {
            $node = new Node($row->{$id}, $row->{$depth}, $row->{$order}, $row->{$parent});
            return $node;
        } else {
            throw new \Sakura\Exceptions\RuntimeException;
        }
    }

}
