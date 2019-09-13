## Integration

### Laravel 6

The Nested Sets package has optional support for Laravel 6 and it comes bundled with a Service Provider for easy integration.

After installing the package, open your Laravel config file `config/app.php` and add the following lines.

In the `$providers` array add the following service provider for this package.

	'Cartalyst\NestedSets\Laravel\NestedSetsServiceProvider',

### Create a new Migration

Run the following command `php artisan make:migration create_menus_table`

Open the `database/migration/xxxx_xx_xxxxxx_create_menus_table.php` file

> Note: the `xxxx_xx_xxxxxx` is your current date and you can customize the migration name to fit your own needs.

Inside the `up()` method add the following:

```
Schema::create('menus', function($table)
{
	$table->increments('id');

	$table->string('name');

	// You can rename these columns to whatever you want, just remember
	// to update them on the $reservedAttributes inside your model.
	$table->integer('lft');
	$table->integer('rgt');
	$table->integer('menu');

	// you can add your own fields here

	$table->timestamps();
	$table->engine = 'InnoDB';
	$table->unique(array('lft', 'rgt', 'menu'));
});
```

> Note: For more information about the Schema Builder, check the [Laravel docs](http://laravel.com/docs/schema)

Once your migration is finished, you just need to run `php artisan migrate`.

### Create a new Model

Here is a default Eloquent model that you can use

```
use Illuminate\Database\Eloquent\Model;
use Cartalyst\NestedSets\Nodes\NodeTrait;
use Cartalyst\NestedSets\Nodes\NodeInterface;

class Menu extends Model implements NodeInterface {

	use NodeTrait;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'menus';

	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array
	 */
	protected $guarded = array('lft', 'rgt', 'menu', 'created_at', 'updated_at');

	/**
	 * Array of attributes reserved for the worker. These attributes
	 * cannot be set publically, only internally and shouldn't
	 * really be set outside this class.
	 *
	 * @var array
	 */
	protected $reservedAttributes = array(
		'left'  => 'lft',
		'right' => 'rgt',
		'tree'  => 'menu',
	);

}
```

### Native

Nested Sets ships with an implementation for Eloquent out of the box, in order to use the Eloquent implementation, you must require 'illuminate/database': "^6.0" on your composer.json file and run composer update afterwards.

```
// Include the composer autoload file
require 'vendor/autoload.php';

// Import the necessary classes
use Illuminate\Database\Eloquent\Model;
use Cartalyst\NestedSets\Nodes\NodeTrait;
use Cartalyst\NestedSets\Nodes\NodeInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

// Create the model
class Menu extends Model implements NodeInterface {

	use NodeTrait;

	protected $table = 'menus';

	protected $guarded = array('lft', 'rgt', 'menu', 'created_at', 'updated_at');

	protected $reservedAttributes = array(
		'left'  => 'lft',
		'right' => 'rgt',
		'tree'  => 'menu',
	);

}

// Establish the database connection
$capsule = new Capsule();

$capsule->addConnection([
	'driver'    => 'mysql',
	'host'      => 'localhost',
	'database'  => 'nested-sets',
	'username'  => 'username',
	'password'  => 'secret',
	'charset'   => 'utf8',
	'collation' => 'utf8_unicode_ci',
]);

$capsule->bootEloquent();

// Start using Nested Sets
$countries = new Menu(['name' => 'Countries']);
$countries->makeRoot();

$australia = new Menu(['name' => 'Australia']);
$australia->makeLastChildOf($countries);

$newZealand = new Menu(['name' => 'New Zealand']);
$newZealand->makeLastChildOf($countries);

$unitedStates = new Menu(['name' => 'United States of America']);
$unitedStates->makeLastChildOf($countries);
```
