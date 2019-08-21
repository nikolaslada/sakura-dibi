<?php

declare(strict_types=1);

namespace SakuraDibi\Recursive;

use Sakura\Recursive\Table;
use Sakura\Recursive\INode;
use Sakura\Recursive\Node;
use Dibi\Row;

final class Factory implements IFactory
{

    public function createNode(Row $row, Table $table): INode
    {
        $id = $table->getIdColumn();
        $parent = $table->getParentColumn();
        
        if (
            property_exists($row, $id)
            && property_exists($row, $parent))
        {
            return new Node($row->{$id}, $row->{$parent});
        } else {
            throw new \Sakura\Exceptions\RuntimeException;
        }
    }

}
