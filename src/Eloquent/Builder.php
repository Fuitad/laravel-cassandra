<?php

namespace fuitad\LaravelCassandra\Eloquent;

use fuitad\LaravelCassandra\Collection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @param  int|null  $pageSize
     * @param  string|null  $nextPageToken
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'], $pageSize = null, $nextPageToken = null)
    {
        $builder = $this->applyScopes();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($models = $builder->getModels($columns, $pageSize, $nextPageToken)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->getModel()->newCollection($models);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     *
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function getModels($columns = ['*'], $length = null, $nextPageToken = null)
    {
        return $this->model->hydrate(
            $this->query->get($columns, $length, $nextPageToken)->all()
        )->all();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  int|null  $pageSize
     * @param  string|null  $nextPageToken
     *
     * @return Collection
     */
    public function getPage($pageSize = null, $nextPageToken = null)
    {
        $builder = $this->applyScopes();

        return $builder->getModelsPage($pageSize, $nextPageToken);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  int|null  $pageSize
     * @param  string|null  $nextPageToken
     *
     * @return Collection
     */
    public function getModelsPage($pageSize = null, $nextPageToken = null)
    {
        return $this->model->hydrateRows(
            $this->query->getPage($pageSize, $nextPageToken)
        );
    }

}
