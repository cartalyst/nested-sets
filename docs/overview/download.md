### Download

You have two options to download Nested Sets 2:

* Download with [Composer](http://getcomposer.org)
* Download from GitHub
	* Download the `.zip` file
	* Clone the repository

> **Note:** To download you need to have valid a Cartalyst.com subscription.
Click [here](https://www.cartalyst.com/pricing) to obtain your subscription.

----------

#### Composer

To install through composer, simply put the following in your `composer.json` file:

	"repositories": [
		{
			"type": "composer",
			"url": "http://packages.cartalyst.com"
		}
	],
	{
		"require": {
			"cartalyst/nested-sets": "2.0.*"
		},
		"minimum-stability": "dev"
	}

The `minimum-stability` flag is only required while Nested Sets 2 is in alpha stage.
When it becomes stable you may change your flag.

> If you are not installing in Laravel 4 and wish to use our default implementations, you will also need to put `"illuminate/database": "4.0.*"` into your `require` attribute in your `composer.json` file. We only require this package to be installed if you don't have your own **worker** and **node** implementations.

----------

#### GitHub

##### Download Nested Sets

Download Nested Sets into the 'vendor/nested-sets' folder (or wherever you see fit for
your application). You can download the latest version of Nested Sets via zip
[here](https://github.com/cartalyst/nested-sets/zipball/master) or pull directly from
 the repository with the following command within the 'vendor/nested-sets' directory.

##### Clone Nested Sets

    $ git clone -b master git@github.com:cartalyst/nested-sets.git

If you manually access from GitHub, you will need to handle autoloading logic
yourself. You will need to do the following:

1. Use PSR 0 to load the `Cartalyst\NestedSets` namespace to `path/to/nested-sets/src`
