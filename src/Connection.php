<?php

namespace fuitad\LaravelCassandra;

use Cassandra;
use Cassandra\BatchStatement;
use Cassandra\ExecutionOptions;

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

        $this->useDefaultSchemaGrammar();

        $this->setQueryGrammar($this->getDefaultQueryGrammar());
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string  $table
     * @return Query\Builder
     */
    public function table($table)
    {
        $processor = $this->getPostProcessor();

        $query = new Query\Builder($this, $processor);

        return $query->from($table);
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

            // Really hacky but Eloquent Builder add  ( ) to where on request with more than one filter.
            // preg_replace change "...where ( ... )" to "...where ..." or with "limit n" at the end.
            $query = preg_replace('~(.*)where \(([^\)]*)\)$~', '${1}where ${2}', $query);
            $query = preg_replace('~(.*)where \(([^\)]*)\)(.*)( limit [0-9]*)$~', '${1}where ${2}${3}', $query);
            $preparedStatement = $this->session->prepare($query);

            return $this->session->execute($preparedStatement, new ExecutionOptions(['arguments' => $bindings]));
        });
    }

    /**
     * Run an bulk insert statement against the database.
     *
     * @param  array  $queries
     * @param  array  $bindings
     * @return bool
     */
    public function insertBulk($queries = [], $bindings = [], $type = Cassandra::BATCH_LOGGED)
    {
        return $this->batchStatement($queries, $bindings, $type);
    }

    /**
     * Execute a group of queries inside a batch statement against the database.
     *
     * @param  array  $queries
     * @param  array  $bindings
     * @return bool
     */
    public function batchStatement($queries = [], $bindings = [], $type = Cassandra::BATCH_LOGGED)
    {
        return $this->run($queries, $bindings, function ($queries, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $batch = new BatchStatement(Cassandra::BATCH_LOGGED);

            foreach ($queries as $k => $query) {
                $preparedStatement = $this->session->prepare($query);
                $batch->add($preparedStatement, $bindings[$k]);
            }

            return $this->session->execute($batch);
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

            $preparedStatement = $this->session->prepare($query);

            return $this->session->execute($preparedStatement, new ExecutionOptions(['arguments' => $bindings]));
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

            $preparedStatement = $this->session->prepare($query);

            $this->session->execute($preparedStatement, new ExecutionOptions(['arguments' => $bindings]));

            return 1;
        });
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammar();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultSchemaGrammar()
    {
        //return new Schema\Grammar();
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
