<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

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
        $this->assertSame(5, $count);
    }

    /** @depends testInit */
    /** @dataProvider dataOrderValue */
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

    /** @depends testOrder */
    /** @dataProvider dataDepthValue */
    public function testDepth($id, $expectedDepth): void
    {
        $depth = $this->tree->getDepth($id);
        $this->assertSame($expectedDepth, $depth);
    }

    public function dataDepthValue(): array
    {
        return [
            [1, 0,],
            [2, 1],
            [6, 2,],
            [11, 3,],
            [13, 3],
        ];
    }

    /** @depends testDepth */
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
        $this->assertSame(13, $branch->count());

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
        $this->assertSame(14, $nodeA2->getOrder());
        $this->assertSame(1, $nodeA2->getDepth());
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
        $this->assertSame(2, $nodeA2->getOrder());
        $this->assertSame(1, $nodeA2->getDepth());
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
        $this->assertSame(4, $nodeA3->getOrder());
        $this->assertSame(1, $nodeA3->getDepth());
        $this->assertSame(1, $nodeA3->getParent());
    }

    /** @depends testRemoveNode */
    public function testMoveBranchAsFirstChild(): void
    {
        $firstNodeInCurrenBefore = $this->tree->getNode(7);
        $goalBranch = $this->tree->getNode(6);
        $this->tree->moveBranchAsFirstChild($firstNodeInCurrenBefore, $goalBranch);
        
        $firstNodeInBranchAfter = $this->tree->getNode(7);
        $this->assertSame(5, $firstNodeInBranchAfter->getOrder());
        $this->assertSame(2, $firstNodeInBranchAfter->getDepth());
        $this->assertSame(6, $firstNodeInBranchAfter->getParent());
        
        $nodeInsideBranchBefore = $this->tree->getNode(12);
        $goalOutsideBranch = $this->tree->getNode(4);
        $this->tree->moveBranchAsFirstChild($nodeInsideBranchBefore, $goalOutsideBranch);

        $nodeInsideBranchAfter = $this->tree->getNode(12);
        $this->assertSame(7, $nodeInsideBranchAfter->getOrder());
        $this->assertSame(2, $nodeInsideBranchAfter->getDepth());
        $this->assertSame(4, $nodeInsideBranchAfter->getParent());

        $nodeAfterGoalBefore = $this->tree->getNode(4);
        $goalBeforeNode = $this->tree->getNode(2);
        $this->tree->moveBranchAsFirstChild($nodeAfterGoalBefore, $goalBeforeNode);

        $nodeAfterGoalAfter = $this->tree->getNode(4);
        $this->assertSame(4, $nodeAfterGoalAfter->getOrder());
        $this->assertSame(2, $nodeAfterGoalAfter->getDepth());
        $this->assertSame(2, $nodeAfterGoalAfter->getParent());

        $nodeBeforeGoalBefore = $this->tree->getNode(4);
        $goalAfterNode = $this->tree->getNode(7);
        $this->tree->moveBranchAsFirstChild($nodeBeforeGoalBefore, $goalAfterNode);

        $nodeBeforeGoalAfter = $this->tree->getNode(4);
        $this->assertSame(6, $nodeBeforeGoalAfter->getOrder());
        $this->assertSame(3, $nodeBeforeGoalAfter->getDepth());
        $this->assertSame(7, $nodeBeforeGoalAfter->getParent());

        $nodeBackBefore = $this->tree->getNode(4);
        $goalPreviousBranch = $this->tree->getNode(2);
        $this->tree->moveBranchAsFirstChild($nodeBackBefore, $goalPreviousBranch);

        $nodeBackAfter = $this->tree->getNode(4);
        $this->assertSame(4, $nodeBackAfter->getOrder());
        $this->assertSame(2, $nodeBackAfter->getDepth());
        $this->assertSame(2, $nodeBackAfter->getParent());

        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $nodeC1 = $this->tree->getNode(9);
        $goalC = $this->tree->getNode(13);
        $this->tree->moveBranchAsFirstChild($nodeC1, $goalC);
    }

    /** @depends testMoveBranchAsFirstChild */
    public function testRootMoveBranchAsFirstChild(): void
    {
        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $nodeA = $this->tree->getNode(1);
        $goalA = $this->tree->getNode(2);
        $this->tree->moveBranchAfter($nodeA, $goalA);
    }

    /** @depends testRootMoveBranchAsFirstChild */
    public function testMoveBranchAfter(): void
    {
        $branchAfterGoalBefore = $this->tree->getNode(8);
        $goalBeforeBranch = $this->tree->getNode(12);
        $this->tree->moveBranchAfter($branchAfterGoalBefore, $goalBeforeBranch);

        $branchAfterGoalAfter = $this->tree->getNode(8);
        $this->assertSame(6, $branchAfterGoalAfter->getOrder());
        $this->assertSame(3, $branchAfterGoalAfter->getDepth());
        $this->assertSame(4, $branchAfterGoalAfter->getParent());

        $branchInsideGoalBefore = $this->tree->getNode(9);
        $goalOutsideBranch = $this->tree->getNode(4);
        $this->tree->moveBranchAfter($branchInsideGoalBefore, $goalOutsideBranch);

        $branchInsideGoalAfter = $this->tree->getNode(9);
        $this->assertSame(9, $branchInsideGoalAfter->getOrder());
        $this->assertSame(2, $branchInsideGoalAfter->getDepth());
        $this->assertSame(2, $branchInsideGoalAfter->getParent());

        $branchBeforeGoalBefore = $this->tree->getNode(4);
        $goalAfterBranch = $this->tree->getNode(7);
        $this->tree->moveBranchAfter($branchBeforeGoalBefore, $goalAfterBranch);

        $branchBeforeGoalAfter = $this->tree->getNode(4);
        $this->assertSame(8, $branchBeforeGoalAfter->getOrder());
        $this->assertSame(2, $branchBeforeGoalAfter->getDepth());
        $this->assertSame(6, $branchBeforeGoalAfter->getParent());

        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $branchOverGoal = $this->tree->getNode(2);
        $goalUnderBranch = $this->tree->getNode(13);
        $this->tree->moveBranchAfter($branchOverGoal, $goalUnderBranch);
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

    /** @depends testBranch */
    public function testGetNodeListByParent(): void
    {
        $root = $this->tree->getRoot();
        $nodeListA = $this->tree->getNodeListByParent($root->getId());
        $this->assertSame(5, $nodeListA->count());
        
        $nodeListB = $this->tree->getNodeListByParent(9);
        $this->assertSame(13, $nodeListB->current()->getId());
    }

}
