<?php namespace Cartalyst\NestedSets\Workers;
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

use Cartalyst\NestedSets\Nodes\NodeInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;

class IlluminateWorker {

	/**
	 * The database connection instance.
	 *
	 * @var Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The base node which the worker uses
	 * to get information (such as reserved
	 * attributes, table name, primary key
	 * names etc).
	 *
	 * @var Cartalyst\NestedSets\Nodes\NodeInterface
	 */
	protected $baseNode;

	/**
	 * Create a Illuminate worker instance.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @return void
	 */
	public function __construct(Connection $connection, NodeInterface $baseNode)
	{
		$this->connection = $connection;
		$this->baseNode   = $baseNode;
	}

	/**
	 * Creates a gap in a tree, starting from the
	 * left limit with the given size (the size can
	 * be negative).
	 *
	 * @param  int  $left
	 * @param  int  $size
	 * @param  int  $tree
	 * @return void
	 */
	public function createGap($left, $size, $tree)
	{
		if ($size === 0)
		{
			throw new \InvalidArgumentException("Cannot create a gap in tree [$tree] starting from [$left] with a size of [0].");
		}

		$attributes = $this->getReservedAttributes();

		$this
			->connection->table($this->getTable())
			->where($attributes['left'], '>=', $left)
			->where($attributes['tree'], '=', $tree)
			->update(array(
				$attributes['left'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['left']),
					$size
				)),
			));

		$this
			->connection->table($this->getTable())
			->where($attributes['right'], '>=', $left)
			->where($attributes['tree'], '=', $tree)
			->update(array(
				$attributes['right'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['right']),
					$size
				)),
			));
	}

	/**
	 * Alias to create a negative gap.
	 *
	 * @param  int  $start
	 * @param  int  $size
	 * @param  int  $tree
	 * @return void
	 */
	public function removeGap($start, $size, $tree)
	{
		if ($size < 0)
		{
			throw new \InvalidArgumentException("Cannot provide a negative size of [$size] remove a gap. Instead, provide the positive size.");
		}

		return $this->createGap($start, $size * -1, $tree);
	}

	/**
	 * Slides a node out of the tree hierarchy (so it's
	 * right limit sits on '0'; it will not render in any
	 * hierarchical data).
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @return void
	 */
	public function slideNodeOutOfTree(NodeInterface $node)
	{
		$attributes = $this->getReservedAttributes();
		$size       = $this->getNodeSize($node);
		$delta      = 0 - $node->getAttribute($attributes['right']);

		// There are two steps to this method. We are firstly going
		// to adjust our node and every child so that our right limit
		// is 0, which removes the node from the hierarchical tree.
		$this
			->connection->table($this->getTable())
			->where($attributes['left'], '>=', $node->getAttribute($attributes['left']))
			->where($attributes['right'], '<=', $node->getAttribute($attributes['right']))
			->where($attributes['tree'], '=', $node->getAttribute($attributes['tree']))
			->update(array(
				$attributes['left'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['left']),
					$delta
				)),
				$attributes['right'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['right']),
					$delta
				)),
			));

		// Now, we will close the gap created by shifting the node
		$this->removeGap($node->getAttribute($attributes['left']), $size + 1, $node->getAttribute($attributes['tree']));

		// We will calculate and update the node's properties now
		// so that the person does not have to re-hydrate them from
		// the database, as that will add overhead.
		$node->setAttribute($attributes['left'], $node->getAttribute($attributes['left']) + $delta);
		$node->setAttribute($attributes['right'], $node->getAttribute($attributes['right']) + $delta);
	}

	/**
	 * Slides a node back into it's tree so it can be used
	 * in hierarchical data. This is the reverse of sliding out
	 * of a tree and can be used to reposition a node.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  int  $left
	 * @return void
	 */
	public function slideNodeInTree(NodeInterface $node, $left)
	{
		$attributes = $this->getReservedAttributes();
		$size       = $this->getNodeSize($node);
		$delta      = $size + $left;

		// Reversing the proces of sliding out of a tree, we will
		// now create a gap for our node to enter at.
		$this->createGap($left, $size + 1, $node->getAttribute($attributes['tree']));

		// We will now adjust the left and right limits of our node and
		// all it's children to be within the hierachical data in the
		// gap we just created above.
		$this
			->connection->table($this->getTable())
			->where($attributes['left'], '>=', 0 - $size)
			->where($attributes['right'], '<=', 0)
			->where($attributes['tree'], '=', $node->getAttribute($attributes['tree']))
			->update(array(
				$attributes['left'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['left']),
					$delta
				)),
				$attributes['right'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['right']),
					$delta
				)),
			));

		// Like sliding out of a tree, we will now update the node's
		// attributes so they don't have to be hydrated.
		$node->setAttribute($attributes['left'], $node->getAttribute($attributes['left']) + $delta);
		$node->setAttribute($attributes['right'], $node->getAttribute($attributes['right']) + $delta);
	}

	public function getBaseNode()
	{
		return $this->baseNode;
	}

	public function getTable()
	{
		return $this->baseNode->getTable();
	}

	/**
	 * Get the reserved attributes.
	 *
	 * @return array
	 */
	public function getReservedAttributes()
	{
		return $this->baseNode->getReservedAttributes();
	}

	/**
	 * Get the name of a reserved attribute.
	 *
	 * @param  string  $key
	 * @return string
	 */
	public function getReservedAttribute($key)
	{
		return $this->baseNode->getReservedAttribute($key);
	}

	public function getNodeSize(NodeInterface $node)
	{
		$node->getAttribute($node->getReservedAttribute('right')) - $node->getAttribute($node->getReservedAttribute('left'));
	}

}
