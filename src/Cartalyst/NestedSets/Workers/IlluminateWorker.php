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

		$result = $this
			->connection->table("$table as node")
			->join("$table as parent", "node.{$attributes['left']}", '>=', "parent.{$attributes['left']}")
			->where("node.{$attributes['left']}", '<=', "parent.{$attributes['right']}")
			->where("node.$keyName", '=', $node->getAttribute($keyName))
			->orderBy("node.{$attributes['left']}")
			->groupBy("node.{$attributes['left']}")
			->first(new Expression(sprintf(
				'(count(%s) - 1) as %s',
				$this->connection->getQueryGrammar()->wrap('parent.name'),
				$this->connection->getQueryGrammar()->wrap('depth')
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

}
