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

}
