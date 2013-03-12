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
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * This class does the MPTT magic which powers nested-sets.
 * A great resource to learn about MPTT can be found at
 * http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/
 */
class IlluminateWorker implements WorkerInterface {

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
	 * Returns all nodes, in a flat array.
	 *
	 * @param  int  $tree
	 * @return array
	 */
	public function allFlat($tree = null)
	{
		$all = $this->baseNode->findAll();

		// If no tree was supplied, we will return all items
		if ($tree === null) return $all;

		$me = $this;

		// If a tree was supplied, we will filter the items to
		// ensure the tree matches.
		return array_filter($all, function($node) use ($me, $tree)
		{
			return ($node->getAttribute($me->getReservedAttribute('tree')) == $tree);
		});
	}

	/**
	 * Returns all root nodes, in a flat array.
	 *
	 * @return array
	 */
	public function allRoot()
	{
		$me = $this;

		// Root items are those who's left limit is equal to "1".
		return array_filter($this->baseNode->findAll(), function($node) use ($me)
		{
			return ($node->getAttribute($me->getReservedAttribute('left')) == 1);
		});
	}

	/**
	 * Finds all leaf nodes, in a flat array.
	 * Leaf nodes are nodes which do not have
	 * any children.
	 *
	 * @param  int  $tree
	 * @return array
	 */
	public function allLeaf($tree = null)
	{
		$me = $this;

		// Leaf nodes are nodes with no children, therefore the
		// right limit will be one greater than the left limit.
		return array_filter($this->baseNode->findAll(), function($node) use ($me, $tree)
		{
			$right = $node->getAttribute($me->getReservedAttribute('right'));
			$left  = $node->getAttribute($me->getReservedAttribute('left'));
			$size  = $right - $left;

			// If we have no tree, simply check the size
			if ($tree === null) return $size == 1;

			// Otherwise, check our tree constraint matches as well.
			return ($size == 1 and $node->getAttribute($me->getReservedAttribute('tree')) == $tree);
		});
	}

	/**
	 * Finds the path of the given node. The path is
	 * the primary key of the node and all of it's
	 * parents up to the root item.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @return array
	 */
	public function path(NodeInterface $node)
	{
		$attributes = $this->getReservedAttributes();
		$table      = $this->getTable();
		$keyName    = $this->baseNode->getKeyName();

		// Note, joins currently don't support "between" operators
		// in the query builder, so we will satisfy half of the
		// "betweeen" in the join and the other half in a "where"
		// clause. This will allow us to use the query builder for
		// it's database agnostic compilation
		$results = $this
			->connection->table("$table as node")
			->join("$table as parent", "node.{$attributes['left']}", '>=', "parent.{$attributes['left']}")
			->where("node.{$attributes['left']}", '<=', "parent.{$attributes['right']}")
			->where("node.$keyName", '=', $node->getAttribute($keyName))
			->orderBy("node.{$attributes['left']}")
			->get("parent.$keyName");

		// Our results is an array of objects containing the key name
		// only. We will simplify this by simply returning the key
		// name so our array is a simple array of primatives.
		return array_map(function($result) use ($keyName)
		{
			return $result->$keyName;
		}, $results);
	}

	/**
	 * Returns the depth of a node in a tree, where
	 * 0 is a root node, 1 is a root node's direct
	 * child and so on.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @return int
	 */
	public function depth(NodeInterface $node)
	{
		$attributes = $this->getReservedAttributes();
		$table      = $this->getTable();
		$keyName    = $this->baseNode->getKeyName();
		$grammar    = $this->connection->getQueryGrammar();

		$result = $this
			->connection->table("$table as node")
			->join("$table as parent", "node.{$attributes['left']}", '>=', "parent.{$attributes['left']}")
			->where("node.{$attributes['left']}", '<=', "parent.{$attributes['right']}")
			->where("node.$keyName", '=', $node->getAttribute($keyName))
			->orderBy("node.{$attributes['left']}")
			->groupBy("node.{$attributes['left']}")
			->first(new Expression(sprintf(
				'(count(%s) - 1) as %s',
				$grammar->wrap("parent.$keyName"),
				$grammar->wrap('depth')
			)));

		return $result->depth;
	}

