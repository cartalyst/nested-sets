### Installation

Once downloaded, you will need to register the bundle with Laravel. Open your
**application/bundles.php** file. This is where you register all bundles used by
your application. Let's add Nested sets:

**Registering Nested sets:**

	return array(

		// array('auto' => true) will
		// automatically start the bundle.
		'nested-sets' => array('auto' => true),

	);

>**Notes:** If you don't want to automatically start the Nested sets bundle, click
[here](http://laravel.com/docs/bundles#starting-bundles) to learn how to manually
start bundles.
