<?php namespace Cartalyst\Nesty;
/**
 * Part of the Platform application.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Platform
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Closure;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

abstract class Model extends EloquentModel
{
	/**
	 * Worker instance. The worker is the
	 * class that does the magic on the
	 * database.
	 *
	 * @var Nesty\Worker
	 */
	protected $worker;

	/**
	 * Array of attributes reserved for the
	 * worker. These attributes cannot be set
	 * publically, only internally and shouldn't
	 * really be set outside this class.
	 *
	 * @var array
	 */
	protected $nestyAttributes = array(

		// The left column limit
		'left'  => 'lft',

		// The rigth column limit
		'right' => 'rgt',

		// The tree we are on (as we
		// support multiple trees).
		'tree'  => 'tree',
	);

	/**
	 * Special reserved property for the parent
	 * of the model.
	 *
	 * @var Nesty\model
	 */
	protected $parent;

	/**
	 * Special array of children for the model.
	 *
	 * @var array
	 */
	protected $children = array();

	/**
	 * The formats which we can dump
	 * the model in.
	 *
	 * @var array
	 */
	protected $dumpFormats = array(
		'array', 'ul', 'ol', 'json',
		// 'serialized', 'php',
	);

	/**
	 * Create a new Eloquent model instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = array())
	{
		$this->worker = new Worker(
			$this->getConnection(),
			$this->table,
			$this->key,
			$this->timestamps,
			$this->nestyAttributes
		);

		return parent::__construct($attributes);
	}

	/**
	 * Hydrates the children for this model. The depth
	 * provided usually won't be altered, however you
	 * may wish to tweak the query by limiting children
	 * returned if you aren't going to use them again.
	 *
	 * @param  int  $depth
	 * @return void
	 */
	public function hydrateChildren($depth = 0)
	{
		// Reset our children
		$this->children = array();

		// Grab the tree from the worker
		$tree = $this->worker->tree(
			$this->{$this->key},
			$this->{$this->nestyAttributes['tree']},
			$depth
		);

		// If we got an array back, the table has been
		// corrupted.
		if (is_array($tree)) {
			$count = count($tree);
			throw new \OutOfBoundsException("Invalid tree provided to hydrate children. Tree should be an object, array with [{$count}] items provided. Database hierarchy has been compromised.");
		}

		// Hydrate the children
		foreach ($tree->children as $child) {
			$this->hydrateChildNodeRecursively($this, $child);
		}
	}

	/**
	 * Returns the children for the model.
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
	 * @param  array|Illuminate\Database\Eloquent\Collection  $collection
	 * @return void
	 */
	public function setChildren($children)
	{
		if ( ! is_array($children) or ! $children instanceof EloquentCollection) {
			throw new \UnexpectedValueException("Invalid children type.");
		}

		$this->children = array();

		// Set the children
		foreach ($children as $child) {
			$this->children[] = $child;
		}
	}

	/**
	 * Dumps either this model or it's
	 * children as a particular format.
	 *
	 * There are several formats to choose from:
	 *
	 * 1. Array
	 * 2. Unordered List
	 * 3. Ordered List
	 * 4. JSON string
	 *
	 * The attribute parameter may either be a string (we'll
	 * use that model's attribute which matches the attribute),
	 * or a Closure which point we'll pass through the model
	 * object and require a string back.
	 *
	 * @param   string  $format
	 * @param   string|Closure  $attribute
	 * @return  mixed
	 */
	public function dumpChildrenAs($format, $attribute)
	{
		// Validate format
		if ( ! in_array($format, $this->dumpFormats)) {
			throw new \UnexpectedValueException("Format [$format] is not a valid format to dump Nesty model.");
		}

		// The array of items to dump
		$toDump = array();

		// Loop through children
		foreach ($this->children as $child) {

			// If we've been given a Closure to
			// output the attribute
			if ($attribute instanceof Closure) {
				$output = $attribute($child);

			// Otherwise, we've been given a string
			} else {
				$output = $child->{$attribute};
			}

			// If there are children, we'll go recursive
			// and grab an array back
			if ($child->children) {
				$toDump[$output] = $child->dumpChildrenAs('array', $attribute);
			} else {
				$toDump[] = $output;
			}
		}

		// Differenciate output based on format
		return $this->{'dumpArrayAs'.ucfirst($format)}($toDump);
	}

	/**
	 * Returns the Worker object for the model.
	 *
	 * @return Nesty\Worker
	 */
	public function getWorker()
	{
		return $this->worker;
	}

	/**
	 * Sets the Worker object for the model.
	 *
	 * @param  Nesty\Worker  $worker
	 * @return void
	 */
	public function setWorker(Worker $worker)
	{
		$this->worker = $worker;
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
		// Check that we are allowed to set the attribute.
		if (in_array($key, $this->nestyAttributes)) {
			throw new \InvalidArgumentException("Key [$key] is reserved by Nesty and cannot be set manually.");
		}

		return parent::setAttribute($key, $value);
	}

	/**
	 * Returns a Node representation of this Nesty model.
	 *
	 * @return  Nesty\Node
	 */
	public function toNode()
	{
		return new Node($this->toArray());
	}

	/**
	 * Hydrates this model from a Node.
	 *
	 * @param  Nesty\Node  $node
	 * @return void
	 */
	public function fromNode(Node $node)
	{
		// Grab the attributes
		$attributes = $node->toArray();

		// Because we're the same class as the
		// model, we can access it's protected
		// properties. Let's get in now and set
		// the protected keys
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function makeRoot()
	{
		// Make our object into a new node
		$node = $this->toNode();

		// Move to a root object
		$this->worker->{(($this->exists) ? 'move' : 'insert').'NodeAsRoot'}($node);

		// Update our attributes from the node
		$this->fromNode($node);

		if ( ! $this->exists) {
			$this->exists = 1;
		}
	}

	/**
	 * Makes this model the first child of the parent
	 *
	 * @param   Nesty\Model  $parent
	 * @return  void
	 */
	public function makeFirstChildOf(Model $parent)
	{
		// Let's match up trees
		if ( ! $this->exists) {
			$this->attributes[$this->nestyAttributes['tree']] = $parent->{$this->nestyAttributes['tree']};
		} elseif ($this->{$this->nestyAttributes['tree']} != $parent->{$this->nestyAttributes['tree']}) {
			throw new \UnexpectedValueException("Nesty model's tree [{$this->{$this->nestyAttributes['tree']}}] does not match the parent tree [{$parent->{$this->nestyAttributes['tree']}}].");
		}

		// Grab node representations of
		// our model
		$parentNode = $parent->toNode();
		$node       = $this->toNode();

		// Call our worker to
		// insert / move nodes
		$this->worker->{(($this->exists) ? 'move' : 'insert').'NodeAsFirstChild'}(
			$node,
			$parentNode
		);

		// Synchronise node back in
		$parent->fromNode($parentNode);
		$this->fromNode($node);

		// Make sure we are set to exist
		if ( ! $this->exists) {
			$this->exists = 1;
		}
	}

	/**
	 * Makes this model the last child of the parent
	 *
	 * @param   Nesty\Model  $parent
	 * @return  void
	 */
	public function makeLastChildOf(Model $parent)
	{
		// Let's match up trees
		if ( ! $this->exists) {
			$this->attributes[$this->nestyAttributes['tree']] = $parent->{$this->nestyAttributes['tree']};
		} elseif ($this->{$this->nestyAttributes['tree']} != $parent->{$this->nestyAttributes['tree']}) {
			throw new \UnexpectedValueException("Nesty model's tree [{$this->{$this->nestyAttributes['tree']}}] does not match the parent tree [{$parent->{$this->nestyAttributes['tree']}}].");
		}

		// Grab node representations of
		// our model
		$parentNode = $parent->toNode();
		$node       = $this->toNode();

		// Call our worker to
		// insert / move nodes
		$this->worker->{(($this->exists) ? 'move' : 'insert').'NodeAsLastChild'}(
			$node,
			$parentNode
		);

		// Synchronise node back in
		$parent->fromNode($parentNode);
		$this->fromNode($node);

		// Make sure we are set to exist
		if ( ! $this->exists) {
			$this->exists = 1;
		}
	}

	public function makePreviousSiblingOf(Model $sibling)
	{
		// Let's match up trees
		if ( ! $this->exists) {
			$this->attributes[$this->nestyAttributes['tree']] = $sibling->{$this->nestyAttributes['tree']};
		} elseif ($this->{$this->nestyAttributes['tree']} != $sibling->{$this->nestyAttributes['tree']}) {
			throw new \UnexpectedValueException("Nesty model's tree [{$this->{$this->nestyAttributes['tree']}}] does not match the sibling tree [{$sibling->{$this->nestyAttributes['tree']}}].");
		}

		// Grab node representations of
		// our model
		$siblingNode = $sibling->toNode();
		$node       = $this->toNode();

		// Call our worker to
		// insert / move nodes
		$this->worker->{(($this->exists) ? 'move' : 'insert').'NodeAsPreviousSibling'}(
			$node,
			$siblingNode
		);

		// Synchronise node back in
		$sibling->fromNode($siblingNode);
		$this->fromNode($node);

		// Make sure we are set to exist
		if ( ! $this->exists) {
			$this->exists = 1;
		}
	}

	public function makeNextSiblingOf(Model $sibling)
	{
		// Let's match up trees
		if ( ! $this->exists) {
			$this->attributes[$this->nestyAttributes['tree']] = $sibling->{$this->nestyAttributes['tree']};
		} elseif ($this->{$this->nestyAttributes['tree']} != $sibling->{$this->nestyAttributes['tree']}) {
			throw new \UnexpectedValueException("Nesty model's tree [{$this->{$this->nestyAttributes['tree']}}] does not match the sibling tree [{$sibling->{$this->nestyAttributes['tree']}}].");
		}

		// Grab node representations of
		// our model
		$siblingNode = $sibling->toNode();
		$node       = $this->toNode();

		// Call our worker to
		// insert / move nodes
		$this->worker->{(($this->exists) ? 'move' : 'insert').'NodeAsNextSibling'}(
			$node,
			$siblingNode
		);

		// Synchronise node back in
		$sibling->fromNode($siblingNode);
		$this->fromNode($node);

		// Make sure we are set to exist
		if ( ! $this->exists) {
			$this->exists = 1;
		}
	}

	/**
	 * Takes a Node object and hydrates it's parents' "children"
	 * property.
	 *
	 * @param  Nesty\Model  $parent
	 * @param  Nesty\Node   $node
	 * @return void
	 */
	protected function hydrateChildNodeRecursively(Model $parent, Node $node)
	{
		// Create a new model instance
		$model = new static();
		$model->fromNode($node);

		// Set the parent object
		$model->parent = $parent;

		// Add the child object
		$parent->children[] = $model;

		// Recursive, baby!
		foreach ($node->children as $child) {
			$this->hydrateChildNodeRecursively($model, $child);
		}
	}

	/**
	 * Dumps an array as an array. Ironic right?
	 *
	 * @param  array  $toDump
	 * @return array
	 */
	protected function dumpArrayAsArray(array $toDump)
	{
		return $toDump;
	}

	/**
	 * Dumps an array as an unordered HTML list.
	 *
	 * @param  array  $toDump
	 * @return string
	 */
	protected function dumpArrayAsUl(array $toDump)
	{
		return $this->dumpArrayAsList('ul', $toDump);
	}

	/**
	 * Dumps an array as an ordered HTML list.
	 *
	 * @param  array  $toDump
	 * @return string
	 */
	protected function dumpArrayAsOl(array $toDump)
	{
		return $this->dumpArrayAsList('ol', $toDump);
	}

	/**
	 * Dumps an array as a HTML list.
	 *
	 * @param  string  $type
	 * @param  array   $toDump
	 * @return string
	 */
	protected function dumpArrayAsList($type, array $toDump)
	{
		$html = '';

		if (count($toDump) == 0) {
			return $html;
		}

		foreach ($toDump as $key => $value)
		{
			// If the value is an array, we will recurse the function so that we can
			// produce a nested list within the list being built. Of course, nested
			// lists may exist within nested lists, etc.
			if (is_array($value))
			{
				if (is_int($key))
				{
					$html .= $this->dumpArrayAsList($type, $value);
				}
				else
				{
					$html .= '<li>'.$key.$this->dumpArrayAsList($type, $value).'</li>';
				}
			}
			else
			{
				$html .= '<li>'.htmlentities($value).'</li>';
			}
		}

		return '<'.$type.'>'.$html.'</'.$type.'>';
	}

	/**
	 * Dumps an array as a JSON object / array
	 *
	 * @param  array  $toDump
	 * @return string
	 */
	protected function dumpArrayAsJson(array $toDump)
	{
		return json_encode($toDump);
	}

}