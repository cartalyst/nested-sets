<?php

namespace Nesty;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Events\Dispatcher as EventDispatcher;

class Worker implements Foreman
{
	/**
	 * The connection name for the worker.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The table associated with the worker.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the worker.
	 *
	 * @var string
	 */
	protected $key = 'id';

	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * Array of attributes reserved for the
	 * worker. These attributes cannot be set
	 * publically, only internally and shouldn't
	 * really be set outside this class.
	 *
	 * @var array
	 */
	protected $nestyAttributes = array(
		'left'  => 'lft',
		'right' => 'rgt',
		'tree'  => 'tree_id',
	);

	/**
	 * Create a new Nesty Worker instance.
	 *
	 * @param   Illuminate\Database\Connection  $connection
	 * @param   string  $table
	 * @param   string  $key
	 * @param   bool    $timestamps
	 * @param   array   $nestyAtributes
	 * @return 
	 */
	public function __construct(Connection $connection, $table, $key = null, $timestamps = null, array $nestyAttributes = array())
	{
		// Required parameters for a Nesty worker to
		// be instantiated.
		$this->connection = $connection;
		$this->table      = $table;

		// Optional parameters
		if ($key !== null) {
			$this->key = $key;
		}
		if ($timestamps !== null) {
			$this->timestamps = $timestamps;
		}
		if ( ! empty($nestyAttributes)) {
			$this->nestyAttributes = $nestyAttributes;
		}
	}

