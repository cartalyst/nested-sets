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

interface WorkerInterface {

	/**
	 * Returns all nodes, in a flat array.
	 *
	 * @param  int  $tree
	 * @return array
	 */
	public function allFlat($tree);

	/**
	 * Returns all root nodes, in a flat array.
	 *
	 * @return array
	 */
	public function allRoot();

	/**
	 * Finds all leaf nodes, in a flat array.
	 * Leaf nodes are nodes which do not have
	 * any children.
	 *
	 * @param  int  $tree
	 * @return array
	 */
	public function allLeafNodes($tree);

	/**
	 * Finds the path of a node by the given key.
	 * the path is the path and all of it's parents
	 * up to the root item.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  int  $tree
	 * @return array
	 */
	public function path(NodeInterface $node, $tree);

	/**
	 * Returns the depth of a node in a tree, where
	 * 0 is a root node, 1 is a root node's direct
	 * children and so on.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  int  $tree
	 * @return int
	 */
	public function depth(NodeInterface $node, $tree);

	/**
	 * Returns the relative depth of a node in a tree,
	 * relative to the parent provided. The parent
	 * must in fact be a parent in the path of this
	 * item otherwise we cannot find the relative
	 * depth.
	 *
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $node
	 * @param  Cartalyst\NestedSets\Nodes\NodeInterface  $parentNode
	 * @param  int  $tree
	 * @return int
	 */
	public function relativeDepth(NodeInterface $node, NodeInterface $parentNode, $tree);

}
