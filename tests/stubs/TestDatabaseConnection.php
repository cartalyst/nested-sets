<?php
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

/**
 * @todo Remove when https://github.com/laravel/framework/pull/1426 gets merged.
 */

class TestDatabaseConnection extends \Illuminate\Database\Connection {

    /**
     * Get the cache manager instance.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCacheManager()
    {
        if ($this->cache instanceof Closure)
        {
            $this->cache = call_user_func($this->cache);
        }

        return $this->cache;
    }

    /**
     * Set the cache manager instance on the connection.
     *
     * @param  \Illuminate\Cache\CacheManager|\Closure  $cache
     * @return void
     */
    public function setCacheManager($cache)
    {
        $this->cache = $cache;
    }
}