	/**
	 * Returns all nodes, in a flat array.
	 *
	 * @param   int  $tree
	 * @return  array
	 */
	public function allFlat($tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns all root nodes, in a flat array.
	 *
	 * @param   int  $tree
	 * @return  array
	 */
	public function allRoot($tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Finds all leaf nodes, in a flat array.
	 * Leaf nodes are nodes which do not have
	 * any children.
	 *
	 * @param   int  $tree
	 * @return  array
	 */
	public function allLeafNodes($tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Finds the path of a node by the given key.
	 * the path is the path and all of it's parents
	 * up to the root item.
	 *
	 * @param   int|string  $key
	 * @param   int  $tree
	 * @return  array
	 */
	public function path($key, $tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns the depth of a node in a tree, where
	 * 0 is a root node, 1 is a root node's direct
	 * children and so on.
	 *
	 * @param   int|string  $key
	 * @param   int  $tree
	 * @return  int
	 */
	public function depth($key, $tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns the relative depth of a node in a tree,
	 * relative to the parent provided. The parent
	 * must in fact be a parent in the path of this
	 * item otherwise we cannot find the relative
	 * depth.
	 *
	 * @param   int|string  $key
	 * @param   int|string  $parentKey
	 * @param   int  $tree
	 * @return  int
	 */
	public function relativeDepth($key, $parentKey, $tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns a tree for the given node. If the depth
	 * is 0, we return all children. If the depth is
	 * 1 or more, that is how many levels of children
	 * nodes we recurse through.
	 *
	 * @param   int|string  $key
	 * @param   int  $tree
	 * @param   int  $depth
	 */
	public function tree($key, $tree, $depth = 0)
	{
		$grammar = $this->connection->getQueryGrammar();
		$query   = $this->connection->table("{$this->table} as node");
		$me      = $this;

		// Build up our select component
		$query->select(array(
			new Expression('`node`.*'),
			new Expression("(count(`parent`.`{$this->key}`) - (`sub_tree`.`depth` + 1)) AS `depth`"),
		));

		// $query->from("{$this->table} as node");

		// Do an implicit join to create our
		// parent component
		$query->join(
			"{$this->table} as parent",
			new Expression("`node`.`{$this->nestyAttributes['left']}`"),
			'between',
			new Expression(

				// "AND" has to be capital, otherwise the grammar
				// class removes it
				"`parent`.`{$this->nestyAttributes['left']}` AND `parent`.`{$this->nestyAttributes['right']}`"
			)
		);

		// And the same thing with the sub parent
		$query->join(
			"{$this->table} as sub_parent",
			new Expression("`node`.`{$this->nestyAttributes['left']}`"),
			'between',
			new Expression(

				// "AND" has to be capital, otherwise the grammar
				// class removes it
				"`sub_parent`.`{$this->nestyAttributes['left']}` AND `sub_parent`.`{$this->nestyAttributes['right']}`"
			)
		);

		// Create a query to select the sub-tree
		// component of each node. We initialize this
		// here so that we can take its' bindings and
		// merge them in.
		$subTreeQuery = $me->connection->table("{$this->table} as node");

		// Now, in a closure we'll build up the sub query
		$query->join('sub_tree', function($join) use ($me, $grammar, $subTreeQuery, $key, $tree) {

			// Build up our select component
			$subTreeQuery->select(array(
				new Expression("`node`.`{$me->key}`"),
				new Expression("(count(`parent`.`{$me->key}`) - 1) as `depth`"),
			));

			// Do an implicit join to create our
			// parent component
			$subTreeQuery->join(
				"{$me->table} as parent",
				new Expression("`node`.`{$me->nestyAttributes['left']}`"),
				'between',
				new Expression(

					// "AND" has to be capital, otherwise the grammar
					// class removes it
					"`parent`.`{$me->nestyAttributes['left']}` AND `parent`.`{$me->nestyAttributes['right']}`"
				)
			);

			// Constrain the key to the key passed for the
			// top of the trees
			$subTreeQuery->where(
				new Expression("`node`.`{$me->key}`"),
				'=',
				$key
			);

			// Larvel's query builder doesn't support
			// BETWEEN, so we'll do two WHERE queries.
			// This equates to the same thing.
			/**
			 * The query builder is assigning bindings to the Expression
			 * values but not substituting them in. See
			 * https://github.com/illuminate/database/issues/52
			 *
			 * @todo Check this and remove
			 */
			$whereParam1 = new Expression("`parent`.`{$me->nestyAttributes['left']}`");
			$whereParam2 = new Expression("`parent`.`{$me->nestyAttributes['right']}`");
			$subTreeQuery
				->where(
					new Expression("`node`.`{$me->nestyAttributes['left']}`"),
					'>=',
					$whereParam1
				)
				->where(
					new Expression("`node`.`{$me->nestyAttributes['left']}`"),
					'<=',
					$whereParam2
				);

			// This should be a safeguard against the query
			// builder bug above.
			// @todo, remove this when the bug is fixed
			$bindings = $subTreeQuery->getBindings();
			if (end($bindings) == $whereParam2) {
				array_pop($bindings);
			}
			if (end($bindings) == $whereParam1) {
				array_pop($bindings);
			}
			$subTreeQuery->setBindings($bindings);

			// Always match up to our tree as we
			// support multiple trees
			$subTreeQuery
				->where(
					new Expression("`node`.`{$me->nestyAttributes['tree']}`"),
					'=',
					$tree
				)
				->where(
					new Expression("`parent`.`{$me->nestyAttributes['tree']}`"),
					'=',
					$tree
				);

			// Group by the id, as we're returning
			// multiple records
			$subTreeQuery->groupBy(
				new Expression("`node`.`{$me->key}`")
			);

			// Order by the left limit, this will preserve the items
			// are in their correct sort order
			$subTreeQuery->orderBy(
				new Expression("`node`.`{$me->nestyAttributes['left']}`")
			);

			// Set the join table to our new sub-query
			$join->table = new Expression("({$subTreeQuery->toSql()}) as `{$join->table}`");

			// Set the "on" clause
			$join->on(
				new Expression("`sub_parent`.`{$me->key}`"),
				'=',
				new Expression("`sub_tree`.`{$me->key}`")
			);
		});

		// We need to append the bindings into our own
		// $query->mergeBindings($subTreeQuery);
		$newBindings = array();
		foreach ($query->getBindings() as $binding) {
			$newBindings[] = $binding;
		}
		foreach ($subTreeQuery->getBindings() as $binding) {
			$newBindings[] = $binding;
		}
		$query->setBindings($newBindings);

		// Always match up to our tree as we
		// support multiple trees
		$query
			->where(
				new Expression("`node`.`{$this->nestyAttributes['tree']}`"),
				'=',
				$tree
			)
			->where(
				new Expression("`parent`.`{$this->nestyAttributes['tree']}`"),
				'=',
				$tree
			)
			->where(
				new Expression("`sub_parent`.`{$this->nestyAttributes['tree']}`"),
				'=',
				$tree
			);

		// Group by the id, as we're returning
		// multiple records
		$query->groupBy(
			new Expression("`node`.`{$me->key}`")
		);

		// Setup the limit
		if ($depth) {
			$query->having('depth', '<=', intval($depth));
		}

		// Order by the left limit, this will preserve the items
		// are in their correct sort order
		$query->orderBy(
			new Expression("`node`.`{$this->nestyAttributes['left']}`")
		);

		// Return the results transformed into a tree.
		return $this->flatResultsToTree($query->get());
	}

	/**
	 * Maps a tree to the database. We update each items'
	 * values as well if they're provided. This can be used
	 * to create a whole new tree structure or simply to re-order
	 * a tree.
	 *
	 * @param   array  $nodes
	 * @param   Closure  $beforePersist
	 * @return  array
	 */
	public function mapTree(array $nodes, Closure $beforePersist)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Takes a flat array of results from the database
	 * and turns into a hierarchial tree of Node objects.
	 *
	 * Each result must contain the following key:
	 *  - depth
	 *
	 * The order of the results also determines the
	 * order of the tree.
	 *
	 * @param  array  $results
	 * @return array|Node  $tree
	 */
	protected function flatResultsToTree(array $results)
	{
		// Tree to return
		$tree = array();

		// Set up some vars used for
		// iterating
		$l     = 0;
		$stack = array();

		// Loop through the results
		foreach ($results as $result)
		{
			// Create a new node - be sure we
			// cast as an array because the
			// query builder (or probably at
			// the PDO level) returns a standard
			// class
			$node = new Node((array) $result);

			// Number of stack items
			$l = count($stack);

			// Check if we're dealing with different levels
			while ($l > 0 and $stack[$l - 1]->depth >= $node->depth)
			{
				array_pop($stack);
				$l--;
			}

			// Stack is empty (we are inspecting the root)
			if ($l == 0)
			{
				// Assigning the root nesty
				$i = count($tree);
				$tree[$i] = $node;
				$stack[] = &$tree[$i];
			}

			// Add node to parent
			else
			{
				$i = count($stack[$l - 1]->children);
				$stack[$l - 1]->children[$i] = $node;
				$stack[] = &$stack[$l - 1]->children[$i];
			}
		}

		// This function may return a tree
		// with an apex node, or have sibling nodes
		// at the top. If only one node, we won't want
		// to return all the items, just the one.
		return (count($tree) > 1) ? $tree : reset($tree);
	}
}