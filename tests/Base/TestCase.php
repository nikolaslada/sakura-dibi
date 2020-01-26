<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Tests\Base;

class TestCase extends \PHPUnit\Framework\TestCase {

    /** @var \Nette\DI\Container */
    protected $container;


    public function setUp(): void
    {
        parent::setUp();
        global $container;
        $this->container = $container;
    }

}
