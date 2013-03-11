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

		$this->connection->table($this->getTable())
			->where($attributes['left'], '>=', $left)
			->where($attributes['tree'], '=', $tree)
			->update(array(
				$attributes['left'] => new Expression(sprintf(
					'%s + %d',
					$this->connection->getQueryGrammar()->wrap($attributes['left']),
					$size
				)),
			));

		$this->connection->table($this->getTable())
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

}
