## Install & Configure in Laravel 4

> **Note:** To use Cartalyst's Nested Sets package you need to have a valid Cartalyst.com subscription.
Click [here](https://www.cartalyst.com/pricing) to obtain your subscription.

### 1. Composer {#composer}

---

Open your `composer.json` file and add the following lines:

	{
		"require": {
			"cartalyst/nested-sets": "2.0.*"
		},
		"repositories": [
			{
				"type": "composer",
				"url": "http://packages.cartalyst.com"
			}
		],
		"minimum-stability": "stable"
	}

Run a composer update from the command line.

	composer update


### 2. Service Provider {#service-provider}

---

Add the following to the list of service providers in `app/config/app.php`.

	'Cartalyst\NestedSets\NestedSetsServiceProvider',
