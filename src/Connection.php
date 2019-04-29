<?php

namespace lroman242\LaravelCassandra;

use Cassandra;
use Cassandra\BatchStatement;

class Connection extends \Illuminate\Database\Connection
{
    const DEFAULT_PAGE_SIZE = 5000;
    
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
     * The config
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new database connection instance.
     *
     * @param  array   $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        if (empty($this->config['page_size'])) {
            $this->config['page_size'] = self::DEFAULT_PAGE_SIZE;
        }

        // You can pass options directly to the Cassandra constructor
        $options = array_get($config, 'options', []);

        // Create the connection
        $this->cluster = $this->createCluster($config, $options);

        if (isset($options['database']) || isset($config['keyspace'])) {
            $keyspaceName = isset($options['database']) ? $options['database'] : $config['keyspace'];

            $this->keyspace = $keyspaceName;
            $this->session = $this->cluster->connect($keyspaceName);
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

        $query = new Query\Builder($this, null, $processor);

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
     * @param  array   $config
     * @param  array   $options
     * @return \Cassandra\Cluster
     */
    protected function createCluster(array $config, array $options)
    {
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

            if (is_array($contactPoints)) {
                $contactPoints = implode(',', $contactPoints);
            }

            $contactPoints = !empty($contactPoints) ? $contactPoints : '127.0.0.1';

            $cluster->withContactPoints($contactPoints);
        }

        if (!isset($options['port']) && !empty($config['port'])) {
            $cluster->withPort((int) $config['port']);
        }

        if (array_key_exists('page_size', $config) && !empty($config['page_size'])) {
            $cluster->withDefaultPageSize(intval($config['page_size'] ?? self::DEFAULT_PAGE_SIZE));
        }

        if (array_key_exists('consistency', $config) && in_array(strtoupper($config['consistency']), [
                Cassandra::CONSISTENCY_ANY, Cassandra::CONSISTENCY_ONE, Cassandra::CONSISTENCY_TWO,
                Cassandra::CONSISTENCY_THREE, Cassandra::CONSISTENCY_QUORUM, Cassandra::CONSISTENCY_ALL,
                Cassandra::CONSISTENCY_SERIAL, Cassandra::CONSISTENCY_QUORUM, Cassandra::CONSISTENCY_LOCAL_QUORUM,
                Cassandra::CONSISTENCY_EACH_QUORUM, Cassandra::CONSISTENCY_LOCAL_SERIAL, Cassandra::CONSISTENCY_LOCAL_ONE,
            ])) {
            $cluster->withDefaultConsistency($config['consistency']);
        }

        if (array_key_exists('timeout', $config) && !empty($config['timeout'])) {
            $cluster->withDefaultTimeout(intval($config['timeout']));
        }

        if (array_key_exists('connect_timeout', $config) && !empty($config['connect_timeout'])) {
            $cluster->withConnectTimeout(floatval($config['connect_timeout']));
        }

        if (array_key_exists('request_timeout', $config) && !empty($config['request_timeout'])) {
            $cluster->withRequestTimeout(floatval($config['request_timeout']));
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
     * @param  array  $customOptions
     *
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true, array $customOptions = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo, $customOptions) {
            if ($this->pretending()) {
                return [];
            }

            $preparedStatement = $this->session->prepare($query);

            //Add bindings
            $customOptions['arguments'] = $bindings;

            return $this->session->execute($preparedStatement, $customOptions);
        });
    }

    /**
     * Run an bulk insert statement against the database.
     *
     * @param  array  $queries
     * @param  array  $bindings
     * @param  int  $type
     * @param  array  $customOptions
     *
     * @return bool
     */
    public function insertBulk($queries = [], $bindings = [], $type = Cassandra::BATCH_LOGGED, array $customOptions = [])
    {
        return $this->batchStatement($queries, $bindings, $type, $customOptions);
    }

    /**
     * Execute a group of queries inside a batch statement against the database.
     *
     * @param  array  $queries
     * @param  array  $bindings
     * @param  int  $type
     * @param  array  $customOptions
     *
     * @return bool
     */
    public function batchStatement($queries = [], $bindings = [], $type = Cassandra::BATCH_LOGGED, array $customOptions = [])
    {
        return $this->run($queries, $bindings, function ($queries, $bindings) use ($type, $customOptions) {
            if ($this->pretending()) {
                return [];
            }

            $batch = new BatchStatement($type);

            foreach ($queries as $k => $query) {
                $preparedStatement = $this->session->prepare($query);
                $batch->add($preparedStatement, $bindings[$k]);
            }

            return $this->session->execute($batch, $customOptions);
        });
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  array  $customOptions
     *
     * @return bool
     */
    public function statement($query, $bindings = [], array $customOptions = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($customOptions) {
            if ($this->pretending()) {
                return [];
            }

            $preparedStatement = $this->session->prepare($query);
            //$this->recordsHaveBeenModified();

            //Add bindings
            $customOptions['arguments'] = $bindings;

            return $this->session->execute($preparedStatement, $customOptions);
        });
    }

    /**
     * Because Cassandra is an eventually consistent database, it's not possible to obtain
     * the affected count for statements so we're just going to return 0, based on the idea
     * that if the query fails somehow, an exception will be thrown
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  array  $customOptions
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [], array $customOptions = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $preparedStatement = $this->session->prepare($query);
            //$this->recordsHaveBeenModified();

            //Add bindings
            $customOptions['arguments'] = $bindings;

            $this->session->execute($preparedStatement, $customOptions);

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
     * Reconnect to the database if connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->session)) {
            $this->session = $this->createCluster($this->config, [])->connect($this->keyspace);
        }
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
