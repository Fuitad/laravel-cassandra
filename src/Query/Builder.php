<?php

namespace fuitad\LaravelCassandra\Query;

use fuitad\LaravelCassandra\Connection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;

class Builder extends BaseBuilder
{
    /**
     * @inheritdoc
     */
    public function __construct(Connection $connection, Processor $processor)
    {
        $this->grammar = new Grammar;
        $this->connection = $connection;
        $this->processor = $processor;
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

}
