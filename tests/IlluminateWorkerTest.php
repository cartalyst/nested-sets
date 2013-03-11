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

	public function testCreatingGap()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Eloquent\Builder'));
		$query->shouldReceive('where')->with('lft', '>=', 1)->once()->andReturn($query);
		$query->shouldReceive('where')->with('tree', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('update')->with(array('lft' => 'lft + 2'))->once();

		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Eloquent\Builder'));
		$query->shouldReceive('where')->with('rgt', '>=', 1)->once()->andReturn($query);
		$query->shouldReceive('where')->with('tree', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('update')->with(array('rgt' => 'rgt + 2'))->once();

		$worker->createGap(1, 2, 3);
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
