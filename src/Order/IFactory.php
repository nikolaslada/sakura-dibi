<?php

declare(strict_types=1);

namespace SakuraDibi\Order;

use Sakura\Order\Table;
use Sakura\Order\INode;
use Dibi\Row;

interface IFactory {

    public function createNode(Row $row, Table $table): INode;

}
