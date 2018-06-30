<?php

namespace fuitad\LaravelCassandra\Query;

use fuitad\LaravelCassandra\Collection;
use fuitad\LaravelCassandra\Connection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;

class Builder extends BaseBuilder
{
    public $allowFiltering = false;

    /**
     * @inheritdoc
     */
    public function __construct(Connection $connection, Grammar $grammar = null, Processor $processor = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
        $this->processor = $processor ?: $connection->getPostProcessor();
    }

    /**
     * Support "allow filtering"
     */
    public function allowFiltering($bool = true) {
        $this->allowFiltering = (bool) $bool;

        return $this;
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];

            return $this->connection->insert(
                $this->grammar->compileInsert($this, $values),
                $this->cleanBindings(Arr::flatten($values, 1))
            );
        }

        // Here, we'll generate the insert queries for every record and send those
        // for a batch query
        else {
            $queries = [];
            $bindings = [];

            foreach ($values as $key => $value) {
                ksort($value);

                $queries[] = $this->grammar->compileInsert($this, $value);
                $bindings[] = $this->cleanBindings(Arr::flatten($value, 1));
            }

            return $this->connection->insertBulk($queries, $bindings);
        }
    }
    
    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @param  int|null  $pageSize
     * @param  string|null  $nextPageToken
     *
     * @return \Illuminate\Support\Collection
     *
     * @TODO implement skip / offset
     */
    public function get($columns = ['*'], $pageSize = null, $nextPageToken = null)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        //Set up custom options
        $options = [];
        if ($pageSize !== null && (int)$pageSize > 0) {
            $options['page_size'] = (int) $pageSize;
        }
        if ($nextPageToken !== null) {
            $options['paging_state_token'] = $nextPageToken;
        }

        // Process select with custom options
        $results = $this->processor->processSelect($this, $this->runSelect($options));

        // Get results from all pages
        $collection = new Collection($results);

//TODO:
//        if (something) {
            while (!$collection->isLastPage()) {
                $collection = $collection->appendNextPage();
            }
//        }

        $this->columns = $original;

        return $collection;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  int|null  $pageSize
     * @param  string|null  $nextPageToken
     *
     * @return \Cassandra\Rows
     *
     * @TODO implement skip / offset
     */
    public function getPage($pageSize = null, $nextPageToken = null)
    {
        $options = [];
        if ($pageSize !== null && (int)$pageSize > 0) {
            $options['page_size'] = (int) $pageSize;
        }
        if ($nextPageToken !== null) {
            $options['paging_state_token'] = $nextPageToken;
        }

        $results = $this->processor->processSelect($this, $this->runSelect($options));

        return $results;
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect($options = [])
    {
        return $this->connection->select(
            $this->toSql(), $this->getBindings(), ! $this->useWritePdo, $options
        );
    }

}
