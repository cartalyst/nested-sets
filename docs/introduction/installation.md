### Installation

Installing Nested Sets is extremely easy. Once you've [downloaded](/nested-sets-2/introduction/download) Nested Sets, you will need to setup the [Presenter](/nested-sets-2/usage/advanced) if you want to use it.

If you're using Laravel 4, simply add the following to the `providers` array in `app/config/app.php`:

	'Cartalyst\NestedSets\NestedSetsServiceProvider',

If you're using Nested Sets outside of Laravel, simple call the following in your application before you first use the Presenter:


	Cartalyst\NestedSets\Nodes\EloquentNode::setPresenter(new Cartalyst\NestedSets\Presenter);
