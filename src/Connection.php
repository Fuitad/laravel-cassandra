<?php

namespace fuitad\LaravelCassandra;

use Cassandra;

class Connection extends \Illuminate\Database\Connection
{
    /**
     * The Cassandra keyspace
     *
     * @var string
     */
    protected $keyspace;

    /**
     * The Cassandra cluster
     *
     * @var \Cassandra\Cluster
     */
    protected $cluster;

    /**
     * The Cassandra connection handler.
     *
     * @var \Cassandra\Session
     */
    protected $session;

    /**
     * Create a new database connection instance.
     *
     * @param  array   $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // You can pass options directly to the Cassandra constructor
        $options = array_get($config, 'options', []);

        // Create the connection
        $this->cluster = $this->createCluster(null, $config, $options);

        if (isset($options['keyspace']) || isset($config['keyspace'])) {
            $this->keyspace = $config['keyspace'];
            $this->session = $this->cluster->connect($config['keyspace']);
        }

        $this->useDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return Query\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor;
    }

    /**
     * Begin a fluent query against a database collection.
     *
     * @param  string  $collection
     * @return Query\Builder
     */
    public function collection($collection)
    {
        $processor = $this->getPostProcessor();

        $query = new Query\Builder($this, $processor);

        return $query->from($collection);
    }

    /**
     * Begin a fluent query against a database collection.
     *
     * @param  string  $table
     * @return Query\Builder
     */
    public function table($table)
    {
        return $this->collection($table);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return Schema\Builder
     */
    public function getSchemaBuilder()
    {
        return new Schema\Builder($this);
    }

    /**
     * return Cassandra cluster.
     *
     * @return \Cassandra\Cluster
     */
    public function getCassandraCluster()
    {
        return $this->cluster;
    }

    /**
     * return Cassandra Session.
     *
     * @return \Cassandra\Session
     */
    public function getCassandraSession()
    {
        return $this->session;
    }

    /**
     * Return the Cassandra keyspace
     *
     * @return string
     */
    public function getKeyspace()
    {
        return $this->keyspace;
    }

    /**
     * Create a new Cassandra cluster object.
     *
     * @param  string  $dsn
     * @param  array   $config
     * @param  array   $options
     * @return \Cassandra\Cluster
     */
    protected function createCluster($dsn, array $config, array $options)
    {
        // By default driver options is an empty array.
        $driverOptions = [];

        if (isset($config['driver_options']) && is_array($config['driver_options'])) {
            $driverOptions = $config['driver_options'];
        }

        $cluster = Cassandra::cluster();

        // Check if the credentials are not already set in the options
        if (!isset($options['username']) && !empty($config['username'])) {
            $options['username'] = $config['username'];
        }
        if (!isset($options['password']) && !empty($config['password'])) {
            $options['password'] = $config['password'];
        }

        // Authentication
        if (isset($options['username']) && isset($options['password'])) {
            $cluster->withCredentials($options['username'], $options['password']);
        }

        // Contact Points/Host
        if (isset($options['contactpoints']) || (isset($config['host']) && !empty($config['host']))) {
            $contactPoints = $config['host'];

            if (isset($options['contactpoints'])) {
                $contactPoints = $options['contactpoints'];
            }

            $cluster->withContactPoints($contactPoints);
        }

        if (!isset($options['port']) && !empty($config['port'])) {
            $cluster->withPort((int) $config['port']);
        }

        return $cluster->build();
    }

    /**
     * Disconnect from the underlying Cassandra connection.
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return 'cassandra';
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->session->prepare($query);

            return $this->session->execute($statement, new \Cassandra\ExecutionOptions(['arguments' => $bindings]));
        });
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->session->prepare($query);

            return $this->session->execute($statement, new \Cassandra\ExecutionOptions(['arguments' => $bindings]));
        });
    }

    /**
     * Because Cassandra is an eventually consistent database, it's not possible to obtain
     * the affected count for statements so we're just going to return 0, based on the idea
     * that if the query fails somehow, an exception will be thrown
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $statement = $this->session->prepare($query);

            $this->session->execute($statement, new \Cassandra\ExecutionOptions(['arguments' => $bindings]));

            return 1;
        });
    }

    /**
     * Dynamically pass methods to the connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->cluster, $method], $parameters);
    }
}
