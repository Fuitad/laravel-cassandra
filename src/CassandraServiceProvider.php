<?php

namespace fuitad\LaravelCassandra;

use Illuminate\Support\ServiceProvider;

class CassandraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('cassandra', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });
    }
}
