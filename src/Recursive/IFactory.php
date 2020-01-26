<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace SakuraDibi\Recursive;

use Sakura\Recursive\Table;
use Sakura\Recursive\INode;
use Dibi\Row;

interface IFactory {

    public function createNode(Row $row, Table $table): INode;

}
