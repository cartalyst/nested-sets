<?php

namespace Nesty;

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
	 * 5. Serialised PHP array
	 * 6. PHP code - can be eval()'d.
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
	 * Takes a Node object and hydrates it's parents' "children"
	 * property.
	 *
	 * @param  Nesty\Model  $parent
	 * @param  Nesty\Node   $node
	 * @return void
	 */
	protected function hydrateChildNodeRecursively($parent, Node $node)
	{
		// Grab the attributes
		$attributes = $node->toArray();

		// Split up the attributes into
		// reserved and unreserved.
		$unreserved = array_diff_key($attributes, array_flip($parent->nestyAttributes));
		$reserved   = array_diff_key($attributes, $unreserved);

		// Create a new model instance
		$model = new static($unreserved);

		// Because we're the same class as the
		// model, we can access it's protected
		// properties. Let's get in now and set
		// the protected keys
		$model->attributes = array_merge($model->attributes, $reserved);

		// Set the parent object
		$model->parent = $parent;

		// Add the child object
		$parent->children[] = $model;

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