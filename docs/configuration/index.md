### Configure an Eloquent Node

We make the assumption you have read the [Laravel 4 documentation](http://four.laravel.com/docs/eloquent#basic-usage) on Eloquent models.

What do you need to do in addition? Not a whole lot:

1. Ensure you your class extends `Cartalyst\NestedSets\Nodes\EloquentNode` or a subclass of this.
2. Ensure you have the following columns in your database:
   1. `lft`  (integer)
   2. `rgt`  (integer)
   3. `tree` (integer)

   If you do not wish to use these names, you will need to configure your model a little bit (shown below).

An example of a basic Nested Sets Node:

	<?php

	class Category extends Cartalyst\NestedSets\Nodes\EloquentNode {

	}

An example of a slightly more configured Nested Sets Node:

	class Category extends Cartalyst\NestedSets\Nodes\EloquentNode {

		/**
		 * The table associated with the model.
		 *
		 * @var string
		 */
		protected $table = 'categories';

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

			// The right column limit. "right" is a
			// reserved word in SQL databases so
			// we default to "rgt" for compatiblity.
			'right' => 'rgt',

			// The tree that the node is on. This
			// package supports multiple trees within
			// the one database.
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
