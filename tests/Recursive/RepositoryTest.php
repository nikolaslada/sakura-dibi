<?php

declare(strict_types=1);

namespace Tests\Recursive;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryTest extends \Tests\Base\TestCase
{

    /** @var \Sakura\Recursive\Tree */
    private $tree;


    public function setUp(): void
    {
        parent::setUp();
        $this->tree = $this->container->getByType('\Sakura\Recursive\Tree');
    }

    public function testInit(): void
    {
        /* @var $connection \Dibi\Connection */
        $connection = $this->container->getByType('\Dibi\Connection');
        $connection->loadFile(__DIR__ . '/sql/table.sql');
        $count = $connection->loadFile(__DIR__ . '/sql/data.sql');
        $this->assertSame(5, $count);
    }

    /** @depends testInit */
    /** @dataProvider dataParentValue */
    public function testParent($id, $expectedParent): void
    {
        $node = $this->tree->getNode($id);
        $this->assertSame($expectedParent, $node->getParent());
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

    /** @depends testParent */
    /** @dataProvider dataNumberChildsValue */
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

        $path = $this->tree->getPath($root, false);
        $this->assertSame(1, $path->count());
        
        $branch = $this->tree->getBranch($root, \null);
        $this->assertSame(\null, $branch->getRootNode()->getParent());
        $this->assertSame(4, $branch->count());
        
        $this->assertSame(0, $branch->current()->count());
        $branch->next();
        $this->assertSame(6, $branch->current()->current()->current()->getRootNode()->getParent());

        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
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
        $nodeA1 = $this->tree->getNode(5);
        $data = [
            'name' => 'BranchE',
        ];
        $newId = $this->tree->createNodeAfter($data, $nodeA1);
        $this->assertSame(14, $newId);

        $nodeA2 = $this->tree->getNode(14);
        $this->assertSame(1, $nodeA2->getParent());

        $root = $this->tree->getRoot();
        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $this->tree->createNodeAfter($data, $root);
    }

    /** @depends testCreateNodeAfter */
    public function testCreateNodeAsFirstChild(): void
    {
        $nodeA1 = $this->tree->getRoot();
        $data = [
            'name' => 'Branch0',
        ];
        $newId = $this->tree->createNodeAsFirstChild($data, $nodeA1);
        $this->assertSame(15, $newId);

        $nodeA2 = $this->tree->getNode(15);
        $this->assertSame(1, $nodeA2->getParent());
    }

    /** @depends testCreateNodeAsFirstChild */
    public function testRemoveNode(): void
    {
        $nodeA1 = $this->tree->getNode(3);
        $this->tree->removeNode($nodeA1);
        $nodeA2 = $this->tree->getNode(3);
        $this->assertSame(\null, $nodeA2);

        $nodeA3 = $this->tree->getNode(6);
        $this->assertSame(1, $nodeA3->getParent());
    }

    /** @depends testRemoveNode */
    public function testMoveBranchAsChild(): void
    {
        $nodeA1 = $this->tree->getNode(12);
        $goalA = $this->tree->getNode(4);
        $this->tree->moveBranchAsChild($nodeA1, $goalA);

        $nodeA2 = $this->tree->getNode(12);
        $this->assertSame(4, $nodeA2->getParent());

        $nodeB1 = $this->tree->getNode(4);
        $goalB = $this->tree->getNode(2);
        $this->tree->moveBranchAsChild($nodeB1, $goalB);

        $nodeB2 = $this->tree->getNode(4);
        $this->assertSame(2, $nodeB2->getParent());

        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $nodeC1 = $this->tree->getNode(9);
        $goalC = $this->tree->getNode(13);
        $this->tree->moveBranchAsChild($nodeC1, $goalC);
    }

    /** @depends testMoveBranchAsChild */
    public function testRootMoveBranchAsChild(): void
    {
        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $nodeA = $this->tree->getNode(1);
        $goalA = $this->tree->getNode(2);
        $this->tree->moveBranchAfter($nodeA, $goalA);
    }

    /** @depends testRootMoveBranchAsChild */
    public function testMoveBranchAfter(): void
    {
        $nodeA1 = $this->tree->getNode(8);
        $goalA = $this->tree->getNode(12);
        $this->tree->moveBranchAfter($nodeA1, $goalA);

        $nodeA2 = $this->tree->getNode(8);
        $this->assertSame(4, $nodeA2->getParent());

        $nodeB1 = $this->tree->getNode(9);
        $goalB = $this->tree->getNode(4);
        $this->tree->moveBranchAfter($nodeB1, $goalB);

        $nodeB2 = $this->tree->getNode(9);
        $this->assertSame(2, $nodeB2->getParent());

        $nodeC1 = $this->tree->getNode(4);
        $goalC = $this->tree->getNode(7);
        $this->tree->moveBranchAfter($nodeC1, $goalC);

        $nodeC2 = $this->tree->getNode(4);
        $this->assertSame(6, $nodeC2->getParent());

        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $nodeD1 = $this->tree->getNode(2);
        $goalD = $this->tree->getNode(13);
        $this->tree->moveBranchAfter($nodeD1, $goalD);
    }

    /** @depends testMoveBranchAfter */
    public function testRootMoveBranchAfter(): void
    {
        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $nodeA = $this->tree->getNode(2);
        $goalA = $this->tree->getNode(1);
        $this->tree->moveBranchAfter($nodeA, $goalA);
    }

    /** @depends testRootMoveBranchAfter */
    public function testBranch(): void
    {
        $root = $this->tree->getRoot();
        $nodeList = $this->tree->getBranch($root);
        $this->assertSame(14, $nodeList->count());
        $this->assertSame(1, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(15, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(2, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(9, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(13, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(6, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(7, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(4, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(12, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(8, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(10, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(11, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(5, $nodeList->current()->getId());

        $nodeList->next();
        $this->assertSame(14, $nodeList->current()->getId());
    }

}
