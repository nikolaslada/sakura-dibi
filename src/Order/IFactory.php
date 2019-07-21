<?php

declare(strict_types=1);

namespace SakuraDibi\Order;

use Sakura\Order\Table;
use Sakura\Order\INode;

interface IFactory {

    public function createNode(array $row, Table $table): INode;

}
