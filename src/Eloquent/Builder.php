<?php

namespace lroman242\LaravelCassandra\Eloquent;

use Cassandra\Rows;
use lroman242\LaravelCassandra\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Create a collection of models from plain arrays.
     *
     * @param  \Cassandra\Rows  $rows
     *
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
     *
     * @throws \Exception
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
            throw new \Exception('Invalid type of getPage response. Expected lroman242\LaravelCassandra\Collection or Cassandra\Rows');
        }

        return $this->model->hydrateRows($results);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     *
     * @throws \Exception
     */
    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        return $builder->getModels($columns);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     *
     * @throws \Exception
     */
    public function getModels($columns = ['*'])
    {
        $results = $this->query->get($columns);

        if ($results instanceof Collection) {
            $results = $results->getRows();
        } elseif (!$results instanceof Rows) {
            throw new \Exception('Invalid type of getPage response. Expected lroman242\LaravelCassandra\Collection or Cassandra\Rows');
        }

        return $this->model->hydrateRows($results);
    }

}
