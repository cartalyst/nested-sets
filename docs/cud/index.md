### Create, Update and Delete Nested sets Objects.

In the examples shown below, the models used are based on the typical `Model_Car`
example shown above, and each example will use the data created from the previous
example, as the methods all-in-all help you build a nested sets tree.

----------

#### root()

The `root` method returns the Nesty object.

The function returns `Nesty` and throws `NestyException`.

##### Example:

	// Create a new car make
	try
	{
		$ford = new Model_Car(array(
			'name' => 'Ford',
		));

		/**
		 * Make Ford a new root
		 * Nesty object
		 */
		$ford->root();
	}
	catch (NestyException $e)
	{
		$error = $e->getMessage();
	}

##### Database Table:

  id        | name      | lft         | rgt         | tree_id
  :-------- | :-------- | :---------: | :---------: | :------:
  1         | Ford      | 1           | 2           | 1


##### Nested Structure:

	Ford

----------

#### first_child_of($parent)

This method assigns an Nesty object to be the first child of the `$parent` parameter.

The function returns `integer` and throws `NestyException` only if the `$parent` does not exist.

##### Example:

	try
	{
		// Get ford
		$ford = Model_Car::find(function($query)
		{
			return $query->where('name', '=', 'Ford');
		});

		$falcon = new Model_Car(array(
			'name' => 'Falcon',
		));

		/**
		 * Make falcon first child
		 * of Ford
		 */
		$falcon->first_child_of($ford);
	}
	catch (NestyException $e)
	{
		$error = $e->getMessage();
	}

##### Database Table:

  id        | name      | lft         | rgt         | tree_id
  :-------- | :-------- | :---------: | :---------: | :------:
  1         | Ford      | 1           | 4           | 1
  2         | Falcon    | 2           | 3           | 1


##### Nested Structure:

	Ford
	|   Falcon

---------

#### last_child_of($parent)

This method assigns an Nesty object to be the last child of the `$parent` parameter.

The function returns `integer` and throws `NestyException` only if the `$parent` does not exist.

##### Example:

	try
	{
		// Get ford
		$ford = Model_Car::find(function($query)
		{
			return $query->where('name', '=', 'Ford');
		});

		$territory = new Model_Car(array(
			'name' => 'Territory',
		));

		/**
		 * This will be put as the new
		 * last child of Ford
		 */
		$territory->last_child_of($ford);
	}
	catch (NestyException $e)
	{
		$error = $e->getMessage();
	}

##### Database Table:

  id        | name      | lft         | rgt         | tree_id
  :-------- | :-------- | :---------: | :---------: | :------:
  1         | Ford      | 1           | 8           | 1
  2         | Falcon    | 2           | 3           | 1
  3         | Territory | 4           | 5           | 1

##### Nested Structure:

	Ford
	|   Falcon
	|   Territory

----------

#### previous_sibling_of($sibling)

This method assigns an Nesty object for which you want this object to be the previous `$sibling` of.

The function returns `Nesty` and throws `NestyException` only if the `$sibling` does not exist.

##### Example:

	try
	{
		$territory = Model_Car::find(function($query)
		{
			return $query->where('name', '=', 'Territory');
		});

		$f150 = new Model_Car(array(
			'name' => 'F150',
		));

		/**
		 * Make previous
		 * sibling of the Territory
		 */
		$f150->previous_sibling_of($territory);
	}
	catch (NestyException $e)
	{
		$error = $e->getMessage();
	}

##### Database Table:

  id        | name      | lft         | rgt         | tree_id
  :-------- | :-------- | :---------: | :---------: | :------:
  1         | Ford      | 1           | 8           | 1
  2         | Falcon    | 2           | 3           | 1
  3         | Territory | 6           | 7           | 1
  3         | F150      | 4           | 5           | 1

##### Nested Structure:
	Ford
	|   Falcon
	|   F150
	|   Territory

----------

#### next_sibling_of($sibling)

This method assigns an Nesty object for which you want this object to be the next `$sibling` of.

The function returns `Nesty` and throws `NestyException` only if the `$sibling` does not exist.

