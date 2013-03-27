<?php namespace Cartalyst\NestedSets\Nodes;
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

use Cartalyst\NestedSets\Presenter;
use Illuminate\Database\Eloquent\Model;

class EloquentNode extends Model implements NodeInterface {

	/**
	 * Array of children associated with the model.
	 *
	 * @var array
	 */
	protected $children = array();

	/**
	 * Array of reserved attributes used by
	 * the node. These attributes cannot be
	 * set like normal attributes, they are
	 * reserved for the node and nested set
	 * workers to use.
	 *
	 * @var array
	 */
	protected $reservedAttributes = array(

		// The left column limit. "left" is a
		// reserved word in SQL databases so
		// we default to "lft" for compatiblity.
		'left'  => 'lft',

		// The right column limit. "right" is a
		// reserved word in SQL databases so
		// we default to "rgt" for compatiblity.
		'right' => 'rgt',

		// The tree that the node is on. This
		// package supports multiple trees within
		// the one database.
		'tree'  => 'tree',

		// Attribute used for the depth of the
		// node when creating hierarchical trees.
		// Note: This attribute should NOT exist
		// in your database, it's reserved for
		// processing only.
		'depth' => 'depth',
	);

	/**
	 * The worker class which the model uses.
	 *
	 * @var string
	 */
	protected $worker = 'Cartalyst\NestedSets\Workers\IlluminateWorker';

	/**
	 * The presenter instance.
	 *
	 * @var Cartalyst\Sentry\Presenter
	 */
	protected static $presenter;

	/**
	 * Returns the children for the node.
	 *
	 * @return array
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Sets the children for the model.
	 *
	 * @param  array  $children
	 * @return void
	 */
	public function setChildren(array $children)
	{
		$this->children = $children;
	}

	/**
	 * Clears the children for the model.
	 *
	 * @return void
	 */
	public function clearChildren()
	{
		$this->children = array();
	}

	/**
	 * Sets the child in the children array at
	 * the given index.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $child
	 * @param  int  $index
	 * @return void
	 */
	public function setChildAtIndex(NodeInterface $child, $index)
	{
		$this->children[$index] = $child;
	}

	/**
	 * Returns the child at the given index. If
	 * the index does not exist, we return "null"
	 *
	 * @param  int  $index
	 * @return Cartalyst\NestedSets\Nodes\NodeInterface  $child
	 */
	public function getChildAtIndex($index)
	{
		return isset($this->children[$index]) ? $this->children[$index] : null;
	}

	/**
	 * Get the table associated with the node.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return parent::getTable();
	}

	/**
	 * Get the primary key for the node.
	 *
	 * @return string
	 */
	public function getKeyName()
	{
		return parent::getKeyName();
	}

	/**
	 * Get the value indicating whether the IDs are incrementing.
	 *
	 * @return bool
	 */
	public function getIncrementing()
	{
		return parent::getIncrementing();
	}

	/**
	 * Get all of the current attributes on the node.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return parent::getAttributes();
	}

	/**
	 * Set all of the current attributes on the node.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function setAttributes(array $attributes)
	{
		return parent::setRawAttributes($attributes);
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		return parent::getAttribute($key);
	}

	/**
	 * Set a given attribute on the model.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function setAttribute($key, $value)
	{
		return parent::setAttribute($key, $value);
	}

	/**
	 * Get the reserved attributes.
	 *
	 * @return array
	 */
	public function getReservedAttributes()
	{
		return $this->reservedAttributes;
	}

	/**
	 * Get the name of a reserved attribute.
	 *
	 * @param  string  $key
	 * @return string
	 */
	public function getReservedAttribute($key)
	{
		return $this->reservedAttributes[$key];
	}

	/**
	 * Finds all nodes in a flat array.
	 *
	 * @return array
	 */
	public function findAll()
	{
		return static::all()->all();
	}

	/**
	 * Creates a new instance of this node.
	 *
	 * @return Cartalyst\NestedSets\Nodes\NodeInterface
	 */
	public function createNode()
	{
		return $this->newInstance();
	}

	/**
	 * Refreshes the node's attributes from the database.
	 *
	 * @return void
	 */
	public function refresh()
	{
		$this->createWorker()->hydrateNode($this);
	}

	/**
	 * Returns if the model is a leaf node or not; whether
	 * it has children.
	 *
	 * @return bool
	 */
	public function isLeaf()
	{
		return $this->createWorker()->isLeaf($this);
	}

	/**
	 * Returns the path of the node.
	 *
	 * @return array
	 */
	public function getPath()
	{
		return $this->createWorker()->path($this);
	}

	/**
	 * Returns the cound of children for the model.
	 *
	 * @param  int  $depth
	 * @return int
	 */
	public function getChildrenCount($depth = 0)
	{
		return $this->createWorker()->childrenCount($this, $depth);
	}

