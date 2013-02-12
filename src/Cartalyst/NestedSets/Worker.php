<?php namespace Cartalyst\NestedSets;
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

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Events\Dispatcher as EventDispatcher;

// @todo, add timestamp support
class Worker implements Foreman {

	/**
	 * The connection name for the worker.
	 *
	 * @var string
	 */
	public $connection;

	/**
	 * The table associated with the worker.
	 *
	 * @var string
	 */
	public $table;

	/**
	 * The primary key for the worker.
	 *
	 * @var string
	 */
	public $primaryKey = 'id';

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = true;

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
	public $reservedAttributes = array(
		'left'  => 'lft',
		'right' => 'rgt',
		'tree'  => 'tree_id',
	);

	/**
	 * Create a new Nested Sets Worker instance.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @param  string  $table
	 * @param  string  $primaryKey
	 * @param  bool    $incrementing
	 * @param  bool    $timestamps
	 * @param  array   $reservedAttributes
	 * @return
	 */
	public function __construct(Connection $connection, $table, $primaryKey = null, $incrementing = null, $timestamps = null, array $reservedAttributes = array())
	{
		// Required parameters for a worker
		// be instantiated.
		$this->connection = $connection;
		$this->table      = $table;

		// Optional parameters
		if ($primaryKey !== null)
		{
			$this->primaryKey = $primaryKey;
		}

		if ($incrementing !== null)
		{
			$this->incrementing = $incrementing;
		}

		if ($timestamps !== null)
		{
			$this->timestamps = $timestamps;
		}

		if ( ! empty($reservedAttributes))
		{
			$this->reservedAttributes = $reservedAttributes;
		}
	}

