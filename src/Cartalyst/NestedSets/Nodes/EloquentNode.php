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

use Illuminate\Database\Eloquent\Model

class EloquentNode extends Model implements NodeInterface {

	/**
	 * Array of reserved attributes used by
	 * the node. These attributes cannot be
	 * set like normal attributes, they are
	 * reserved for the node and nested set
	 * workers to use.
	 *
	 * @var array
	 */
	protected $reservedAttributes = array(

		// The left column limit. "left" is a
		// reserved word in SQL databases so
		// we default to "lft" for compatiblity.
		'left'  => 'lft',

		// The rigth column limit. "right" is a
		// reserved word in SQL databases so
		// we default to "rgt" for compatiblity.
		'right' => 'rgt',

		// The tree that the node is on. This
		// package supports multiple trees within
		// the one database.
		'tree'  => 'tree',
	);

}
