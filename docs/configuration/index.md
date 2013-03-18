### Configuring the Nested sets bundle

Nested sets has quite a few configuration options out of the box. We allow for
setting `Table Name` , `Primary Key` ,  `Validation Rules` and their labels.

----------

#### Table Name

This is the table name that this Nested sets model uses in the database.

Property                         | Type            | Default       | Description
:------------------------------- | :-------------: | :------------ | :---------------
`protected static $_table`       | string          |               | The name of your database table.

----------

#### Primary Key

Property                         | Type            | Default       | Description
:------------------------------- | :-------------: | :------------ | :---------------
`protected static $_primary_key` | string          | id            | The primary key of this table.

----------

#### Validation Rules

An array of validation rules for a Nested sets model to have.

Property                         | Type            | Default       | Description
:------------------------------- | :-------------: | :------------ | :---------------
`protected static $_rules`       | array           |               | Validation rules.

----------

#### Validation Labels

An associative array of key / value pairs where key / value correspond to column_name / label respectively.

Property                         | Type            | Default       | Description
:------------------------------- | :-------------: | :------------ | :---------------
`protected static $_labels`      | array           |               | Validation labels.

----------

#### Nested sets Columns

`protected static $_Nested sets_cols` contains 4 properties. You can choose to customise these properties in this array and your table, should do wish to do so.

Option                       | Type            | Default       | Description
:--------------------------- | :-------------: | :------------ | :---------------
left                         | string          | lft           | The left-hand identifier for a Nested sets object
right                        | string          | rgt           | The right-hand identifier for a Nested sets object
name                         | string          | name          | The name property for a Nested sets object. Typically used for the label of a Nested sets object when displaying.
tree                         | string          | tree_id       | The tree identifier for a Nested sets object. (Nested sets supports multiple trees in one table)


##### Example of a typical Nested sets Model:

	class Model_Car extends Nesty
	{
		// Set the table name
		protected static $_table = 'cars';
	}

##### Slightly more configured Nested sets Model:

	class Model_Car extends Nesty
	{
		// Set the table name
		protected static $_table = 'cars';

		// Sets the rules
		protected static $_rules = array(
			'name' => 'required',
		);

		// Set labels for our rules
		protected static $_labels = array(
			'name' => 'Name field',
		);

		/**
		 * Override the Nested sets cols in the
		 * database. If you override this property
		 * you must specify all four keys (left, right,
		 * name and tree). The defaults are provided
		 * in the comments.
		 */
		protected static $_nesty_cols = array(
			'left'  => 'left_limit',   // Default is `lft`
			'right' => 'right_limit',  // Default is `rgt`
			'tree'  => 'car_range_id', // Default is `tree_id`
		);
	}

