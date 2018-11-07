<?php

namespace fuitad\LaravelCassandra\Eloquent;

use Cassandra\Rows;
use fuitad\LaravelCassandra\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Create a collection of models from plain arrays.
     *
     * @param  Rows  $rows
     * @return Collection
     */
    public function hydrateRows(Rows $rows)
    {
        $instance = $this->newModelInstance();

        return $instance->newCassandraCollection($rows);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     *
     * @return Collection
     */
    public function getPage($columns = ['*'])
    {
        $builder = $this->applyScopes();

        return $builder->getModelsPage($columns);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function getModelsPage($columns = ['*'])
    {
        $results = $this->query->getPage($columns);

        if ($results instanceof Collection) {
            $results = $results->getRows();
        } elseif (!$results instanceof Rows) {
            throw new \Exception('Invalid type of getPage response. Expected fuitad\LaravelCassandra\Collection or Cassandra\Rows');
        }

        return $this->model->hydrateRows($results);
    }
}
