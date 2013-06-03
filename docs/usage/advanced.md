### Advanced Usage

In addition to creating, reading, updating and deleting records, there are a number of other useful things you can do with Nested Sets. These include:

* [Retrieving All Root Nodes](#all-root)
* [Retrieving All Nodes Without Children](#all-leaf)
* [Getting the Path of a Node](#path)
* [Getting the Depth of a Node](#depth)
* [Getting the Depth of a Node Relative to Another Node](#relative-depth)
* [Mapping a Tree of Nodes (Mass-Assignment)](#map-tree)
* [Presenting a Node (Debugging / Outputting)](#presenting-a-node-debugging-outputting)

<a id="all-root"></a>
#### Retrieving All Root Nodes

Retrieving all root nodes is easy and is very similar to methods you [may already be familiar with](http://four.laravel.com/docs/eloquent#insert-update-delete).

For our example, let's assume we have a `Menu` model, which can have any number of separate menus (trees) in it and each menu may have any given hierarchical structure of data.

Let's retrieve all root nodes (the node which holds each menu's structure):

	<?php

	$menus = Menu::allRoot();

	echo '<h3>You have the following menus registered:</h3><ul>';

	foreach ($menus as $menu)
	{
		echo "<li>{$menu->name}</li>";
	}

	echo '</ul>';

The output of this might be:

	<h3>You have the following menus registered:</h3>

	<ul>
		<li>Main</li>
		<li>Sidebar</li>
		<li>Footer</li>
	</ul>

You might have started to notice how you can begin buliding a menu-management system for your next application.

<a id="all-leaf"></a>
#### Retrieving All Nodes Without Children

Referred to as "leaf nodes" in the Nested Sets Model, you may find a need to find all nodes which do not have any children:

	<?php

	$leaf = Menu::allLeaf();

Alternatively, you may wish to see if a node has children when iterating through. An example of this could be if you wanted to display special output (such as an additional CSS class) if a node has no children:

	<?php

	if ($item->isLeaf())
	{
		//
	}

<a id="path"></a>
#### Getting the Path of a Node

You can get the path of a node quite easily in Nested Sets. The path will return an array of all the primary keys for all parent nodes up to the root item. An example of usage for this would be if you had an active menu item, `$activeItem`. You could build an "active path"; the path of menu items which lead to this item.

	<?php

	// In your controller
	$activePath = $activeItem->getPath();

	return View::make('foo', compact('activeItem', 'activePath'));

	// In your view
	if (in_array($item->getKey(), $activePath))
	{
		echo 'class="active"';
	}

<a id="depth"></a>
#### Getting the Depth of a Node

Retrieving the depth of a node can be useful as well. You may wish to know how many parents the item has in the hierarchy tree:

	<?php

	// Outputs an integer, the item's depth,
	// where 0 means the item has no parents,
	// 1 means the item has 1 parent, 2 means
	// the item's parent has 1 parent and so on.
	echo $item->depth();

<a id="relative-depth"></a>
#### Getting the Depth of a Node Relative to Another Node

You can retrieve the depth of a node relative to another node extremely easily:

	<?php

	// You may wrap your own logic to find by a "slug"
	// or other unique field, we will not go into detail
	// on how to do that here.
	$services        = Menu::find('footer-services');
	$computerRepairs = Menu::find('footer-services-computer-repairs');

	echo "Computer Repairs is {$computerRepairs->relativeDepth($services)} levels deeper than Services";

<a id="map-tree"></a>
#### Mapping a Tree of Nodes (Mass-Assignment)

Because Nested Sets can involve a number of queries and API calls to build a hierarchy structure (through setting children & siblings), we have provided a convenient method which will match the supplied array of nodes (or simple arrays) and create a hierarchy tree from them.

It is intelligent enough to only create the nodes which do not exist, otherwise it will shuffle around or delete existing nodes accordingly.

Let's say we have the following menu on our website:

* Home
* About
* Services
   * Computer Services
      * Computer Repairs
      * Virus Removal
   * Website Development
   * Graphic Design
* Contact Us

This could be represented through the following dataset:

	<?php

	$tree = [
		['id' => 1, 'name' => 'Home'],
		['id' => 2, 'name' => 'About'],
		['id' => 3, 'name' => 'Services', 'children' => [
			['id' => 4, 'name' => 'Computer Services', 'children' => [
				['id' => 5, 'name' => 'Computer Repairs'],
				['id' => 6, 'name' => 'Virus Removal'],
			]],
			['id' => 7, 'name' => 'Website Development'],
			['id' => 8, 'name' => 'Graphic Design'],
		]],
		['id' => 9, 'name' => 'Contact Us'],
	];

	// Map the tree to our database
	$footer = Menu::find('footer');
	$footer->maptree($tree);

Now, let's pretend we want to break down website development into our two favorite jobs - building Cartalyst websites and Laravel applications. Additionally let's pretend our graphic designer has moved to a new city and is no longer working with us. We will stop advertising that service now.

Let's adjust the array which we map:

	<?php

	$tree = [
		['id' => 1, 'name' => 'Home'],
		['id' => 2, 'name' => 'About'],
		['id' => 3, 'name' => 'Services', 'children' => [
			['id' => 4, 'name' => 'Computer Services', 'children' => [
				['id' => 5, 'name' => 'Computer Repairs'],
				['id' => 6, 'name' => 'Virus Removal'],
			]],
			['id' => 7, 'name' => 'Website Development', 'children' => [

				// Note how we are not providing a primary key here,
				// these items will be created
				['name' => 'Cartalyst Websites'],
				['name' => 'Laravel Applications'],
			]],


			// Let's comment out Graphic Design, it will be removed.
			// ['id' => 8, 'name' => 'Graphic Design'],
		]],
		['id' => 9, 'name' => 'Contact Us'],
	];

	$footer = Menu::find('footer');
	$footer->mapTree($tree);

We would now have the following menu:

* Home
* About
* Services
   * Computer Services
      * Computer Repairs
      * Virus Removal
   * Website Development
      * Cartalyst Websites
      * Laravel Applications
* Contact Us

<a id="presenting"></a>
#### Presenting a Node (Debugging / Outputting)

Sometimes, you may not wish to write your own custom logic for outputting your trees. Thankfully, the hard work has already been done for many custom operations. We call it the presenting - and it's dead simple to use.

There are currently four ways to present your nodes:

1. Array (will return a set of nested arrays, much like what you pass to `mapTree()`).
2. Unordered List
3. Ordered List
4. JSON

You may choose to present a node's children or include the node itself. The methods to call are `presentChildrenAs()` and `presentAs()` respectively.

Both methods take a second parameter, which is the name of the attribute to be presented. Alternatively, you may pass through a Closure which is called for every element the presenter iterates through, where you must return a string which is the value outputted for that node.

And last but not least, you can set a limit for how far you want to loop.

Let's grab our footer menu from the previous example:

	$footer = Menu::find('footer');

	// We will create an unordered list, limited to 1 level deep
	// where the value of the 'name' property is outputted
	echo $footer->presentChildrenAs('ol', 'name', 1);

The result of this would look something like:

	<ol>
		<li>Home</li>
		<li>About</li>
		<li>Services</li>
		<li>Contact Us</li>
	</ol>

let's say we want to build our navigation for our website without adding the complexity of loading views. Let's combine some of the knowledge we have learned to quickly scaffold up our website's navigation:

	<?php

	$footer = Menu::find('footer');

	// We will present our items, providing a callback for
	// each item so we can custom render it's output. We
	// are limiting depth to two levels deep.
	echo $footer->presentChildrenAs('ul', function($item)
	{
		$output = '';

		// Create the start tag for our anchor
		$output .= '<a href="'.URL::to($item->uri).'"';
		if ($item->isLeaf()) $output .= ' class="no-children"';
		$output .= '>';

		// Output our name
		$output .= $item->name;

		// Close our anchor tag
		$output .= '</a>';

		return $output;
	}, 2);

The result of this would be:

	<ul>
		<li>
			<a href="http://www.example.com/" class="no-children">Home</a>
		</li>
		<li>
			<a href="http://www.example.com/about" class="no-children">About</a>
		</li>
		<li>
			<a href="http://www.example.com/services">Services</a>
			<ul>
				<li>
					<a href="http://www.example.com/services/computer-services">Computer Services</a>
				</li>
				<li>
					<a href="http://www.example.com/services/website development">Website Development</a>
				</li>
			</ul>
		</li>
		<li>
			<a href="http://www.example.com/contact-us" class="no-children">Contact Us</a>
		</li>
	</ul>

You could even get fancy and pass through something like this:

	echo $footer->presentChildrenAs('ul', function($item)
	{
		return View::make('navigation/item', compact('item'));
	}, 2);

This would not be recommended on large data sets as you will notice significant performance issues due to your computer's hard-drive I/O. A better solution would be to [loop through children manually](/nestedsets2/usage#reading) and present a new view per depth of children, not for each child. This is the best mix of separation of concerns (loading a view for view-related data) and speed.
