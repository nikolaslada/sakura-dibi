<?php

declare(strict_types=1);

namespace Tests\Traversal;

require_once __DIR__ . '/../bootstrap.php';

class RepositoryTest extends \Tests\Base\TestCase
{

    /** @var \Sakura\Traversal\Tree */
    private $tree;


    public function setUp(): void
    {
        parent::setUp();
        $this->tree = $this->container->getByType('\Sakura\Traversal\Tree');
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
    /** @dataProvider dataLeftRightValue */
    public function testLeftRight($id, $expectedLeft, $expectedRight): void
    {
        $node = $this->tree->getNode($id);
        $this->assertSame($expectedLeft, $node->getLeft());
        $this->assertSame($expectedRight, $node->getRight());
    }

    public function dataLeftRightValue(): array
    {
        return [
            [1, 1, 26,],
            [5, 24, 25,],
            [7, 6, 7,],
            [11, 14, 15],
            [13, 20, 21],
        ];
    }

    /** @depends testOrder */
    /** @dataProvider dataDepthValue */
    public function testDepth($id, $expectedDepth): void
    {
        $node = $this->tree->getNode($id);
        $depth = $this->tree->getDepth($node);
        $this->assertSame($expectedDepth, $depth);
    }

    public function dataDepthValue(): array
    {
        return [
            [1, 0,],
            [2, 1],
            [3, 1],
            [4, 1],
            [5, 1],
            [6, 2,],
            [7, 3,],
            [8, 2,],
            [9, 2,],
            [10, 3,],
            [11, 3,],
            [12, 3,],
            [13, 3,],
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
        $this->assertSame(26, $nodeA2->getLeft());
        $this->assertSame(27, $nodeA2->getRight());
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
        $this->assertSame(2, $nodeA2->getLeft());
        $this->assertSame(3, $nodeA2->getRight());
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
        $this->assertSame(6, $nodeA3->getLeft());
        $this->assertSame(9, $nodeA3->getRight());
        $this->assertSame(1, $nodeA3->getParent());
    }

    /** @depends testRemoveNode */
    public function testMoveBranchAsFirstChild(): void
    {
        $firstNodeInCurrenBefore = $this->tree->getNode(7);
        $goalBranch = $this->tree->getNode(6);
        $this->tree->moveBranchAsFirstChild($firstNodeInCurrenBefore, $goalBranch);
        
        $firstNodeInBranchAfter = $this->tree->getNode(7);
        $this->assertSame(7, $firstNodeInBranchAfter->getLeft());
        $this->assertSame(8, $firstNodeInBranchAfter->getRight());
        $this->assertSame(6, $firstNodeInBranchAfter->getParent());
        
        $nodeInsideBranchBefore = $this->tree->getNode(12);
        $goalOutsideBranch = $this->tree->getNode(4);
        $this->tree->moveBranchAsFirstChild($nodeInsideBranchBefore, $goalOutsideBranch);

        $nodeInsideBranchAfter = $this->tree->getNode(12);
        $this->assertSame(11, $nodeInsideBranchAfter->getLeft());
        $this->assertSame(12, $nodeInsideBranchAfter->getRight());
        $this->assertSame(4, $nodeInsideBranchAfter->getParent());

        $nodeAfterGoalBefore = $this->tree->getNode(4);
        $goalBeforeNode = $this->tree->getNode(2);
        $this->tree->moveBranchAsFirstChild($nodeAfterGoalBefore, $goalBeforeNode);

        $nodeAfterGoalAfter = $this->tree->getNode(4);
        $this->assertSame(5, $nodeAfterGoalAfter->getLeft());
        $this->assertSame(18, $nodeAfterGoalAfter->getRight());
        $this->assertSame(2, $nodeAfterGoalAfter->getParent());

        $nodeBeforeGoalBefore = $this->tree->getNode(4);
        $goalAfterNode = $this->tree->getNode(7);
        $this->tree->moveBranchAsFirstChild($nodeBeforeGoalBefore, $goalAfterNode);

        $nodeBeforeGoalAfter = $this->tree->getNode(4);
        $this->assertSame(8, $nodeBeforeGoalAfter->getLeft());
        $this->assertSame(21, $nodeBeforeGoalAfter->getRight());
        $this->assertSame(7, $nodeBeforeGoalAfter->getParent());

        $nodeBackBefore = $this->tree->getNode(4);
        $goalPreviousBranch = $this->tree->getNode(2);
        $this->tree->moveBranchAsFirstChild($nodeBackBefore, $goalPreviousBranch);

        $nodeBackAfter = $this->tree->getNode(4);
        $this->assertSame(5, $nodeBackAfter->getLeft());
        $this->assertSame(18, $nodeBackAfter->getRight());
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
        $this->assertSame(8, $branchAfterGoalAfter->getLeft());
        $this->assertSame(13, $branchAfterGoalAfter->getRight());
        $this->assertSame(4, $branchAfterGoalAfter->getParent());

        $branchInsideGoalBefore = $this->tree->getNode(9);
        $goalOutsideBranch = $this->tree->getNode(4);
        $this->tree->moveBranchAfter($branchInsideGoalBefore, $goalOutsideBranch);

        $branchInsideGoalAfter = $this->tree->getNode(9);
        $this->assertSame(15, $branchInsideGoalAfter->getLeft());
        $this->assertSame(18, $branchInsideGoalAfter->getRight());
        $this->assertSame(2, $branchInsideGoalAfter->getParent());

        $branchBeforeGoalBefore = $this->tree->getNode(4);
        $goalAfterBranch = $this->tree->getNode(7);
        $this->tree->moveBranchAfter($branchBeforeGoalBefore, $goalAfterBranch);

        $branchBeforeGoalAfter = $this->tree->getNode(4);
        $this->assertSame(13, $branchBeforeGoalAfter->getLeft());
        $this->assertSame(22, $branchBeforeGoalAfter->getRight());
        $this->assertSame(6, $branchBeforeGoalAfter->getParent());

        $this->expectException(\Sakura\Exceptions\BadArgumentException::class);
        $branchOverGoal = $this->tree->getNode(2);
        $goalUnderBranch = $this->tree->getNode(13);
        $this->tree->moveBranchAfter($branchOverGoal, $goalUnderBranch);
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