	/**
	 * Queries the database for all children for the model.
	 * Optionally, a depth may be provided.
	 *
	 * @param   int  $depth
	 * @return  array
	 */
	public function findChildren($depth = 0)
	{
		return $this->children = $this->createWorker()->tree($this, $depth);
	}

	/**
	 * Makes the model a root node.
	 *
	 * @return void
	 */
	public function makeRoot()
	{
		// @todo Allow existing items to become new root items
		if ($this->exists)
		{
			throw new \RuntimeException("Currently cannot make existing node {$this->getKey()} a root item.");
		}

		$this->createWorker()->insertNodeAsRoot($this);
	}

	/**
	 * Makes the model the first child of the given parent.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\EloquentNode  $parent
	 * @return void
	 */
	public function makeFirstChildOf(EloquentNode $parent)
	{
		$method = $this->exists ? 'moveNodeAsFirstChild' : 'insertNodeAsFirstChild';
		$this->createWorker()->$method($this, $parent);
	}

	/**
	 * Makes the model the last child of the given parent.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\EloquentNode  $parent
	 * @return void
	 */
	public function makeLastChildOf(EloquentNode $parent)
	{
		$method = $this->exists ? 'moveNodeAsLastChild' : 'insertNodeAsLastChild';
		$this->createWorker()->$method($this, $parent);
	}

	/**
	 * Makes the model the previous sibling of the given sibling.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\EloquentNode  $sibling
	 * @return void
	 */
	public function makePreviousSiblingOf(EloquentNode $sibling)
	{
		$method = $this->exists ? 'moveNodeAsPreviousSibling' : 'insertNodeAsPreviousSibling';
		$this->createWorker()->$method($this, $sibling);
	}

	/**
	 * Makes the model the next sibling of the given sibling.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\EloquentNode  $sibling
	 * @return void
	 */
	public function makeNextSiblingOf(EloquentNode $sibling)
	{
		$method = $this->exists ? 'moveNodeAsNextSibling' : 'insertNodeAsNextSibling';
		$this->createWorker()->$method($this, $sibling);
	}

	/**
	 * Maps a tree of either nodes, arrays of StdClass objects to
	 * the hierarchy array.
	 *
	 * @param  mixed  $nodes
	 * @return void
	 */
	public function mapTree($nodes)
	{
		$this->createWorker()->mapTree($this, $nodes);
	}

	/**
	 * Presents the node in the given format. If the attribute
	 * provided is a closure, we will call it, providing every
	 * single node recursively. You must return a string from
	 * your closure which will be used as the output for that
	 * node when presenting.
	 *
	 * @param  string  $format
	 * @param  string|Closure  $attribute
	 * @param  int  $depth
	 * @return mixed
	 */
	public function presentAs($format, $attribute, $depth = 0)
	{
		return static::$presenter->presentAs($this, $format, $attribute, $depth);
	}

	/**
	 * Presents the children of the given node in the given
	 * format. If the attribute provided is a closure, we will
	 * call it, providing every single node recursively. You
	 * must return a string from your closure which will be
	 * used as the output for that node when presenting.
	 *
	 * @param  string  $format
	 * @param  string|Closure  $attribute
	 * @param  int  $depth
	 * @return mixed
	 */
	public function presentChildrenAs($format, $attribute, $depth = 0)
	{
		return static::$presenter->presentChildrenAs($this, $format, $attribute, $depth);
	}

	/**
	 * Creates a worker instance for the model.
	 *
	 * @return Cartalyst\NestedSets\Workers\WorkerInterface
	 */
	public function createWorker()
	{
		$class = '\\'.ltrim($this->worker, '\\');

		return new $class($this->getConnection(), $this);
	}

	/**
	 * Sets the wroker to be used by the model.
	 *
	 * @param  string  $helper
	 * @return void
	 */
	public function setWorker($worker)
	{
		$this->worker = $worker;
	}

	/**
	 * Returns a collection of all root nodes.
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public static function allRoot()
	{
		$static = new static;
		$root   = $static->createWorker()->allRoot();

		return $static->newCollection($root);
	}

	/**
	 * Returns a collection of all leaf nodes.
	 *
	 * @param  ind  $tree
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public static function allLeaf($tree = null)
	{
		$static = new static;
		$leaf   = $static->createWorker()->allLeaf($tree);

		return $static->newCollection($leaf);
	}

	/**
	 * Sets the presenter to be used by all Eloquent nodes.
	 *
	 * @param  Cartalyst\NestedSets\Presenter
	 * @return void
	 */
	public static function setPresenter(Presenter $presenter)
	{
		static::$presenter = $presenter;
	}

	/**
	 * Gets the presenter used by all Eloquent nodes.
	 *
	 * @return Cartalyst\NestedSets\Presenter
	 */
	public static function getPresenter()
	{
		return static::presenter();
	}

	/**
	 * Unsets the presenter instance.
	 *
	 * @return void
	 */
	public static function unsetPresenter()
	{
		static::$presenter = null;
	}

}