#####Example:

	try
	{
		$territory = Model_Car::find(function($query)
		{
			return $query->where('name', '=', 'Territory');
		});

		$festiva = new Model_Car(array(
			'name' => 'Festiva',
		));

		/**
		 * Make previous
		 * sibling of the Territory
		 */
		$festiva->next_sibling_of($territory);
	}
	catch (NestyException $e)
	{
		$error = $e->getMessage();
	}


##### Database Table:

  id        | name      | lft         | rgt         | tree_id
  :-------- | :-------- | :---------: | :---------: | :------:
  1         | Ford      | 1           | 10          | 1
  2         | Falcon    | 2           | 3           | 1
  3         | Territory | 6           | 7           | 1
  3         | F150      | 4           | 5           | 1
  3         | Festiva   | 8           | 9           | 1

##### Nested Structure:

	Ford
	|   Falcon
	|   F150
	|   Territory
	|   Festiva

----------

#### reload()

This method returns the Nesty object.

The function returns `Nesty` and throws `NestyException`.

> <strong>**Notes:**</strong> If you are moving or inserting more than one Nesty object in a request, you should be calling `>reload()` on active Nesty objects. This is because, to achieve maximum speed for the user, Nesty updates the database using `UPDATE` queries. These do not automatically update the models that have been initialised. Calling `reload()` automatically updates the model's properties to reflect any database changes.

##### Example:

	// Find the Ford Territory
	$territory = Model_Car::find(function($query)
	{
		return $query->where('name', '=', 'Territory');
	});

	/**
	 * Add a child ($t4)...
	 */
	$territory->reload();

	/**
	 * Add a child to $t4...
	 */
	$territory->reload();

	/**
	 * Etc...
	 */

----------

#### delete()

This method returns an `integer`; the number of records affected.

>**Notes:** Children items of a deleted Nesty object will be orphaned. This means they'll be put at the same level as the deleted object, becoming children of the deleted object's parent. This is a safeguard against deleting data accidently. The Exception to this is if the Nesty object is a root object. Calling delete on a root object will remove the entire tree. Be careful using this.

##### Example:

	// Sorry Territory, you're gone!
	$territory = Model_Car::find(function($query)
	{
		return $query->where('name', '=', 'Territory');
	});

	$territory->delete();

##### Database Table:

id        | name      | lft         | rgt         | tree_id
:-------- | :-------- | :---------: | :---------: | :------:
1         | Ford      | 1           | 8           | 1
2         | Falcon    | 2           | 3           | 1
4         | F150      | 4           | 5           | 1
5         | Festiva   | 6           | 7           | 1

##### Nested Structure:

	Ford
	|   Falcon
	|   F150
	|   Festiva

##### Example:

Say we had the following Nested Structure:

	Ford
	|   Falcon
	|   |   Futura
	|   |   FPV
	|   |   |   GT
	|   |   |   F6
	|   |   |   GS
	|   F150
	|   Festiva

And we deleted the "FPV" Nesty object. Our new structure would be:

	Ford
	|   Falcon
	|   |   Futura
	|   |   GT
	|   |   F6
	|   |   GS
	|   F150
	|   Festiva

Note how the children Nesty objects of FPV have been orphaned.

----------

#### delete_with_children()

This method returns an `integer`; the number of records affected.

>**Notes:** This method will delete a Nesty object and all of it's children objects (if they exist). See `delete()` if you do not want to delete children also.

##### Example:

	// Sorry Territory, you're gone!
	$territory = Model_Car::find(function($query)
	{
		return $query->where('name', '=', 'Territory');
	});

	$territory->delete();

##### Database Table:

id        | name      | lft         | rgt         | tree_id
:-------- | :-------- | :---------: | :---------: | :------:
1         | Ford      | 1           | 8           | 1
2         | Falcon    | 2           | 3           | 1
4         | F150      | 4           | 5           | 1
5         | Festiva   | 6           | 7           | 1

##### Nested Structure:

	Ford
	|   Falcon
	|   F150
	|   Festiva

##### Example:

Say we had the following Nested Structure:

	Ford
	|   Falcon
	|   |   Futura
	|   |   FPV
	|   |   |   GT
	|   |   |   F6
	|   |   |   GS
	|   F150
	|   Festiva

And we deleted the "Falcon" Nesty object. Our new structure would be:

	Ford
	|   F150
	|   Festiva

Note how "Falcon" and all it's children are gone.
