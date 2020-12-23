<?php

/*
 * Part of the Nested Sets package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Cartalyst PSL License.
 *
 * This source file is subject to the Cartalyst PSL License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Nested Sets
 * @version    7.0.0
 * @author     Cartalyst LLC
 * @license    Cartalyst PSL
 * @copyright  (c) 2011-2020, Cartalyst LLC
 * @link       https://cartalyst.com
 */

namespace Cartalyst\NestedSets\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Cartalyst\NestedSets\Nodes\NodeTrait;
use Cartalyst\NestedSets\Nodes\NodeInterface;

class NodeTraitTest extends TestCase
{
    /**
     * Setup resources and dependencies.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/stubs/DummyWorker.php';
    }

    /**
     * Close mockery.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        m::close();
    }

    public function testChildrenManipulation()
    {
        $node = new Node();

        $node->setChildren(['foo']);
        $this->assertCount(1, $node->getChildren());
        $this->assertSame(['foo'], $node->getChildren());

        $node->clearChildren();
        $this->assertEmpty($node->getChildren());

        $node->setChildAtIndex($child1 = new Node(), 2);
        $this->assertCount(1, $children = $node->getChildren());
        $this->assertSame($child1, reset($children));
        $this->assertSame(2, key($children));
    }

    public function testSettingHelper()
    {
        $node = new Node();
        $this->addMockConnection($node);
        $node->setWorker('DummyWorker');
        $this->assertInstanceOf('DummyWorker', $node->createWorker());
    }

    public function testPresenterNotSet1()
    {
        $this->expectException('RuntimeException');

        $node = new Node();
        $node->presentAs('qux', 2);
    }

    public function testPresenterNotSet2()
    {
        $this->expectException('RuntimeException');

        $node = new Node();
        $node->presentAsBaz('qux', 2);
    }

    public function testPresenterNotSet3()
    {
        $this->expectException('RuntimeException');

        $node = new Node();
        $node->presentChildrenAs('qux', 2);
    }

    public function testPresenter()
    {
        $presenter       = m::mock('Cartalyst\NestedSets\Presenter');
        Node::$presenter = $presenter;
        $this->assertSame($presenter, Node::$presenter);

        $node = new Node();
        $presenter->shouldReceive('presentAs')->with($node, 'foo', 'bar', 0)->once()->andReturn('success');
        $this->assertSame('success', $node->presentAs('foo', 'bar'));

        $presenter->shouldReceive('presentAs')->with($node, 'baz', 'qux', 2)->once()->andReturn('success');
        $this->assertSame('success', $node->presentAsBaz('qux', 2));
    }

    public function testFindingChildrenAlwaysReturnsArray()
    {
        $node = m::mock('Cartalyst\NestedSets\Tests\Node[createWorker]');
        $node->shouldReceive('createWorker')->once()->andReturn($worker = m::mock('Cartalyst\NestedSets\Workers\WorkerInterface'));
        $worker->shouldReceive('tree')->with($node, 0, null)->once()->andReturn($treeNode = new Node());
        $this->assertSame([$treeNode], $node->findChildren());
    }

    protected function addMockConnection($model)
    {
        $model->setConnectionResolver($resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'));

        $resolver->shouldReceive('connection')->andReturn(m::mock('Illuminate\Database\Connection'));
        $model->getConnection()->shouldReceive('getQueryGrammar')->andReturn(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
        $model->getConnection()->shouldReceive('getPostProcessor')->andReturn(m::mock('Illuminate\Database\Query\Processors\Processor'));
    }
}

class Node extends Model implements NodeInterface
{
    use NodeTrait;
}
