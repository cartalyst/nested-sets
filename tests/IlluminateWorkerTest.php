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

		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
		$query->shouldReceive('where')->with('lft', '>=', 1)->once()->andReturn($query);
		$query->shouldReceive('where')->with('tree', '=', 3)->once()->andReturn($query);
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('lft')->once()->andReturn('"lft"');
		$query->shouldReceive('update')->with(array('lft' => '"lft" + 2'))->once();

		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
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
		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
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
		$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
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

	public function testTransaction()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$callback = function(Illuminate\Database\Connection $connection)
		{
			$_SERVER['__nested_sets.dynamic_query'] = true;
		};

		$connection->shouldReceive('transaction')->with(m::on(function($actualCallback) use ($connection, $callback)
		{
			if ($actualCallback == $callback) $actualCallback($connection);

			return ($actualCallback instanceof Closure);
		}))->once();

		$worker->dynamicQuery($callback);
		$this->assertTrue(isset($_SERVER['__nested_sets.dynamic_query']));
		unset($_SERVER['__nested_sets.dynamic_query']);
		$worker->dynamicQuery($callback, false);
		$this->assertTrue($_SERVER['__nested_sets.dynamic_query']);
		unset($_SERVER['__nested_sets.dynamic_query']);
	}

	public function testInsertNode()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());
		$connection->shouldReceive('table')->with('categories')->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));

		$node1 = $this->getMockNode();
		$node1->shouldReceive('getIncrementing')->once()->andReturn(true);
		$node1->shouldReceive('getAttributes')->once()->andReturn($attributes = array('foo'));
		$query->shouldReceive('insertGetId')->with($attributes)->once()->andReturn('bar');
		$node1->shouldReceive('setAttribute')->with('id', 'bar')->once();
		$worker->insertNode($node1);

		$node2 = $this->getMockNode();
		$node2->shouldReceive('getIncrementing')->once()->andReturn(false);
		$node2->shouldReceive('getAttributes')->once()->andReturn($attributes);
		$query->shouldReceive('insert')->with($attributes)->once();
		$worker->insertNode($node2);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testHydrateNodeForNonExistentNode()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());
		$connection->shouldReceive('table')->with('categories')->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));

		$node->shouldReceive('getAttribute')->with('id')->once()->andReturn(1);
		$query->shouldReceive('where')->with('id', '=', 1)->once()->andReturn($query);
		$query->shouldReceive('first')->with(array('lft', 'rgt', 'tree'))->once()->andReturn(null);

		$worker->hydrateNode($node);
	}

	public function testHydrateNode()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());
		$connection->shouldReceive('table')->with('categories')->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));

		$node->shouldReceive('getAttribute')->with('id')->once()->andReturn(1);
		$query->shouldReceive('where')->with('id', '=', 1)->once()->andReturn($query);

		$result = new StdClass;
		$result->lft  = 2;
		$result->rgt  = 3;
		$result->tree = 4;

		$query->shouldReceive('first')->with(array('lft', 'rgt', 'tree'))->once()->andReturn($result);

		$node->shouldReceive('setAttribute')->with('lft', 2)->once();
		$node->shouldReceive('setAttribute')->with('rgt', 3)->once();
		$node->shouldReceive('setAttribute')->with('tree', 4)->once();

		$worker->hydrateNode($node);
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

		$worker->shouldReceive('getReservedAttribute')->with('tree')->times(3)->andReturn('tree');

		$node1->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);
		$node2->shouldReceive('getAttribute')->with('tree')->once()->andReturn(2);
		$node3->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

		// For some reason the array_filter appears to not be returning
		// the same instances of the nodes declated above. Either that,
		// or somethign else wacky is happening.
		// @todo, Check this out
		$this->assertCount(2, $allFlat = $worker->allFlat(1));
		// $this->assertEquals(array($node1, $node3), $allFlat);
	}

	public function testAllRoot()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[getReservedAttribute]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$node->shouldReceive('findAll')->once()->andReturn(array(
			$node1 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node2 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node3 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
		));

		$worker->shouldReceive('getReservedAttribute')->with('left')->times(3)->andReturn('lft');

		$node1->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);
		$node2->shouldReceive('getAttribute')->with('lft')->once()->andReturn(2);
		$node3->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);

		$this->assertCount(2, $worker->allRoot());
	}

	public function testAllLeafWithNoTree()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[getReservedAttribute]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$node->shouldReceive('findAll')->once()->andReturn(array(
			$node1 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node2 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node3 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
		));

		$worker->shouldReceive('getReservedAttribute')->with('right')->times(3)->andReturn('rgt');
		$worker->shouldReceive('getReservedAttribute')->with('left')->times(3)->andReturn('lft');

		$node1->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(2);
		$node1->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);

		$node2->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(4);
		$node2->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);

		$node3->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(3);
		$node3->shouldReceive('getAttribute')->with('lft')->once()->andReturn(2);

		$this->assertCount(2, $worker->allLeaf());
	}

	public function testAllLeafWithTree()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[getReservedAttribute]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$node->shouldReceive('findAll')->once()->andReturn(array(
			$node1 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node2 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
			$node3 = m::mock('Cartalyst\NestedSets\Nodes\NodeInterface'),
		));

		$worker->shouldReceive('getReservedAttribute')->with('right')->times(3)->andReturn('rgt');
		$worker->shouldReceive('getReservedAttribute')->with('left')->times(3)->andReturn('lft');
		$worker->shouldReceive('getReservedAttribute')->with('tree')->twice()->andReturn('tree');

		$node1->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(2);
		$node1->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);
		$node1->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

		$node2->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(4);
		$node2->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);

		$node3->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(3);
		$node3->shouldReceive('getAttribute')->with('lft')->once()->andReturn(2);
		$node3->shouldReceive('getAttribute')->with('tree')->once()->andReturn(3);

		$this->assertCount(1, $worker->allLeaf(1));
	}

	public function testPath()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$connection->shouldReceive('table')->with('categories as node')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
		$query->shouldReceive('join')->with('categories as parent', 'node.lft', '>=', 'parent.lft')->once()->andReturn($query);
		$query->shouldReceive('where')->with('node.lft', '<=', 'parent.rgt')->once()->andReturn($query);
		$node->shouldReceive('getAttribute')->with('id')->once()->andReturn(3);
		$query->shouldReceive('where')->with('node.id', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('orderBy')->with('node.lft')->once()->andReturn($query);

		$result1 = new StdClass;
		$result1->id = 3;
		$result2 = new StdClass;
		$result2->id = 2;
		$result3 = new StdClass;
		$result3->id = 1;

		$query->shouldReceive('get')->with('parent.id')->once()->andReturn(array($result3, $result2, $result1));

		$this->assertCount(3, $path = $worker->path($node));
		$this->assertEquals('123', implode('', $path));
	}

	public function testDepth()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$connection->shouldReceive('table')->with('categories as node')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
		$query->shouldReceive('join')->with('categories as parent', 'node.lft', '>=', 'parent.lft')->once()->andReturn($query);
		$query->shouldReceive('where')->with('node.lft', '<=', 'parent.rgt')->once()->andReturn($query);
		$node->shouldReceive('getAttribute')->with('id')->once()->andReturn(3);
		$query->shouldReceive('where')->with('node.id', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('orderBy')->with('node.lft')->once()->andReturn($query);
		$query->shouldReceive('groupBy')->with('node.lft')->once()->andReturn($query);

		$connection->getQueryGrammar()->shouldReceive('wrap')->with('parent.id')->once()->andReturn('"parent"."id"');
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('depth')->once()->andReturn('"depth"');

		$result = new StdClass;
		$result->depth = 4;

		// For some reason, unlike other tests, we have to actually ensure the
		// expression is cast as a string when used in the "select" clause. When
		// used in "where" clauses, the query builder must cast it as a string
		// and hence remove the need for us to do it here in our tests.
		$query->shouldReceive('first')->with(m::on(function($expression)
		{
			return ((string) $expression == '(count("parent"."id") - 1) as "depth"');
		}))->andReturn($result);

		$this->assertEquals(4, $worker->depth($node));
	}

	public function testTree()
	{
		$worker = new Worker($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$node->shouldReceive('getAttribute')->with('id')->once()->andReturn(1);
		$node->shouldReceive('getAttribute')->with('tree')->once()->andReturn(3);

		$connection->shouldReceive('table')->with('categories as node')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));
		$query->shouldReceive('join')->with('categories as parent', 'node.lft', '>=', 'parent.lft')->once()->andReturn($query);
		$query->shouldReceive('where')->with('node.lft', '<=', 'parent.rgt')->once()->andReturn($query);
		$query->shouldReceive('join')->with('categories as sub_parent', 'node.lft', '>=', 'sub_parent.lft')->once()->andReturn($query);
		$query->shouldReceive('where')->with('node.lft', '<=', 'sub_parent.rgt')->once()->andReturn($query);

		$connection->shouldReceive('table')->with('categories as node')->once()->andReturn($subQuery = m::mock('Illuminate\Database\Query\Builder'));

		// We need to mock our sub-query that we put in our join
		$query->shouldReceive('join')->with('sub_tree', m::on(function($closure) use ($subQuery, $connection)
		{
			$join = m::mock('Illuminate\Database\Query\JoinClause');

			$connection->getQueryGrammar()->shouldReceive('wrap')->with('parent.id')->once()->andReturn('"parent"."id"');
			$connection->getQueryGrammar()->shouldReceive('wrap')->with('depth')->once()->andReturn('"depth"');

			$subQuery->shouldReceive('select')->with('node.id', m::on(function($expression)
			{
				return ((string) $expression == '(count("parent"."id") - 1) as "depth"');
			}))->once()->andReturn($subQuery);

			$subQuery->shouldReceive('join')->with('categories as parent', 'node.lft', '>=', 'parent.lft')->once()->andReturn($subQuery);
			$subQuery->shouldReceive('where')->with('node.lft', '<=', 'parent.rgt')->once()->andReturn($subQuery);
			$subQuery->shouldReceive('where')->with('node.id', '=', 1)->once()->andReturn($subQuery);
			$subQuery->shouldReceive('whereBetween')->with('node.lft', array('parent.lft', 'parent.rgt'))->once()->andReturn($subQuery);
			$subQuery->shouldReceive('where')->with('node.tree', '=', 3)->once()->andReturn($subQuery);
			$subQuery->shouldReceive('where')->with('parent.tree', '=', 3)->once()->andReturn($subQuery);
			$subQuery->shouldReceive('orderBy')->with('node.lft')->once()->andReturn($subQuery);
			$subQuery->shouldReceive('groupBy')->with('node.id')->once()->andReturn($subQuery);

			$subQuery->shouldReceive('toSql')->once()->andReturn('foo');

			$join->table = 'categories';
			$connection->getQueryGrammar()->shouldReceive('wrap')->with('categories')->once()->andReturn('"categories"');

			$join->shouldReceive('on')->with('sub_parent.id', '=', 'sub_tree.id')->once();

			// Call our closure
			$closure($join);

			$this->assertEquals('(foo) as "categories"', $join->table);

			// Our assertions will ensure we catch any errors, safe to
			// return true here.
			return true;
		}))->once();

		$query->shouldReceive('mergeBindings')->with(m::type('Illuminate\Database\Query\Builder'))->once();

		$query->shouldReceive('where')->with('node.tree', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('where')->with('parent.tree', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('where')->with('sub_parent.tree', '=', 3)->once()->andReturn($query);
		$query->shouldReceive('orderBy')->with('node.lft')->once()->andReturn($query);
		$query->shouldReceive('groupBy')->with('node.id')->once()->andReturn($query);
		$query->shouldReceive('having')->with('depth', '<=', 2)->once()->andReturn($query);

		$connection->getQueryGrammar()->shouldReceive('wrap')->with('parent.id')->once()->andReturn('"parent"."id"');
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('sub_tree.depth')->once()->andReturn('"sub_tree"."depth"');
		$connection->getQueryGrammar()->shouldReceive('wrap')->with('depth')->once()->andReturn('"depth"');

		$query->shouldReceive('get')->with('node.*', m::on(function($expression)
		{
			return ((string) $expression == '(count("parent"."id") - ("sub_tree"."depth" + 1)) as "depth"');
		}))->once();

		$worker->tree($node, 2);
	}

	public function testInsertNodeAsRoot()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,insertNode]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $node)
		{
			$connection->shouldReceive('table')->with('categories')->once()->andReturn($query = m::mock('Illuminate\Database\Query\Builder'));

			$query->shouldReceive('max')->with('tree')->once()->andReturn(3);

			$node->shouldReceive('setAttribute')->with('lft', 1)->once();
			$node->shouldReceive('setAttribute')->with('rgt', 2)->once();
			$node->shouldReceive('setAttribute')->with('tree', 4)->once();

			$worker->shouldReceive('insertNode')->with($node)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->insertNodeAsRoot($node);
	}

	public function testInsertNodeAsFirstChild()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,insertNode,createGap]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$childNode  = $this->getMockNode();
		$parentNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $childNode, $parentNode)
		{
			$parentNode->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);
			$parentNode->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

			$worker->shouldReceive('createGap')->with(2, 2, 1)->once();

			$childNode->shouldReceive('setAttribute')->with('lft', 2)->once();
			$childNode->shouldReceive('setAttribute')->with('rgt', 3)->once();
			$childNode->shouldReceive('setAttribute')->with('tree', 1)->once();

			$worker->shouldReceive('insertNode')->with($childNode)->once();

			$parentNode->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(4);
			$parentNode->shouldReceive('setAttribute')->with('rgt', 6)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->insertNodeAsFirstChild($childNode, $parentNode);
	}

	public function testInsertNodeAsLastChild()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,insertNode,createGap]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$childNode  = $this->getMockNode();
		$parentNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $childNode, $parentNode)
		{
			$parentNode->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(4);
			$parentNode->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

			$worker->shouldReceive('createGap')->with(4, 2, 1)->once();

			$childNode->shouldReceive('setAttribute')->with('lft', 4)->once();
			$childNode->shouldReceive('setAttribute')->with('rgt', 5)->once();
			$childNode->shouldReceive('setAttribute')->with('tree', 1)->once();

			$worker->shouldReceive('insertNode')->with($childNode)->once();

			$parentNode->shouldReceive('setAttribute')->with('rgt', 6)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->insertNodeAsLastChild($childNode, $parentNode);
	}

	public function testInsertNodeAsPreviousSibling()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,insertNode,createGap]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$siblingNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $node, $siblingNode)
		{
			$siblingNode->shouldReceive('getAttribute')->with('lft')->once()->andReturn(2);
			$siblingNode->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

			$worker->shouldReceive('createGap')->with(2, 2, 1)->once();

			$node->shouldReceive('setAttribute')->with('lft', 2)->once();
			$node->shouldReceive('setAttribute')->with('rgt', 3)->once();
			$node->shouldReceive('setAttribute')->with('tree', 1)->once();

			$worker->shouldReceive('insertNode')->with($node)->once();

			$siblingNode->shouldReceive('setAttribute')->with('lft', 4)->once();
			$siblingNode->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(3);
			$siblingNode->shouldReceive('setAttribute')->with('rgt', 5)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->insertNodeAsPreviousSibling($node, $siblingNode);
	}

	public function testInsertNodeAsNextSibling()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,insertNode,createGap]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$siblingNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $node, $siblingNode)
		{
			$siblingNode->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(4);
			$siblingNode->shouldReceive('getAttribute')->with('tree')->once()->andReturn(1);

			$worker->shouldReceive('createGap')->with(5, 2, 1)->once();

			$node->shouldReceive('setAttribute')->with('lft', 5)->once();
			$node->shouldReceive('setAttribute')->with('rgt', 6)->once();
			$node->shouldReceive('setAttribute')->with('tree', 1)->once();

			$worker->shouldReceive('insertNode')->with($node)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->insertNodeAsNextSibling($node, $siblingNode);
	}

	public function testMoveNodeAsFirstChild()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,slideNodeOutOfTree,hydrateNode,slideNodeInTree]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$childNode  = $this->getMockNode();
		$parentNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $childNode, $parentNode)
		{
			$worker->shouldReceive('slideNodeOutOfTree')->with($childNode)->once();
			$worker->shouldReceive('hydrateNode')->with($parentNode)->twice();

			$parentNode->shouldReceive('getAttribute')->with('lft')->once()->andReturn(1);
			$worker->shouldReceive('slideNodeInTree')->with($childNode, 2)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->moveNodeAsFirstChild($childNode, $parentNode);
	}

	public function testMoveNodeAsLastChild()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,slideNodeOutOfTree,hydrateNode,slideNodeInTree]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$childNode  = $this->getMockNode();
		$parentNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $childNode, $parentNode)
		{
			$worker->shouldReceive('slideNodeOutOfTree')->with($childNode)->once();
			$worker->shouldReceive('hydrateNode')->with($parentNode)->twice();

			$parentNode->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(3);
			$worker->shouldReceive('slideNodeInTree')->with($childNode, 3)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->moveNodeAsLastChild($childNode, $parentNode);
	}

	public function testMoveNodeAsPreviousSibling()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,slideNodeOutOfTree,hydrateNode,slideNodeInTree]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$siblingNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $node, $siblingNode)
		{
			$worker->shouldReceive('slideNodeOutOfTree')->with($node)->once();
			$worker->shouldReceive('hydrateNode')->with($siblingNode)->twice();

			$siblingNode->shouldReceive('getAttribute')->with('lft')->once()->andReturn(3);
			$worker->shouldReceive('slideNodeInTree')->with($node, 3)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->moveNodeAsPreviousSibling($node, $siblingNode);
	}

	public function testMoveNodeAsNextSibling()
	{
		$worker = m::mock('Cartalyst\NestedSets\Workers\IlluminateWorker[dynamicQuery,slideNodeOutOfTree,hydrateNode,slideNodeInTree]');
		$worker->__construct($connection = $this->getMockConnection(), $node = $this->getMockNode());

		$siblingNode = $this->getMockNode();

		$worker->shouldReceive('dynamicQuery')->with(m::on(function($callback) use ($worker, $connection, $node, $siblingNode)
		{
			$worker->shouldReceive('slideNodeOutOfTree')->with($node)->once();
			$worker->shouldReceive('hydrateNode')->with($siblingNode)->twice();

			$siblingNode->shouldReceive('getAttribute')->with('rgt')->once()->andReturn(3);
			$worker->shouldReceive('slideNodeInTree')->with($node, 4)->once();

			$callback($connection);

			return true;
		}), true)->once();

		$worker->moveNodeAsNextSibling($node, $siblingNode);
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

		$node->shouldReceive('getKeyName')->andReturn('id');
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
