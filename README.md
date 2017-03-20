Laravel Cassandra
===============

[![Build Status](https://scrutinizer-ci.com/g/Fuitad/laravel-cassandra/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Fuitad/laravel-cassandra/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Fuitad/laravel-cassandra/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Fuitad/laravel-cassandra/?branch=master) [![Donate](https://img.shields.io/badge/donate-paypal-blue.svg)](https://www.paypal.me/fuitad)

A Cassandra based Eloquent model and Query builder for Laravel (Casloquent)

**WARNING**: This is a work in progress... not ready for usage yet.

Installation
------------

### Laravel version Compatibility

 Laravel  | Package
:---------|:----------
 5.4.x    | dev-master

Make sure you have the Cassandra PHP driver installed (version 1.2+). You can find more information at http://datastax.github.io/php-driver/.

Installation using composer:

```
composer require fuitad/laravel-cassandra
```

And add the service provider in `config/app.php`:

```php
fuitad\LaravelCassandra\CassandraServiceProvider::class,
```

(haven't tested with Lumen yet... TODO)

The service provider will register a cassandra database extension with the original database manager. There is no need to register additional facades or objects. When using cassandra connections, Laravel will automatically provide you with the corresponding cassandra objects.

For usage outside Laravel, check out the [Capsule manager](https://github.com/illuminate/database/blob/master/README.md) and add:

```php
$capsule->getDatabaseManager()->extend('cassandra', function($config)
{
    return new fuitad\LaravelCassandra\Connection($config);
});
```

Configuration
-------------

Change your default database connection name in `config/database.php`:

```php
'default' => env('DB_CONNECTION', 'cassandra'),
```

And add a new cassandra connection:

```php
'cassandra' => [
    'driver'   => 'cassandra',
    'host'     => env('DB_HOST', 'localhost'),
    'port'     => env('DB_PORT', 9142),
    'keyspace' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
],
```

You can connect to multiple servers with the following configuration:

```php
'cassandra' => [
    'driver'   => 'cassandra',
    'host'     => ['server1', 'server2'],
    'port'     => env('DB_PORT', 9142),
    'keyspace' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
],
```


Eloquent
--------

TODO

Query Builder
-------------

The database driver plugs right into the original query builder. When using cassandra connections, you will be able to build fluent queries to perform database operations.

```php
$users = DB::table('users')->get();

$user = DB::table('users')->where('name', 'John')->first();
```

If you did not change your default database connection, you will need to specify it when querying.

```php
$user = DB::connection('cassandra')->table('users')->get();
```

Read more about the query builder on http://laravel.com/docs/queries

Credits
---------

A lot of the logic behind this package is borrowed from the great [Laravel-MongoDB](https://github.com/jenssegers/) package.
