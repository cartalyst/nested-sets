# Usage

## Configure an Eloquent Node

We make the assumption that you have read the [Laravel 7 documentation](https://laravel.com/docs/7.0/database) on Eloquent models.

What do you need to do in addition? Not a whole lot:

Ensure that your class implements

	Cartalyst\NestedSets\Nodes\NodeInterface

Ensure that your class uses the following trait

	Cartalyst\NestedSets\Nodes\NodeTrait

Ensure you have the following columns in your database:

	lft, rgt, tree (all integers)

> **Note**: If you do not wish to use these names, you will need to setup your model a little bit (shown below).

An example of a basic Nested Sets Node:

	<?php

	use Illuminate\Database\Eloquent\Model;
	use Cartalyst\NestedSets\Nodes\NodeTrait;
	use Cartalyst\NestedSets\Nodes\NodeInterface;

	class Category extends Model implements NodeInterface {

		use NodeTrait;

	}

An example of a slightly more configured Nested Sets Node:

	<?php

	use Illuminate\Database\Eloquent\Model;
	use Cartalyst\NestedSets\Nodes\NodeTrait;
	use Cartalyst\NestedSets\Nodes\NodeInterface;

	class Category extends Model implements NodeInterface {

		/**
		 * The table associated with the model.
		 *
		 * @var string
		 */
		protected $table = 'categories';

		/**
		 * Array of reserved attributes used by the node. These attributes
		 * cannot be set like normal attributes, they are reserved for
		 * the node and nested set workers to use.
		 *
		 * @var array
		 */
		protected $reservedAttributes = array(

			// The left column limit. "left" is a reserved word in SQL
			// databases so we default to "lft" for compatiblity.
			'left'  => 'lft',

			// The right column limit. "right" is a reserved word in SQL
			// databases so we default to "rgt" for compatiblity.
			'right' => 'rgt',

			// The tree that the node is on. This package supports multiple
			// trees within one database.
			'tree'  => 'tree',
		);

		/**
		 * The worker class which the model uses.
		 *
		 * @var string
		 */
		protected $worker = 'Cartalyst\NestedSets\Workers\IlluminateWorker';

	}

That's it!
