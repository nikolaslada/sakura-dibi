<?php

declare(strict_types=1);

namespace Tests\Order;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryTest extends \Tests\Base\TestCase
{

    /** @var \Sakura\Order\Tree */
    private $tree;


    public function setUp(): void
    {
        parent::setUp();
        $this->tree = $this->container->getByType('\Sakura\Order\Tree');
    }

    public function testInit(): void
    {
        /* @var $connection \Dibi\Connection */
        $connection = $this->container->getByType('\Dibi\Connection');
        $connection->loadFile(__DIR__ . '/sql/table.sql');
        $count = $connection->loadFile(__DIR__ . '/sql/data.sql');
        $this->assertSame(13, $count);
    }

    /** @dataProvider dataOrderValue */
    /** @depends testInit */
    public function testOrder($id, $expectedOrder): void
    {
        $node = $this->tree->getNode($id);
        $this->assertSame($expectedOrder, $node->getOrder());
    }

    public function dataOrderValue(): array
    {
        return [
            [1, 1,],
            [5, 13,],
            [7, 5,],
            [11, 9,],
            [13, 12],
        ];
    }

    /** @dataProvider dataDepthValue */
    /** @depends testOrder */
    public function testDepth($id, $expectedDepth): void
    {
        $depth = $this->tree->getDepth($id);
        $this->assertSame($expectedDepth, $depth);
    }

    public function dataDepthValue(): array
    {
        return [
            [1, 1,],
            [2, 2],
            [6, 3,],
            [11, 4,],
            [13, 4],
        ];
    }

    /** @dataProvider dataParentValue */
    /** @depends testDepth */
    public function testParent($id, $expectedParent): void
    {
        $parent = $this->tree->getParent($id);
        $this->assertSame($expectedParent, $parent);
    }

    public function dataParentValue(): array
    {
        return [
            [1, \null,],
            [2, 1],
            [6, 3,],
            [9, 4,],
            [13, 9],
        ];
    }

    /** @dataProvider dataNumberChildsValue */
    /** @depends testParent */
    public function testNumberChilds($id, $expectedCount): void
    {
        $count = $this->tree->getNumberOfChilds($id);
        $this->assertSame($expectedCount, $count);
    }

    public function dataNumberChildsValue(): array
    {
        return [
            [1, 4,],
            [2, 0],
            [6, 1,],
            [9, 2,],
            [13, 0],
        ];
    }

    /** @depends testNumberChilds */
    public function testRoot(): void
    {
        $root = $this->tree->getRoot();
        $this->assertSame(1, $root->getId());

        $nodeLíst = $this->tree->getPath($root, false);
        $this->assertSame(0, $nodeLíst->count());
        
        $nodeList = $this->tree->getBranch($root, \null);
        $this->assertSame(13, $nodeList->count());

        $this->expectException(\Sakura\Exceptions\InvalidArgumentException::class);
        $this->tree->removeNode($root);
    }

    /** @depends testRoot */
    public function testPath(): void
    {
        $node = $this->tree->getNode(10);
        $path = $this->tree->getPath($node);
        $this->assertSame(4, $path->count());

        $this->assertSame(1, $path->current()->getId());

        $path->next();
        $this->assertSame(4, $path->current()->getId());

        $path->next();
        $this->assertSame(8, $path->current()->getId());
    }

    /** @depends testPath */
    public function testCreateNodeAfter(): void
    {
        $previousNode = $this->tree->getNode(5);
        $data = [
            'name' => 'BranchE',
        ];
        $this->tree->createNodeAfter($data, $previousNode);

        $root = $this->tree->getRoot();
        $this->expectException(\Sakura\Exceptions\InvalidArgumentException::class);
        $this->tree->createNodeAfter($data, $root);
    }

    /** @depends testCreateNodeAfter */
    public function testCreateNodeAsFirstChild(): void
    {
        $previousNode = $this->tree->getRoot();
        $data = [
            'name' => 'Branch0',
        ];
        $this->tree->createNodeAfter($data, $previousNode);
    }

    /** @depends testCreateNodeAsFirstChild */
    public function testRemoveNode(): void
    {
        $node = $this->tree->getNode(3);
        $this->tree->removeNode($node);
    }

    /** @depends testRemoveNode */
    public function testMoveBranchAsFirstChild(): void
    {
        $nodeA = $this->tree->getNode(12);
        $goalA = $this->tree->getNode(4);
        $this->tree->moveBranchAsFirstChild($nodeA, $goalA);

        $nodeB = $this->tree->getNode(4);
        $goalB = $this->tree->getNode(2);
        $this->tree->moveBranchAsFirstChild($nodeB, $goalB);
    }

    /** @depends testMoveBranchAsFirstChild */
    public function testMoveBranchAfter(): void
    {
        $nodeA = $this->tree->getNode(8);
        $goalA = $this->tree->getNode(11);
        $this->tree->moveBranchAfter($nodeA, $goalA);

        $nodeB = $this->tree->getNode(5);
        $goalB = $this->tree->getNode(8);
        $this->tree->moveBranchAfter($nodeB, $goalB);
    }

    /** @depends testMoveBranchAfter */
    public function testBranch(): void
    {
        $nodeList = $this->tree->getBranch(1);
        $this->assertSame(14, $nodeList->count());

        $this->assertSame(1, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(4, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(8, $nodeList->current()->getId());
    }

}
