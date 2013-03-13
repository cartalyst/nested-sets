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

interface NodeInterface {

	/**
	 * Returns the children for the node.
	 *
	 * @return array
	 */
	public function getChildren();

	/**
	 * Sets the children for the model.
	 *
	 * @param  array  $children
	 * @return void
	 */
	public function setChildren(array $children);

	/**
	 * Clears the children for the model.
	 *
	 * @return void
	 */
	public function clearChildren();

	/**
	 * Adds a new child to the node. Node, this
	 * does not actually set it in the database,
	 * it merely registers the instance. Used by
	 * workers when converting flat results to a
	 * tree.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $child
	 * @param  int  $index
	 * @return void
	 */
	public function setChildAtIndex(NodeInterface $child, $index);

	/**
	 * Adds a new child to the node. Node, this
	 * does not actually set it in the database,
	 * it merely registers the instance. Used by
	 * workers when converting flat results to a
	 * tree.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $child
	 * @param  int  $index
	 * @return void
	 */
	public function getChildAtIndex($index);

	/**
	 * Get the table associated with the node.
	 *
	 * @return string
	 */
	public function getTable();

	/**
	 * Get the primary key for the node.
	 *
	 * @return string
	 */
	public function getKeyName();

	/**
	 * Get the value indicating whether the IDs are incrementing.
	 *
	 * @return bool
	 */
	public function getIncrementing();

	/**
	 * Get all of the current attributes on the node.
	 *
	 * @return array
	 */
	public function getAttributes();

	/**
	 * Set all of the current attributes on the node.
	 *
	 * @return array
	 */
	public function setAttributes();

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key);

	/**
	 * Set a given attribute on the model.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function setAttribute($key, $value);

	/**
	 * Get the reserved attributes.
	 *
	 * @return array
	 */
	public function getReservedAttributes();

	/**
	 * Get the name of a reserved attribute.
	 *
	 * @param  string  $key
	 * @return string
	 */
	public function getReservedAttribute($key = null);

	/**
	 * Finds all nodes in a flat array.
	 *
	 * @return array
	 */
	public function findAll();

	/**
	 * Creates a new instance of this node.
	 *
	 * @return Cartalyst\NestedSets\Nodes\NodeInterface
	 */
	public function createNode();

}
