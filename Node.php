<?php

namespace Nesty;

/**
 * Object to represent a node on the Nesty system. Each node has
 * a couple of protected properties that should not be overridden.
 *
 * These are:
 *  - children
 *  - depth
 *
 */
class Node
{
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