	/**
	 * Returns the relative depth of a node in a tree,
	 * relative to the parent provided. The parent
	 * must in fact be a parent in the path of this
	 * item otherwise we cannot find the relative
	 * depth.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $parentNode
	 * @return int
	 */
	public function relativeDepth(NodeInterface $node, NodeInterface $parentNode)
	{

	}

	/**
	 * Returns all children for the given node in a flat
	 * array. If the depth is 1 or more, that is how many
	 * levels of children we recurse through to put into
	 * the flat array.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  int  $depth
	 * @return array
	 */
	public function childrenNodes(NodeInterface $node, $depth = 0)
	{

	}

	/**
	 * Returns a tree for the given node. If the depth
	 * is 0, we return all children. If the depth is
	 * 1 or more, that is how many levels of children
	 * nodes we recurse through.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  int  $depth
	 * @return array
	 */
	public function tree(NodeInterface $node, $depth = 0)
	{
		$attributes = $this->getReservedAttributes();
		$table      = $this->getTable();
		$keyName    = $this->baseNode->getKeyName();
		$key        = $node->getAttribute($keyName);
		$tree       = $node->getAttribute($attributes['tree']);
		$grammar    = $this->connection->getQueryGrammar();

		// We will store a query builder object that we
		// use throughout the course of this method.
		$query = $this
			->connection->table("$table as node")
			->join("$table as parent", "node.{$attributes['left']}", '>=', "parent.{$attributes['left']}")
			->where("node.{$attributes['left']}", '<=', "parent.{$attributes['right']}")
			->join("$table as sub_parent", "node.{$attributes['left']}", '>=', "sub_parent.{$attributes['left']}")
			->where("node.{$attributes['left']}", '<=', "sub_parent.{$attributes['right']}");

		// Create a query to select the sub-tree
		// component of each node. We initialize this
		// here so that we can take its' bindings and
		// merge them in.
		$subQuery = $this->connection->table("$table as node");

		// We now build up the sub-tree component of the
		// query in a closure which is passed as the condition
		// an inner join for the main query.
		$me = $this;

		$query->join('sub_tree', function($join) use ($me, $node, $subQuery, $attributes, $table, $keyName, $key, $tree, $grammar)
		{
			$subQuery
				->select("node.$keyName", new Expression(sprintf(
					'(count(%s) - 1) as %s',
					$grammar->wrap("parent.$keyName"),
					$grammar->wrap('depth')
				)))
				->join("$table as parent", "node.{$attributes['left']}", '>=', "parent.{$attributes['left']}")
				->where("node.{$attributes['left']}", '<=', "parent.{$attributes['right']}")
				->where("node.$keyName", '=', $key)
				->whereBetween("node.{$attributes['left']}", array("parent.{$attributes['left']}", "parent.{$attributes['right']}"))
				->where("node.{$attributes['tree']}", '=', $tree)
				->where("parent.{$attributes['tree']}", '=', $tree)
				->orderBy("node.{$attributes['left']}")
				->groupBy("node.$keyName");

			// Configure the join from the SQL the query
			// builder generates.
			$join->table = new Expression(sprintf(
				'(%s) as %s',
				$subQuery->toSql(),
				$grammar->wrap($join->table)
			));

			$join->on("sub_parent.$keyName", '=', "sub_tree.$keyName");
		});

		// Now we have compiled the SQL for our sub query,
		// we need to merge it's bindings into our main query.
		$query->mergeBindings($subQuery);

		$query
			->where("node.{$attributes['tree']}", '=', $tree)
			->where("parent.{$attributes['tree']}", '=', $tree)
			->where("sub_parent.{$attributes['tree']}", '=', $tree)
			->orderBy("node.{$attributes['left']}")
			->groupBy("node.$keyName");

		// If we have a depth, we need to supply a "having"
		// clause to the query builder.
		if ($depth > 0)
		{
			$query->having('depth', '<=', $depth);
		}

		$results = $query->get("node.*", new Expression(sprintf(
			'(count(%s) - (%s + 1)) as %s',
			$grammar->wrap("parent.$keyName"),
			$grammar->wrap('sub_tree.depth'),
			$grammar->wrap('depth')
		)));
	}