	/**
	 * Returns all nodes, in a flat array.
	 *
	 * @param  int  $tree
	 * @return array
	 */
	public function allFlat($tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns all root nodes, in a flat array.
	 *
	 * @return array
	 */
	public function allRoot()
	{
		$rootNodes = array();

		$databaseItems = $this->connection->table($this->table)
		    ->where($this->reservedAttributes['left'], 1)
		    ->get();

		foreach ($databaseItems as $item)
		{
			$node = new Node;
			foreach ($item as $key => $value)
			{
				$node->{$key} = $value;
			}
			$nodes[] = $node;
		}

		return $nodes;
	}

	/**
	 * Finds all leaf nodes, in a flat array.
	 * Leaf nodes are nodes which do not have
	 * any children.
	 *
	 * @param  int  $tree
	 * @return array
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
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @return array
	 */
	public function path($primaryKey, $tree)
	{
		$query = $this->connection->table("{$this->table} as node");

		$query->select('parent.*');

		$query->join(
			"{$this->table} as parent",
			new Expression("`node`.`{$this->reservedAttributes['left']}`"),
			'between',
			new Expression(

				// "AND" has to be capital, otherwise the grammar
				// class removes it
				"`parent`.`{$this->reservedAttributes['left']}` AND `parent`.`{$this->reservedAttributes['right']}`"
			)
		);

		$query->where("node.{$this->primaryKey}", $primaryKey);

		$query->orderBy("node.{$this->reservedAttributes['left']}");

		$nodes = array();

		foreach ($query->get() as $item)
		{
			$node = new Node;
			foreach ($item as $key => $value)
			{
				$node->{$key} = $value;
			}
			$nodes[] = $node;
		}

		return $nodes;
	}

	/**
	 * Returns the depth of a node in a tree, where
	 * 0 is a root node, 1 is a root node's direct
	 * children and so on.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @return int
	 */
	public function depth($primaryKey, $tree)
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
	 * @param  int|string  $primaryKey
	 * @param  int|string  $parentPrimaryKey
	 * @param  int  $tree
	 * @return int
	 */
	public function relativeDepth($primaryKey, $parentPrimaryKey, $tree)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns all children for the given node in a flat
	 * array. If the depth is 1 or more, that is how many
	 * levels of children we recurse through to put into
	 * the flat array.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @param  int  $depth
	 * @return array
	 */
	public function childrenNodes($primaryKey, $tree, $depth = 0)
	{
		throw new \RuntimeException("Implement me!");
	}

	/**
	 * Returns a tree for the given node. If the depth
	 * is 0, we return all children. If the depth is
	 * 1 or more, that is how many levels of children
	 * nodes we recurse through.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @param  int  $depth
	 * @return array
	 */
	public function tree($primaryKey, $tree, $depth = 0)
	{
		$grammar = $this->connection->getQueryGrammar();
		$query   = $this->connection->table("{$this->table} as node");
		$me      = $this;

		// Build up our select component
		$query->select(array(
			new Expression('`node`.*'),
			new Expression("(count(`parent`.`{$this->primaryKey}`) - (`sub_tree`.`depth` + 1)) AS `depth`"),
		));

		// Do an implicit join to create our
		// parent component
		$query->join(
			"{$this->table} as parent",
			new Expression("`node`.`{$this->reservedAttributes['left']}`"),
			'between',
			new Expression(

				// "AND" has to be capital, otherwise the grammar
				// class removes it
				"`parent`.`{$this->reservedAttributes['left']}` AND `parent`.`{$this->reservedAttributes['right']}`"
			)
		);

		// And the same thing with the sub parent
		$query->join(
			"{$this->table} as sub_parent",
			new Expression("`node`.`{$this->reservedAttributes['left']}`"),
			'between',
			new Expression(

				// "AND" has to be capital, otherwise the grammar
				// class removes it
				"`sub_parent`.`{$this->reservedAttributes['left']}` AND `sub_parent`.`{$this->reservedAttributes['right']}`"
			)
		);

		// Create a query to select the sub-tree
		// component of each node. We initialize this
		// here so that we can take its' bindings and
		// merge them in.
		$subTreeQuery = $me->connection->table("{$this->table} as node");

		// Now, in a closure we'll build up the sub query
		$query->join('sub_tree', function($join) use ($me, $grammar, $subTreeQuery, $primaryKey, $tree)
		{
			// Build up our select component
			$subTreeQuery->select(array(
				new Expression("`node`.`{$me->primaryKey}`"),
				new Expression("(count(`parent`.`{$me->primaryKey}`) - 1) as `depth`"),
			));

			// Do an implicit join to create our
			// parent component
			$subTreeQuery->join(
				"{$me->table} as parent",
				new Expression("`node`.`{$me->reservedAttributes['left']}`"),
				'between',
				new Expression(

					// "AND" has to be capital, otherwise the grammar
					// class removes it
					"`parent`.`{$me->reservedAttributes['left']}` AND `parent`.`{$me->reservedAttributes['right']}`"
				)
			);

			// Constrain the key to the key passed for the
			// top of the trees
			$subTreeQuery->where(
				new Expression("`node`.`{$me->primaryKey}`"),
				$primaryKey
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
			$whereParam1 = new Expression("`parent`.`{$me->reservedAttributes['left']}`");
			$whereParam2 = new Expression("`parent`.`{$me->reservedAttributes['right']}`");
			$subTreeQuery
				->where(
					new Expression("`node`.`{$me->reservedAttributes['left']}`"),
					'>=',
					$whereParam1
				)
				->where(
					new Expression("`node`.`{$me->reservedAttributes['left']}`"),
					'<=',
					$whereParam2
				);

			// This should be a safeguard against the query
			// builder bug above.
			// @todo, remove this when the bug is fixed
			$bindings = $subTreeQuery->getBindings();
			if (end($bindings) == $whereParam2)
			{
				array_pop($bindings);
			}
			if (end($bindings) == $whereParam1)
			{
				array_pop($bindings);
			}
			$subTreeQuery->setBindings($bindings);

			// Always match up to our tree as we
			// support multiple trees
			$subTreeQuery
				->where(
					new Expression("`node`.`{$me->reservedAttributes['tree']}`"),
					$tree
				)
				->where(
					new Expression("`parent`.`{$me->reservedAttributes['tree']}`"),
					$tree
				);

			// Group by the id, as we're returning
			// multiple records
			$subTreeQuery->groupBy(
				new Expression("`node`.`{$me->primaryKey}`")
			);

			// Order by the left limit, this will preserve the items
			// are in their correct sort order
			$subTreeQuery->orderBy(
				new Expression("`node`.`{$me->reservedAttributes['left']}`")
			);

			// Set the join table to our new sub-query
			$join->table = new Expression("({$subTreeQuery->toSql()}) as `{$join->table}`");

			// Set the "on" clause
			$join->on(
				new Expression("`sub_parent`.`{$me->primaryKey}`"),
				'=',
				new Expression("`sub_tree`.`{$me->primaryKey}`")
			);
		});

		// We need to append the bindings into our own
		// $query->mergeBindings($subTreeQuery);
		$newBindings = array();
		foreach ($query->getBindings() as $binding)
		{
			$newBindings[] = $binding;
		}
		foreach ($subTreeQuery->getBindings() as $binding)
		{
			$newBindings[] = $binding;
		}
		$query->setBindings($newBindings);

		// Always match up to our tree as we
		// support multiple trees
		$query
			->where(
				new Expression("`node`.`{$this->reservedAttributes['tree']}`"),
				$tree
			)
			->where(
				new Expression("`parent`.`{$this->reservedAttributes['tree']}`"),
				$tree
			)
			->where(
				new Expression("`sub_parent`.`{$this->reservedAttributes['tree']}`"),
				$tree
			);

		// Group by the id, as we're returning
		// multiple records
		$query->groupBy(
			new Expression("`node`.`{$me->primaryKey}`")
		);

		// Setup the limit
		if ($depth)
		{
			$query->having('depth', '<=', intval($depth));
		}

		// Order by the left limit, this will preserve the items
		// are in their correct sort order
		$query->orderBy(
			new Expression("`node`.`{$this->reservedAttributes['left']}`")
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
	 * @param  Node   $parent
	 * @param  array  $nodes
	 * @param  bool  $transaction
	 * @return array
	 */
	public function mapTree(Node $parent, array $nodes, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $parent, $nodes)
		{
			// Let's find all existing keys for the given parent. This
			// will allow us to compare what we've mapped with what was
			// there and delete any orphaned items!
			$query = $connection->table($me->table);
			$existingKeys = $query
			    ->where(
			    	$me->reservedAttributes['left'],
			    	'>',
			    	$parent->{$me->reservedAttributes['left']}
			    )
			    ->where(
			    	$me->reservedAttributes['right'],
			    	'<',
			    	$parent->{$me->reservedAttributes['right']}
			    )
			    ->get(array($me->primaryKey));
			if ($existingKeys)
			{
				$existingKeys = array_map(function($primaryKey) use ($me)
				{
					return $primaryKey->{$me->primaryKey};
				}, $existingKeys);
			}

			// Now, we'll loop through all of our nodes and
			// recursively map them
			foreach ($nodes as $node)
			{
				$this->recursivelyMapTree($node, $parent);
			}

			// Great, if we got this far, we can look
			// at the keys that were mapped without an
			// Exception thrown and compare them with
			// the existing keys.
			foreach (array_diff($existingKeys, $me->extractKeysFromNodesTree($nodes)) as $key)
			{
				// Let's create a node object from the record in
				// the databse as we're going to manipulate it
				$databaseItem = $connection->table($me->table)
				    ->where($me->primaryKey, $key)
				    ->first();

				$node = new Node;

				// $_key is used to no interfere with $key
				foreach ($databaseItem as $_key => $value)
				{
					$node->{$_key} = $value;
				}

				// Now, we'll slide it out of the tree and
				// delete it.
				$me->slideNodeOutsideTree($node);

				// And delete the record in the database
				$deleted = $connection->table($this->table)
				    ->where($me->primaryKey, $key)
				    ->delete();
			}
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	protected function extractKeysFromNodesTree(array &$nodes = array())
	{
		$keys = array();

		foreach ($nodes as &$node)
		{
			if ($node->children)
			{
				$keys = array_merge($keys, $this->extractKeysFromNodesTree($node->children));
			}

			$keys[] = $node->{$this->primaryKey};
		}

		return $keys;
	}

	/**
	 * Makes a new node a root node.
	 *
	 * @param  Node  $node
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsRoot(Node $node, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node)
		{
			$node->{$me->reservedAttributes['left']} = 1;
			$node->{$me->reservedAttributes['right']} = 2;

			$query = $connection->table($me->table);
			$node->{$me->reservedAttributes['tree']} = $query->max($me->reservedAttributes['tree']) + 1;

			$query = $connection->table($me->table);

			if ($me->incrementing)
			{
				$node->{$me->primaryKey} = $query->insertGetId($node->toArray());;
			}
			else
			{
				$query->insert($node->toArray());
			}
		};

		if ($transaction === true)
		{
			// Run the update commands within a database
			// transaction, so that worst-case, the database
			// rolls back
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Inserts the given node as the first child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsFirstChild(Node $node, Node $parent, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $parent)
		{
			// Make a gap for us
			$this->gap(
				$parent->{$me->reservedAttributes['left']} + 1,
				2,
				$node->{$this->reservedAttributes['tree']}
			);

			// Update node
			$node->{$me->reservedAttributes['left']}  = $parent->{$me->reservedAttributes['left']} + 1;
			$node->{$me->reservedAttributes['right']} = $parent->{$me->reservedAttributes['left']} + 2;

			// Now, we're going to update our node's
			// left and right limits, our parent node's
			// left and right limits (so the objects are)
			// up to date and insert it in the database
			$query = $connection->table($me->table);

			if ($me->incrementing)
			{
				$node->{$me->primaryKey} = $query->insertGetId($node->toArray());;
			}
			else
			{
				$query->insert($node->toArray());
			}

			// Of course, we manipulate the local parent
			$parent->{$me->reservedAttributes['right']} += 2;
		};

		if ($transaction === true)
		{
			// Run the update commands within a database
			// transaction, so that worst-case, the database
			// rolls back
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Inserts the given node as the last child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsLastChild(Node $node, Node $parent, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $parent)
		{
			// Make a gap for us
			$this->gap(
				$parent->{$me->reservedAttributes['right']},
				2,
				$node->{$this->reservedAttributes['tree']}
			);

			// Update node
			$node->{$me->reservedAttributes['left']}  = $parent->{$me->reservedAttributes['right']};
			$node->{$me->reservedAttributes['right']} = $parent->{$me->reservedAttributes['right']} + 1;

			// Now, we're going to update our node's
			// left and right limits, our parent node's
			// left and right limits (so the objects are)
			// up to date and insert it in the database
			$query = $connection->table($me->table);

			if ($me->incrementing)
			{
				$node->{$me->primaryKey} = $query->insertGetId($node->toArray());;
			}
			else
			{
				$query->insert($node->toArray());
			}

			// Of course, we manipulate the local parent
			$parent->{$me->reservedAttributes['right']} += 2;
		};

		if ($transaction === true)
		{
			// Run the update commands within a database
			// transaction, so that worst-case, the database
			// rolls back
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Inserts the given node as the previous sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsPreviousSibling(Node $node, Node $sibling, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $sibling)
		{
			// Make a gap for us
			$this->gap(
				$sibling->{$me->reservedAttributes['left']},
				2,
				$node->{$this->reservedAttributes['tree']}
			);

			// Update node
			$node->{$me->reservedAttributes['left']}  = $sibling->{$me->reservedAttributes['left']};
			$node->{$me->reservedAttributes['right']} = $sibling->{$me->reservedAttributes['left']} + 1;

			// And the sibling node, it's moved two to the right
			$sibling->{$me->reservedAttributes['left']} += 2;
			$sibling->{$me->reservedAttributes['right']} += 2;

			// Now, we're going to update our node's
			// left and right limits, our sibling node's
			// left and right limits (so the objects are)
			// up to date and insert it in the database
			$query = $connection->table($me->table);

			if ($me->incrementing)
			{
				$node->{$me->primaryKey} = $query->insertGetId($node->toArray());;
			}
			else
			{
				$query->insert($node->toArray());
			}
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Inserts the given node as the next sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsNextSibling(Node $node, Node $sibling, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $sibling)
		{
			// Make a gap for us
			$this->gap(
				$sibling->{$me->reservedAttributes['right']} + 1,
				2,
				$node->{$this->reservedAttributes['tree']}
			);

			// Update node
			$node->{$me->reservedAttributes['left']}  = $sibling->{$me->reservedAttributes['right']} + 1;
			$node->{$me->reservedAttributes['right']} = $sibling->{$me->reservedAttributes['right']} + 2;

			// Now, we're going to update our node's
			// left and right limits, our sibling node's
			// left and right limits (so the objects are)
			// up to date and insert it in the database
			$query = $connection->table($me->table);

			if ($me->incrementing)
			{
				$node->{$me->primaryKey} = $query->insertGetId($node->toArray());;
			}
			else
			{
				$query->insert($node->toArray());
			}
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Moves the given node as the first child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsFirstChild(Node $node, Node $parent, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $parent)
		{
			// Slide it out of the tree
			$me->slideNodeOutsideTree($node);

			// Now, let's re-query the database for our
			// parent item.
			$parentUpdated = $connection
			    ->table($me->table)
			    ->select(array_values($this->reservedAttributes))
			    ->where($me->primaryKey, $parent->{$me->primaryKey})
			    ->first();

			if ($parentUpdated == null)
			{
				throw new \RuntimeException("Cannot find parent node [{$parent->{$me->primaryKey}}] in [{$me->table}].");
			}

			// Update our parent object's attributes
			foreach ($this->reservedAttributes as $attribute)
			{
				$parent->{$attribute} = $parentUpdated->{$attribute};
			}

			// Now we've updated the parent, we'll use
			// it's new left to slide the node back into
			// the tree.
			$this->slideNodeInTree(
				$node,
				$parent->{$me->reservedAttributes['left']} + 1
			);
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Moves the given node as the last child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsLastChild(Node $node, Node $parent, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $parent)
		{
			// Slide it out of the tree
			$me->slideNodeOutsideTree($node);

			// Now, let's re-query the database for our
			// parent item.
			$parentUpdated = $connection
			    ->table($me->table)
			    ->select(array_values($this->reservedAttributes))
			    ->where($me->primaryKey, $parent->{$me->primaryKey})
			    ->first();

			if ($parentUpdated == null)
			{
				throw new \RuntimeException("Cannot find parent node [{$parent->{$me->primaryKey}}] in [{$me->table}].");
			}

			// Update our parent object's attributes
			foreach ($this->reservedAttributes as $attribute)
			{
				$parent->{$attribute} = $parentUpdated->{$attribute};
			}

			// Now we've updated the parent, we'll use
			// it's new left to slide the node back into
			// the tree.
			$this->slideNodeInTree(
				$node,
				$parent->{$me->reservedAttributes['right']}
			);
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Moves the given node as the previous sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsPreviousSibling(Node $node, Node $sibling, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $sibling)
		{
			// Slide it out of the tree
			$me->slideNodeOutsideTree($node);

			// Now, let's re-query the database for our
			// sibling item.
			$siblingUpdated = $connection
			    ->table($me->table)
			    ->select(array_values($this->reservedAttributes))
			    ->where($me->primaryKey, $sibling->{$me->primaryKey})
			    ->first();

			if ($siblingUpdated == null)
			{
				throw new \RuntimeException("Cannot find sibling node [{$sibling->{$me->primaryKey}}] in [{$me->table}].");
			}

			// Update our sibling object's attributes
			foreach ($this->reservedAttributes as $attribute)
			{
				$sibling->{$attribute} = $siblingUpdated->{$attribute};
			}

			// Now we've updated the sibling, we'll use
			// it's new left to slide the node back into
			// the tree.
			$this->slideNodeInTree(
				$node,
				$sibling->{$me->reservedAttributes['left']}
			);

			// Update the node, slide it
			$nodeSize = $node->{$me->reservedAttributes['right']} - $node->{$me->reservedAttributes['left']};

			// Update the sibling
			// @todo, probably force a reload of the sibling. That's
			// because the node may have been a child of the sibling before
			// meaning that the sibling will become smaller.
			$sibling->{$me->reservedAttributes['left']} += $nodeSize + 1;
			$sibling->{$me->reservedAttributes['right']} += $nodeSize + 1;
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Moves the given node as the next sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsNextSibling(Node $node, Node $sibling, $transaction = true)
	{
		$me = $this;

		$callback = function($connection) use ($me, $node, $sibling)
		{
			// Slide it out of the tree
			$me->slideNodeOutsideTree($node);

			// Now, let's re-query the database for our
			// sibling item.
			$siblingUpdated = $connection
			    ->table($me->table)
			    ->select(array_values($this->reservedAttributes))
			    ->where($me->primaryKey, $sibling->{$me->primaryKey})
			    ->first();

			if ($siblingUpdated == null)
			{
				throw new \RuntimeException("Cannot find sibling node [{$sibling->{$me->primaryKey}}] in [{$me->table}].");
			}

			// Update our sibling object's attributes
			foreach ($this->reservedAttributes as $attribute)
			{
				$sibling->{$attribute} = $siblingUpdated->{$attribute};
			}

			// Now we've updated the sibling, we'll use
			// it's new left to slide the node back into
			// the tree.
			$this->slideNodeInTree(
				$node,
				$sibling->{$me->reservedAttributes['right']} + 1
			);
		};

		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	/**
	 * Grabs a node, and adjusts it (and it's children
	 * in the database) so they sit outside the hierarchy
	 * of the tree.
	 *
	 * @param  Node  $node
	 * @return void
	 */
	protected function slideNodeOutsideTree(Node $node)
	{
		// Let's grab the size of the node
		$nodeSize = $node->{$this->reservedAttributes['right']} - $node->{$this->reservedAttributes['left']};

		// Change in position when shifted
		$delta = 0 - $node->{$this->reservedAttributes['right']};

		// Let's push our node into negative numbers, essentially
		// fully removing it from the tree.
		$query = $this->connection->table($this->table);
		$query
			->where(
				$this->reservedAttributes['left'],
				'>=',
				$node->{$this->reservedAttributes['left']}
			)
			->where(
				$this->reservedAttributes['right'],
				'<=',
				$node->{$this->reservedAttributes['right']}
			)
			->where(
				$this->reservedAttributes['tree'],
				$node->{$this->reservedAttributes['tree']}
			)
			->update(array(
				$this->reservedAttributes['left'] => new Expression(sprintf(

					// We just use negative and abs() so
					// our SQL makes a little more sense.
					'`%s` - %d',
					$this->reservedAttributes['left'],
					abs($delta)
				)),
				$this->reservedAttributes['right'] => new Expression(sprintf(
					'`%s` - %d',
					$this->reservedAttributes['right'],
					abs($delta)
				))
			));

		// Remove the gap left by the node we've removed
		$this->gap($node->{$this->reservedAttributes['left']}, -($nodeSize + 1), $node->{$this->reservedAttributes['tree']});

		// Update our node object
		$node->{$this->reservedAttributes['left']}  += $delta;
		$node->{$this->reservedAttributes['right']} += $delta;
	}

	/**
	 * Slides a node back into the tree structure, positioning
	 * its left limits at the left limits provided.
	 *
	 * @param  Node  $node
	 * @return void
	 */
	protected function slideNodeInTree(Node $node, $left)
	{
		// Grab our node size
		$nodeSize = $node->{$this->reservedAttributes['right']} - $node->{$this->reservedAttributes['left']};

		// Let's make a gap in the tree
		// for the size of our node plus 1
		$this->gap(
			$left,
			$nodeSize + 1,
			$node->{$this->reservedAttributes['tree']}
		);

		// We have a gap, so let's adjust our left / right
		// attributes for everybody inside this node
		$query = $this->connection->table($this->table);
		$query
			->where(
				$this->reservedAttributes['left'],
				'>=',
				0 - $nodeSize
			)
			->where(
				$this->reservedAttributes['left'],
				'<=',
				0
			)
			->where(
				$this->reservedAttributes['tree'],
				$node->{$this->reservedAttributes['tree']}
			)
			->update(array(
				$this->reservedAttributes['left'] => new Expression(sprintf(
					'`%s` + %d',
					$this->reservedAttributes['left'],
					$nodeSize + $left
				)),
				$this->reservedAttributes['right'] => new Expression(sprintf(
					'`%s` + %d',
					$this->reservedAttributes['right'],
					$nodeSize + $left
				))
			));

		// Update the node object
		$node->{$this->reservedAttributes['left']}  += $nodeSize + $left;
		$node->{$this->reservedAttributes['right']} += $nodeSize + $left;
	}

	/**
	 * Creates a gap in the tree, starting at a given position,
	 * for a certain size.
	 *
	 * @param  int   $left
	 * @param  int   $size
	 * @param  int   $tree
	 * @return void
	 */
	protected function gap($left, $size, $tree)
	{
		$query = $this->connection->table($this->table);
		$query
			->where(
				$this->reservedAttributes['left'],
				'>=',
				$left
			)
			->where(
				$this->reservedAttributes['tree'],
				$tree
			)
			->update(array(
				$this->reservedAttributes['left'] => new Expression(sprintf(

					// Just keep the SQL tidy
					'`%s` %s %d',
					$this->reservedAttributes['left'],
					($size >= 0) ? '+' : '-',
					abs($size)
				))
			));

		$query = $this->connection->table($this->table);
		$query
			->where(
				$this->reservedAttributes['right'],
				'>=',
				$left
			)
			->where(
				$this->reservedAttributes['tree'],
				$tree
			)
			->update(array(
				$this->reservedAttributes['right'] => new Expression(sprintf(

					// Just keep the SQL tidy
					'`%s` %s %d',
					$this->reservedAttributes['right'],
					($size >= 0) ? '+' : '-',
					abs($size)
				))
			));
	}

	/**
	 * Recursively maps a tree to a given parent.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @return void
	 */
	protected function recursivelyMapTree(Node $node, Node $parent)
	{
		// Firstly, we'll check if our node exists
		$existing = $this->connection->table($this->table)
		    ->where($this->primaryKey, $node->{$this->primaryKey})
		    ->first();

		if ($existing)
		{
			foreach ($this->reservedAttributes as $attribute)
			{
				$node->{$attribute} = $existing->{$attribute};
			}

			$this->moveNodeAsLastChild($node, $parent, false);
		}
		else
		{
			$this->insertNodeAsLastChild($node, $parent, false);
		}

		// Great, let's do one more save now to add
		// in the rest of our data to the database
		$this->connection->table($this->table)
		    ->where($this->primaryKey, $node->{$this->primaryKey})
		    ->update(array_diff_key($node->toArray(), array_flip($this->reservedAttributes)));

		// Recursive!
		if ($node->children)
		{
			foreach ($node->children as $child)
			{
				$this->recursivelyMapTree($child, $node);
			}
		}
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
				// Assigning the root node
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
