<?php namespace Cartalyst\NestedSets;
/**
 * Part of the Nested Sets package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Nested Sets
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Cartalyst\NestedSets\Nodes\EloquentNode;
use Illuminate\Support\ServiceProvider;

class NestedSetsServiceProvider extends ServiceProvider {

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		EloquentNode::setPresenter($this->app['nested.sets.presenter']);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerPresenter();
	}

	/**
	 * Register the presenter.
	 *
	 * @return void
	 */
	protected function registerPresenter()
	{
		$this->app['nested.sets.presenter'] = $this->app->share(function($app)
		{
			return new Presenter;
		});
	}

}