	/**
	 * Maps a tree to the database. We update each items'
	 * values as well if they're provided. This can be used
	 * to create a whole new tree structure or simply to re-order
	 * a tree.
	 *
	 * @param  NodeInterface   $parent
	 * @param  array  $nodes
	 * @param  bool  $transaction
	 * @return array
	 */
	public function mapTree(NodeInterface $parent, array $nodes, $transaction = true)
	{

	}

	/**
	 * Makes a new node a root node.
	 *
	 * @param  NodeInterface  $node
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsRoot(NodeInterface $node, $transaction = true)
	{
		$table      = $this->getTable();
		$attributes = $this->getReservedAttributes();
		$me         = $this;

		$this->dynamicQuery(function($connection) use ($me, $node, $table, $attributes)
		{
			$query = $connection->table($table);

			$node->setAttribute($attributes['left'], 1);
			$node->setAttribute($attributes['right'], 2);
			$node->setAttribute($attributes['tree'], $query->max($attributes['tree']) + 1);

			$me->insertNode($node, $query);

		}, $transaction);
	}

	/**
	 * Inserts the given node as the first child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsFirstChild(NodeInterface $node, NodeInterface $parent, $transaction = true)
	{
		$table      = $this->getTable();
		$attributes = $this->getReservedAttributes();
		$me         = $this;

		$this->dynamicQuery(function($connection) use ($me, $node, $parent, $table, $attributes)
		{
			// Our left limit will be one greater than that of the parent
			// node, which will mean we are the first child.
			$left  = $parent->getAttribute($attributes['left']) + 1;
			$right = $left + 1;
			$tree  = $parent->getAttribute($attributes['tree']);

			$me->createGap($left, 2, $tree);

			// Update the node instance with our properties
			$node->setAttribute($attributes['left'], $left);
			$node->setAttribute($attributes['right'], $right);
			$node->setAttribute($attributes['tree'], $tree);

			$me->insertNode($node, $connection->table($table));

			// We will update the parent instance so that it's
			// limits are accurate and it can be used again.
			$parent->setAttribute($attributes['right'], $parent->getAttribute($attributes['right']) + 2);

		}, $transaction);
	}

	/**
	 * Inserts the given node as the last child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsLastChild(NodeInterface $node, NodeInterface $parent, $transaction = true)
	{
		$table      = $this->getTable();
		$attributes = $this->getReservedAttributes();
		$me         = $this;

		$this->dynamicQuery(function($connection) use ($me, $node, $parent, $table, $attributes)
		{
			// Our left limit will be the same as the (current) right limit
			// of the parent node, which will mean we are the last child.
			$left  = $parentRight = $parent->getAttribute($attributes['right']);
			$right = $left + 1;
			$tree  = $parent->getAttribute($attributes['tree']);

			$me->createGap($left, 2, $tree);

			// Update the node instance with our properties
			$node->setAttribute($attributes['left'], $left);
			$node->setAttribute($attributes['right'], $right);
			$node->setAttribute($attributes['tree'], $tree);

			$me->insertNode($node, $connection->table($table));

			// We will update the parent instance so that it's
			// limits are accurate and it can be used again.
			$parent->setAttribute($attributes['right'], $parentRight + 2);

		}, $transaction);
	}

	/**
	 * Inserts the given node as the previous sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsPreviousSibling(NodeInterface $node, NodeInterface $sibling, $transaction = true)
	{
		$table      = $this->getTable();
		$attributes = $this->getReservedAttributes();
		$me         = $this;

		$this->dynamicQuery(function($connection) use ($me, $node, $sibling, $table, $attributes)
		{
			// Our left limit will be the same as the (current) left limit
			// of the sibling node, which will mean we are the previous sibling.
			$left  = $siblingLeft = $sibling->getAttribute($attributes['left']);
			$right = $left + 1;
			$tree  = $sibling->getAttribute($attributes['tree']);

			$me->createGap($left, 2, $tree);

			// Update the node instance with our properties
			$node->setAttribute($attributes['left'], $left);
			$node->setAttribute($attributes['right'], $right);
			$node->setAttribute($attributes['tree'], $tree);

			$me->insertNode($node, $connection->table($table));

			// We will update the parent instance so that it's
			// limits are accurate and it can be used again.
			$sibling->setAttribute($attributes['left'], $siblingLeft + 2);
			$sibling->setAttribute($attributes['right'], $sibling->getAttribute($attributes['right']) + 2);

		}, $transaction);
	}

	/**
	 * Inserts the given node as the next sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsNextSibling(NodeInterface $node, NodeInterface $sibling, $transaction = true)
	{
		$table      = $this->getTable();
		$attributes = $this->getReservedAttributes();
		$me         = $this;

		$this->dynamicQuery(function($connection) use ($me, $node, $sibling, $table, $attributes)
		{
			// Our left limit will be one more than the (current) right limit
			// of the sibling node, which will mean we are the next sibling.
			// Additionally, because we sit to the right of the child, we do
			// not have to update the child's properties as none of our queries
			// will adjust the record it represents in the database.
			$left  = $sibling->getAttribute($attributes['right']) + 1;
			$right = $left + 1;
			$tree  = $sibling->getAttribute($attributes['tree']);

			$me->createGap($left, 2, $tree);

			// Update the node instance with our properties
			$node->setAttribute($attributes['left'], $left);
			$node->setAttribute($attributes['right'], $right);
			$node->setAttribute($attributes['tree'], $tree);

			$me->insertNode($node, $connection->table($table));

		}, $transaction);
	}

	/**
	 * Makes the given node a root node.
	 *
	 * @param  NodeInterface  $node
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsRoot(NodeInterface $node, $transaction = true)
	{

	}

	/**
	 * Moves the given node as the first child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsFirstChild(NodeInterface $node, NodeInterface $parent, $transaction = true)
	{

	}

	/**
	 * Moves the given node as the last child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsLastChild(NodeInterface $node, NodeInterface $parent, $transaction = true)
	{

	}

	/**
	 * Moves the given node as the previous sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsPreviousSibling(NodeInterface $node, NodeInterface $sibling, $transaction = true)
	{

	}

	/**
	 * Moves the given node as the next sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  NodeInterface  $node
	 * @param  NodeInterface  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsNextSibling(NodeInterface $node, NodeInterface $sibling, $transaction = true)
	{

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
		$grammar    = $this->connection->getQueryGrammar();

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
					$grammar->wrap($attributes['left']),
					$delta
				)),
				$attributes['right'] => new Expression(sprintf(
					'%s + %d',
					$grammar->wrap($attributes['right']),
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
		$grammar    = $this->connection->getQueryGrammar();

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
					$grammar->wrap($attributes['left']),
					$delta
				)),
				$attributes['right'] => new Expression(sprintf(
					'%s + %d',
					$grammar->wrap($attributes['right']),
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

	/**
	 * Calculate's the "size" of a node in the hierachical
	 * structure, based off it's left and right limits.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @return int
	 */
	public function getNodeSize(NodeInterface $node)
	{
		return $node->getAttribute($node->getReservedAttribute('right')) - $node->getAttribute($node->getReservedAttribute('left'));
	}

	/**
	 * Runs a query enclosed in a callback and wraps it in
	 * a databae transaction if required. The "creating", "updating"
	 * and "deleting" processes in MPTT require several queries (whereas
	 * "reading" only takes one query). It is good practice to wrap these
	 * queries in a transaction so that if just one fails, we can rollback
	 * tne entire transaction.
	 *
	 * @param  Closure  $callback
	 * @param  bool     $transaction
	 */
	public function dynamicQuery(Closure $callback, $transaction = true)
	{
		if ($transaction === true)
		{
			$this->connection->transaction($callback);
		}
		else
		{
			$callback($this->connection);
		}
	}

	public function insertNode(NodeInterface $node, QueryBuilder $query)
	{
		if ($node->getIncrementing())
		{
			$node->setAttribute($this->baseNode->getKeyName(), $query->insertGetId($node->getAttributes()));
		}
		else
		{
			$query->insert($node->getAttributes());
		}
	}

}
