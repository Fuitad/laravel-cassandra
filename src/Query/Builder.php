<?php

namespace fuitad\LaravelCassandra\Query;

use fuitad\LaravelCassandra\Connection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;

class Builder extends BaseBuilder
{
    /**
     * Custom options to add to the query.
     *
     * @var array
     */
    public $options = [];

    /**
     * Indicate if we are executing a pagination query.
     *
     * @var bool
     */
    public $paginating = false;

    /**
     * @inheritdoc
     */
    public function __construct(Connection $connection, Processor $processor)
    {
        $this->grammar = new Grammar;
        $this->connection = $connection;
        $this->processor = $processor;
    }

}
