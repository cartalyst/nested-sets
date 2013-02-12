<?php namespace Cartalyst\NestedSets;
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

/**
 * Object to represent a node on the Nested Sets system. Each node has
 * a couple of protected properties that should not be overridden.
 *
 * These are:
 *  - children
 *  - depth
 *
 */
class Node {

	/**
	 * The node's attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Special array of children for the node.
	 *
	 * @var array
	 */
	public $children = array();

	/**
	 * The depth of the node when inside a
	 * tree.
	 *
	 * @var int
	 */
	public $depth;

	/**
	 * Create a new Node.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = array())
	{
		foreach ($attributes as $key => $value) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Set the array of Node attributes. No checking is done.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * Convert the Node instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->attributes;
	}

	/**
	 * Dynamically retrieve attributes on the Node.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->attributes[$key];
	}

	/**
	 * Dynamically set attributes on the Node.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	/**
	 * Determine if an attribute exists on the Node.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute on the Node.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

}
