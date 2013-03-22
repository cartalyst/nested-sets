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

class NodeStub extends Cartalyst\Support\Attributable implements Cartalyst\NestedSets\Nodes\NodeInterface {

	protected $children = array();

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
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

	/**
	 * Sets the child in the children array at
	 * the given index.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $child
	 * @param  int  $index
	 * @return void
	 */
	public function setChildAtIndex(Cartalyst\NestedSets\Nodes\NodeInterface $child, $index)
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
		return (isset($this->children[$index])) ? $this->children[$index] : null;
	}

	/**
	 * Get the table associated with the node.
	 *
	 * @return string
	 */
	public function getTable()
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

	/**
	 * Get the primary key for the node.
	 *
	 * @return string
	 */
	public function getKeyName()
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

	/**
	 * Get the value indicating whether the IDs are incrementing.
	 *
	 * @return bool
	 */
	public function getIncrementing()
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
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
	 * @return array
	 */
	public function setAttributes(array $attributes)
	{
		return parent::setAttributes($attributes);
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
		parent::setAttribute($key, $value);
	}

	/**
	 * Get the reserved attributes.
	 *
	 * @return array
	 */
	public function getReservedAttributes()
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

	/**
	 * Get the name of a reserved attribute.
	 *
	 * @param  string  $key
	 * @return string
	 */
	public function getReservedAttribute($key)
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

	/**
	 * Finds all nodes in a flat array.
	 *
	 * @return array
	 */
	public function findAll()
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

	/**
	 * Creates a new instance of this node.
	 *
	 * @return Cartalyst\NestedSets\Nodes\NodeInterface
	 */
	public function createNode()
	{
		throw new BadMethodCallException('Stub method '.__METHOD__.' not implemented.');
	}

}
