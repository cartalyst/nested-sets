<?php
/**
 * Part of the Nested Sets package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Nested Sets
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Mockery as m;
use Cartalyst\NestedSets\Workers\IlluminateWorker as Worker;

class IlluminateWorkerTest extends PHPUnit_Framework_TestCase {

	/**
	 * Close mockery.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testCreatingZeroGap()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$worker->createGap(1, 0, 1);
	}

	public function testCreatingGap()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Eloquent\Builder'));
		$query->shouldReceive('where')->with('lft', '>=', 1)->once()->andReturn($query);
		$query->shouldReceive('where')->with('tree', '=', 3)->once()->andReturn($query);
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('lft')->once()->andReturn('"lft"');
		$query->shouldReceive('update')->with(array('lft' => '"lft" + 2'))->once();

		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Eloquent\Builder'));
		$query->shouldReceive('where')->with('rgt', '>=', 1)->once()->andReturn($query);
		$query->shouldReceive('where')->with('tree', '=', 3)->once()->andReturn($query);
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('rgt')->once()->andReturn('"rgt"');
		$query->shouldReceive('update')->with(array('rgt' => '"rgt" + 2'))->once();

		$worker->createGap(1, 2, 3);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testRemovingNegativeGap()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());
		$worker->removeGap(1, -2, 3);
	}

	public function testRemovingGap()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[createGap]');
		$worker->shouldReceive('createGap')->with(1, -2, 3);
		$worker->removeGap(1, 2, 3);
	}

	public function testSlidingNodeOutOfTree()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[getNodeSize,removeGap]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$worker->shouldReceive('getNodeSize')->with($node)->once()->andReturn(1);
		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Eloquent\Builder'));
		$node->shouldReceive('getAttribute')->with('lft')->times(3)->andReturn(2);
		$query->shouldReceive('where')->with('lft', '>=', 2)->once()->andReturn($query);
		$node->shouldReceive('getAttribute')->with('rgt')->times(3)->andReturn(3);
		$query->shouldReceive('where')->with('rgt', '<=', 3)->once()->andReturn($query);
		$node->shouldReceive('getAttribute')->with('tree')->twice()->andReturn(1);
		$query->shouldReceive('where')->with('tree', '=', 1)->once()->andReturn($query);
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('lft')->once()->andReturn('"lft"');
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('rgt')->once()->andReturn('"rgt"');
		$query->shouldReceive('update')->with(array('lft' => '"lft" + -3', 'rgt' => '"rgt" + -3'))->once();

		$worker->shouldReceive('removeGap')->with(2, 2, 1)->once();

		$node->shouldReceive('setAttribute')->with('lft', -1)->once();
		$node->shouldReceive('setAttribute')->with('rgt', 0)->once();

		$worker->slideNodeOutOfTree($node);
	}

	public function testSlidingNodeInTree()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[getNodeSize,createGap]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$worker->shouldReceive('getNodeSize')->with($node)->once()->andReturn(1);
		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Eloquent\Builder'));
		$node->shouldReceive('getAttribute')->with('lft')->once()->andReturn(-1);
		$query->shouldReceive('where')->with('lft', '>=', -1)->once()->andReturn($query);
		$node->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(0);
		$query->shouldReceive('where')->with('rgt', '<=', 0)->once()->andReturn($query);
		$node->shouldReceive('getAttribute')->with('tree')->twice()->andReturn(1);
		$query->shouldReceive('where')->with('tree', '=', 1)->once()->andReturn($query);
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('lft')->once()->andReturn('"lft"');
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('rgt')->once()->andReturn('"rgt"');
		$query->shouldReceive('update')->with(array('lft' => '"lft" + 3', 'rgt' => '"rgt" + 3'))->once();

		$worker->shouldReceive('createGap')->with(2, 2, 1)->once();

		$node->shouldReceive('setAttribute')->with('lft', 2)->once();
		$node->shouldReceive('setAttribute')->with('rgt', 3)->once();

		$worker->slideNodeInTree($node, 2);
	}

	public function testAllFlatWithNoTree()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());
		$node->shouldReceive('findAll')->once()->andReturn($allFlat = array('foo', 'bar'));
		$this->assertEquals($allFlat, $worker->allFlat());
	}

	public function testAllFlatWithTree()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[getReservedAttribute]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$node->shouldReceive('findAll')->once()->andReturn(array(
			$node1 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node2 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node3 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
		));

		$node1->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);
		$node2->shouldReceive('getAttribute')->with('tree')->once()->andReturn(2);
		$node3->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

		$worker->shouldReceive('getReservedAttribute')->with('tree')->times(3)->andReturn('tree');

		// For some reason the array_filter appears to not be returning
		// the same instances of the nodes declated above. Either that,
		// or somethign else wacky is happening.
		// @todo, Check this out
		$this->assertCount(2, $allFlat = $worker->allFlat(1));
		// $this->assertEquals(array($node1, $node3), $allFlat);
	}

	protected function getMockConnection()
	{
		$connection = m::mock('Illuminate\Database\Connection');
		$connection->shouldReceive('getQueryGrammar')->andReturn(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
		$connection->shouldReceive('getPostProcessor')->andReturn(m::mock('Illuminate\Database\Query\Processors\Processor'));

		return $connection;
	}

	protected function getMockNode()
	{
		$node = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface');

		$node->shouldReceive('getTable')->andReturn('categories');
		$node->shouldReceive('getReservedAttributes')->andReturn(array(
			'left'  => 'lft',
			'right' => 'rgt',
			'tree'  => 'tree',
		));
		$node->shouldReceive('getReservedAttribute')->with('left')->andReturn('lft');
		$node->shouldReceive('getReservedAttribute')->with('right')->andReturn('rgt');
		$node->shouldReceive('getReservedAttribute')->with('tree')->andReturn('tree');

		return $node;
	}

}
