<?php

namespace lroman242\LaravelCassandra\Query;

use lroman242\LaravelCassandra\Collection;
use lroman242\LaravelCassandra\Connection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;

class Builder extends BaseBuilder
{
    /**
     * Use cassandra filtering
     *
     * @var bool
     */
    public $allowFiltering = false;

    /**
     * Size of fetched page
     *
     * @var null|int
     */
    protected $pageSize = null;

    /**
     * Pagination state token
     *
     * @var null|string
     */
    protected $paginationStateToken = null;

    /**
     * Indicate what amount of pages should be fetched
     * all or single
     *
     * @var bool
     */
    protected $fetchAllResults = true;

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
     *
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        //Set up custom options
        $options = [];
        if ($this->pageSize !== null && (int) $this->pageSize > 0) {
            $options['page_size'] = (int) $this->pageSize;
        }
        if ($this->paginationStateToken !== null) {
            $options['paging_state_token'] = $this->paginationStateToken;
        }

        // Process select with custom options
        $results = $this->processor->processSelect($this, $this->runSelect($options));

        // Get results from all pages
        $collection = new Collection($results);

        if ($this->fetchAllResults) {
            while (!$collection->isLastPage()) {
                $collection = $collection->appendNextPage();
            }
        }

        $this->columns = $original;

        return $collection;
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @param array $options
     *
     * @return array
     */
    protected function runSelect(array $options = [])
    {
        return $this->connection->select(
            $this->toSql(), $this->getBindings(), !$this->useWritePdo, $options
        );
    }

    /**
     * Set pagination state token to fetch
     * next page
     *
     * @param string $token
     *
     * @return Builder
     */
    public function setPaginationStateToken($token = null)
    {
        $this->paginationStateToken = $token;

        return $this;
    }

    /**
     * Set page size
     *
     * @param int $pageSize
     *
     * @return Builder
     */
    public function setPageSize($pageSize = null)
    {
        if ($pageSize !== null) {
            $this->pageSize = (int) $pageSize;
        } else {
            $this->pageSize = $pageSize;
        }

        return $this;
    }

    /**
     * Get collection with single page results
     *
     * @param $columns array
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPage($columns = ['*'])
    {
        $this->fetchAllResults = false;

        $result = $this->get($columns);

        $this->fetchAllResults = true;

        return $result;
    }
}
