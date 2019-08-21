<?php

declare(strict_types=1);

namespace SakuraDibi\Recursive;

use Sakura\Recursive\Table;
use Sakura\Recursive\INode;
use Dibi\Row;

interface IFactory {

    public function createNode(Row $row, Table $table): INode;

}
