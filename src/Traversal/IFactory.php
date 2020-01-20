<?php

declare(strict_types=1);

namespace SakuraDibi\Traversal;

use Sakura\Traversal\Table;
use Sakura\Traversal\INode;
use Dibi\Row;

interface IFactory {

    public function createNode(Row $row, Table $table): INode;

}
