### Basic Usage

A special rule to remember about using Eloquent models with Nested Sets is you probably do not want to be calling **save()** on a model to add it to the hierarchy. Calling **save()** will not automatically assign the node to any hierarchy in any tree.

A new concept to understand with Nested Sets is you insert, re-arrange nodes by placing them before or after another node (sibling) or as the first / last child of another node. In some other patterns, you may be used to setting a sort order for nodes. In Nested Sets, you set the sort order by placing a node relative to another node.

Common operations include:

* [Making a Root Node](#make-root)
* [Make a Node a Child of Another Node](#make-child)
* [Make a Node a Sibling of Another Node](#make-sibling)
* [Delete a Node](#deleting)
* [Getting the Hierarchy Tree](#reading)

<a id="make-root"></a>
#### Making a Root Node

Making a node a root node (which starts a new tree in the database) is done by calling `makeRoot()`. This will automatically save the node in the database and setup the `lft`, `rgt` and `tree` properties:

	<?php

	// Declare our Directory class
	class Directory extends Cartalyst\NestedSets\Nodes\EloquentNode {

		protected $table = 'directory';

	}

	// Make a new "Countries" root node
	$countries = new Directory(['name' => 'Countries']);
	$countries->makeRoot();

<a id="make-child"></a>
#### Make a Node a Child of Another Node

Leading off from our root node above, let's make a couple of child nodes. We have a choice to put the nodes as either the first or last child of the root node (Countries):

	<?php

	// Lead off from previous example...

	$australia = new Directory(['name' => 'Australia']);
	$australia->makeFirstChildOf($countries);

Nested Sets will ensure that any changes caused to the `Countries` record in the database are reflected in the `$countries` instance (the `lft` & `rgt` values are synchronized).

Let's make a couple more countries:

	<?php

	$newZealand = new Directory(['name' => 'New Zealand']);
	$newZealand->makeFirstChildOf($countries);

	$unitedStates = new Directory(['name' => 'United States of America']);
	$unitedStates->makeFirstChildOf($countries);

As you can see, we now have three children nodes for the "Countries" tree:

1. Australia
2. New Zealand
3. United States of America

Let's add Argentina to our list of countries:

	<?php

	$argentina = new Directory(['name' => 'Argentina']);
	$argentina->makeLastChildOf($countries);

Because we called `makeLastChild()` we now have the following list:

1. Australia
2. New Zealand
3. United States of America
4. Argentina

Let's call `makeFirstChild()` instead. This will ensure our new node gets put at the front of the list:

	<?php

	$argentina->makeFirstChildOf($countries);


We now have the following list of countries:

1. Argentina
2. Australia
3. New Zealand
4. United States of America

This is much better as it is sorted alphabetically.


Now, we've decided we want to broaden our directory listing to include England. This is where siblings come in;

<a id="make-sibling"></a>
#### Make a Node a Sibling of Another Node

Let's say you want to put a node in the hierarchy but do not want to make it the first or last child of a parent node. Easy - just make it a sibling instead.

You can make a node the previous sibling (meaning it will be on the same left, but sorted one before) another node:

	<?php

	// Lead off from previous example...

	$newZealand = Directory::where('name', 'New Zealand')->first();

	$england = new Directory(['name' => 'England']);

	$england->makePreviousSiblingOf($newZealand);

Great! Now our list is:

1. Argentina
2. Australia
3. England
4. New Zealand
5. United States of America

You can also make a node the next sibling of another node:

	<?php

	$australia = Directory::where('name', 'Australia')->first();

	$brazil = new Directory('name' => 'Brazil');
	$brazil->makeNextSiblingOf($australia);

> It is important to note, while the `$australia` variable is updated, any other variables are not updated. You must call `refresh()` on those models instead, or query for them again.

<a id="deleting"></a>
#### Deleting

When deleting a node, you need to consider any children it has. Let's assume we have the following hierarchial directory structure (of countries and their states):

1. Argentina
2. Australia
   1. New South Wales
   2. Victoria
3. England
4. New Zealand
5. United States of America
   1. California
   2. New York
   3. Washington

As a safeguard, when deleting a node, Nested Sets will move it's orphaned children up to the same level as it to replace it. For example, if you were to call `delete()` on Australia, you'd see:

1. Argentina
2. New South Wales
3. Victoria
4. England
5. New Zealand
6. United States of America
   1. California
   2. New York
   3. Washington

This is probably not what you want in your directory, a list of Australia's states at the same level as a country. So, to overcome this, you need to call `deleteWithChildren()`. This will ensure the orphaned children are not deleted:

1. Argentina
2. England
3. New Zealand
4. United States of America
   1. California
   2. New York
   3. Washington

<a id="reading"></a>
#### Getting the Hierarchy Tree

To get the hierarchy tree we've been creating, you need to do the following:

	<?php

	// Leading from previous example...

	$root = Directory::find(1);

	// We need to find our children. We don't do this
	// lazily because it can be advantageous to
	// control when it happens. You may wish
	// to provide a $depth limit to speed
	// up queries even more.
	$depth = 2; // Countries & states
	$root->findChildren($depth);

	// We can now loop through our children
	foreach ($root->getChildren() as $country)
	{
		echo "<h3>{$country->name}</h3>";

		if (count($country->getChildren()))
		{
			echo "<p>{$country->name} has the following states registered with our system:</p>";
			echo "<ul>";

			foreach ($country->getChildren() as $state)
			{
				echo "<li>{$state->name}</li>";
			}

			echo "</ul>";
		}
	}

The output from this would be (the markup below is tidied):

	<h3>Argentina</h3>

	<h3>England</h3>

	<h3>New Zealand</h3>

	<h3>United States of America</h3>
	<p>United States of America has the following states registered with our system:</p>
	<ul>
		<li>California</li>
		<li>New York</li>
		<li>Washington</li>
	</ul>
