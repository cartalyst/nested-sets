<?php

/*
 * Part of the Nested Sets package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Cartalyst PSL License.
 *
 * This source file is subject to the Cartalyst PSL License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Nested Sets
 * @version    7.0.0
 * @author     Cartalyst LLC
 * @license    Cartalyst PSL
 * @copyright  (c) 2011-2020, Cartalyst LLC
 * @link       https://cartalyst.com
 */

namespace Cartalyst\NestedSets\Laravel;

use Cartalyst\NestedSets\Presenter;
use Illuminate\Support\ServiceProvider;
use Cartalyst\NestedSets\Nodes\NodeTrait;

class NestedSetsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPresenter();

        NodeTrait::$presenter = $this->app['nested.sets.presenter'];
    }

    /**
     * Register the presenter.
     *
     * @return void
     */
    protected function registerPresenter()
    {
        $this->app->singleton('nested.sets.presenter', function ($app) {
            return new Presenter();
        });
    }
}
