<?php

declare(strict_types=1);

namespace SakuraDibi\Traversal;

use Sakura\Traversal\Table;
use Sakura\Traversal\INode;
use Sakura\Traversal\Node;
use Dibi\Row;

final class Factory implements IFactory
{

    public function createNode(Row $row, Table $table): INode
    {
        $id = $table->getIdColumn();
        $left = $table->getLeftColumn();
        $right = $table->getRightColumn();
        $parent = $table->getParentColumn();
        
        if (
            property_exists($row, $id)
            && property_exists($row, $left)
            && property_exists($row, $right)
            && property_exists($row, $parent))
        {
            $node = new Node($row->{$id}, $row->{$left}, $row->{$right}, $row->{$parent});
            return $node;
        } else {
            throw new \Sakura\Exceptions\RuntimeException;
        }
    }

}
