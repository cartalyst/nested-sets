###delete()

-----------

This method returns an `integer`; the number of records affected.

>**Notes:** Children items of a deleted Nesty object will be orphaned. This means they'll be put at the same level as the deleted object, becoming children of the deleted object's parent. This is a safeguard against deleting data accidently. The Exception to this is if the Nesty object is a root object. Calling delete on a root object will remove the entire tree. Be careful using this.

#####Example:

	// Sorry Territory, you're gone!
	$territory = Model_Car::find(function($query)
	{
		return $query->where('name', '=', 'Territory');
	});
	
	$territory->delete();

#####Database Table:

id        | name      | lft         | rgt         | tree_id
:-------- | :-------- | :---------: | :---------: | :------:
1         | Ford      | 1           | 8           | 1
2         | Falcon    | 2           | 3           | 1
4         | F150      | 4           | 5           | 1
5         | Festiva   | 6           | 7           | 1


#####Nested Structure:

	Ford
	|   Falcon
	|   F150
	|   Festiva

#####Example:

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
