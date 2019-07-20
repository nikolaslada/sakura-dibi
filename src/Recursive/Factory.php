<?php

declare(strict_types=1);

namespace SakuraDibi\Recursive;

use Sakura\Recursive\Table;
use Sakura\Recursive\Node;

final class Factory implements IFactory
{

    public function createNode(\stdClass $row, Table $table): Node
    {
        $id = $table->getIdColumn();
        $parent = $table->getParentColumn();
        
        if (
            property_exists($row, $id)
            && property_exists($row, $parent))
        {
            $node = new Node($row->{$id}, $row->{$parent});
            return $node;
        } else {
            throw new \Sakura\Exceptions\RuntimeException;
        }
    }

}
