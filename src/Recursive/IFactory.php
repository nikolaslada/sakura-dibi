<?php

declare(strict_types=1);

namespace SakuraDibi\Recursive;

use Sakura\Recursive\Table;
use Sakura\Recursive\INode;

interface IFactory {

    public function createNode(array $row, Table $table): INode;

}
