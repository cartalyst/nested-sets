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

/**
 * Nesty workers must implement this
 * Foreman interface. We'll allow
 * third party workers to exist and run
 * on their own database connections.
 */
interface Foreman {

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
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @return array
	 */
	public function path($primaryKey, $tree);

	/**
	 * Returns the depth of a node in a tree, where
	 * 0 is a root node, 1 is a root node's direct
	 * children and so on.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @return int
	 */
	public function depth($primaryKey, $tree);

	/**
	 * Returns the relative depth of a node in a tree,
	 * relative to the parent provided. The parent
	 * must in fact be a parent in the path of this
	 * item otherwise we cannot find the relative
	 * depth.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int|string  $parentPrimaryKey
	 * @param  int  $tree
	 * @return int
	 */
	public function relativeDepth($primaryKey, $parentPrimaryKey, $tree);

	/**
	 * Returns all children for the given node in a flat
	 * array. If the depth is 1 or more, that is how many
	 * levels of children we recurse through to put into
	 * the flat array.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @param  int  $depth
	 * @return array
	 */
	public function childrenNodes($primaryKey, $tree, $depth = 0);

	/**
	 * Returns a tree for the given node. If the depth
	 * is 0, we return all children. If the depth is
	 * 1 or more, that is how many levels of children
	 * nodes we recurse through.
	 *
	 * @param  int|string  $primaryKey
	 * @param  int  $tree
	 * @param  int  $depth
	 * @return array
	 */
	public function tree($primaryKey, $tree, $depth = 0);

	/**
	 * Maps a tree to the database. We update each items'
	 * values as well if they're provided. This can be used
	 * to create a whole new tree structure or simply to re-order
	 * a tree.
	 *
	 * @param  Node   $parent
	 * @param  array  $nodes
	 * @param  bool  $transaction
	 * @return array
	 */
	public function mapTree(Node $parent, array $nodes, $transaction = true);

	/**
	 * Makes a new node a root node.
	 *
	 * @param  Node  $node
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsRoot(Node $node, $transaction = true);

	/**
	 * Inserts the given node as the first child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsFirstChild(Node $node, Node $parent, $transaction = true);

	/**
	 * Inserts the given node as the last child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsLastChild(Node $node, Node $parent, $transaction = true);

	/**
	 * Inserts the given node as the previous sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsPreviousSibling(Node $node, Node $sibling, $transaction = true);

	/**
	 * Inserts the given node as the next sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function insertNodeAsNextSibling(Node $node, Node $sibling, $transaction = true);

	/**
	 * Moves the given node as the first child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsFirstChild(Node $node, Node $parent, $transaction = true);

	/**
	 * Moves the given node as the last child of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $parent
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsLastChild(Node $node, Node $parent, $transaction = true);

	/**
	 * Moves the given node as the previous sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsPreviousSibling(Node $node, Node $sibling, $transaction = true);

	/**
	 * Moves the given node as the next sibling of
	 * the parent node. Updates node attributes as well.
	 *
	 * @param  Node  $node
	 * @param  Node  $sibling
	 * @param  bool  $transaction
	 * @return void
	 */
	public function moveNodeAsNextSibling(Node $node, Node $sibling, $transaction = true);

	/**
	 * Grabs a node, and adjusts it (and it's children
	 * in the database) so they sit outside the hierarchy
	 * of the tree.
	 *
	 * @param  Node  $node
	 * @param  bool  $transaction
	 * @return void
	 */
	// public function slideNodeOutsideTree(Node $node, $transaction = true);

	/**
	 * Slides a node back into the tree structure, positioning
	 * its left limits at the left limits provided.
	 *
	 * @param  Node  $node
	 * @param  bool  $transaction
	 * @return void
	 */
	// public function slideNodeInTree(Node $node, $left, $transaction = true);

	/**
	 * Creates a gap in the tree, starting at a given position,
	 * for a certain size.
	 *
	 * @param  int   $left
	 * @param  int   $size
	 * @param  int   $tree
	 * @param  bool  $transaction
	 * @return void
	 */
	// public function gap($left, $size, $tree, $transaction = true);
}