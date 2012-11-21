<?php

namespace Nesty;

use Closure;

/**
 * Nesty workers must implement this
 * Foreman interface. We'll allow
 * third party workers to exist and run
 * on their own database connections.
 */
interface Foreman
{
	/**
	 * Returns all nodes, in a flat array.
	 *
	 * @param   int  $tree
	 * @return  array
	 */
	public function allFlat($tree);

	/**
	 * Returns all root nodes, in a flat array.
	 *
	 * @param   int  $tree
	 * @return  array
	 */
	public function allRoot($tree);

	/**
	 * Finds all leaf nodes, in a flat array.
	 * Leaf nodes are nodes which do not have
	 * any children.
	 *
	 * @param   int  $tree
	 * @return  array
	 */
	public function allLeafNodes($tree);

	/**
	 * Finds the path of a node by the given key.
	 * the path is the path and all of it's parents
	 * up to the root item.
	 *
	 * @param   int|string  $key
	 * @param   int  $tree
	 * @return  array
	 */
	public function path($key, $tree);

	/**
	 * Returns the depth of a node in a tree, where
	 * 0 is a root node, 1 is a root node's direct
	 * children and so on.
	 *
	 * @param   int|string  $key
	 * @param   int  $tree
	 * @return  int
	 */
	public function depth($key, $tree);

	/**
	 * Returns the relative depth of a node in a tree,
	 * relative to the parent provided. The parent
	 * must in fact be a parent in the path of this
	 * item otherwise we cannot find the relative
	 * depth.
	 *
	 * @param   int|string  $key
	 * @param   int|string  $parentKey
	 * @param   int  $tree
	 * @return  int
	 */
	public function relativeDepth($key, $parentKey, $tree);

	/**
	 * Returns a tree for the given node. If the depth
	 * is 0, we return all children. If the depth is
	 * 1 or more, that is how many levels of children
	 * nodes we recurse through.
	 *
	 * @param   int|string  $key
	 * @param   int  $tree
	 * @param   int  $depth
	 */
	public function tree($key, $tree, $depth = 0);

	/**
	 * Maps a tree to the database. We update each items'
	 * values as well if they're provided. This can be used
	 * to create a whole new tree structure or simply to re-order
	 * a tree.
	 *
	 * @param   array  $nodes
	 * @param   Closure  $beforePersist
	 * @return  array
	 */
	public function mapTree(array $nodes, Closure $beforePersist);
}