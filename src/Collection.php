<?php

namespace lroman242\LaravelCassandra;

use Cassandra\Rows;
use lroman242\LaravelCassandra\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Cassandra rows instance
     *
     * @var \Cassandra\Rows
     */
    private $rows;

    /**
     * Items model
     *
     * @var Model
     */
    private $model;

    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @param  Model  $model
     *
     * @return void
     */
    public function __construct($items = [], Model $model = null)
    {
        if ($items instanceof \Cassandra\Rows) {
            $this->rows = $items;
        }
        $this->model = $model;

        parent::__construct($this->prepareItems($items));
    }

    /**
     * Prepare items for collection
     *
     * @return Rows|Model[]
     */
    protected function prepareItems($items)
    {
        if ($this->model !== null && $items instanceof \Cassandra\Rows) {
            $models = [];

            foreach ($items as $row) {
                $models[] = $this->model->newFromBuilder($row);
            }

            return $models;
        } else {
            return $items;
        }
    }

    /**
     * Next page token
     *
     * @return mixed
     */
    public function getNextPageToken()
    {
        return $this->rows->pagingStateToken();
    }

    /**
     * Last page indicator
     * @return bool
     */
    public function isLastPage()
    {
        return $this->rows->isLastPage();
    }

    /**
     * Get next page
     *
     * @return Collection
     */
    public function nextPage()
    {
        if (!$this->isLastPage()) {
            return new self($this->rows->nextPage(), $this->model);
        }
    }

    /**
     * Get rows instance
     *
     * @return \Cassandra\Rows
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Update current collection with results from
     * the next page
     *
     * @return Collection
     */
    public function appendNextPage()
    {
        $nextPage = $this->nextPage();

        if ($nextPage) {
            $this->items = array_merge($this->items, $nextPage->toArray());
            $this->rows = $nextPage->getRows();
        }

        return $this;
    }

    /**
     * Find a model in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getKey();
        }

        if ($key instanceof Arrayable) {
            $key = $key->toArray();
        }

        if (is_array($key)) {
            if ($this->isEmpty()) {
                return new static([], $this->model);
            }

            return $this->whereIn($this->first()->getKeyName(), $key);
        }

        return Arr::first($this->items, function ($model) use ($key) {
            return $model->getKey() == $key;
        }, $default);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function merge($items)
    {
        $dictionary = $this->getDictionary();

        foreach ($items as $item) {
            $dictionary[(string) $item->getKey()] = $item;
        }

        return new static(array_values($dictionary), $this->model);
    }

    /**
     * Reload a fresh model instance from the database for all the entities.
     *
     * @param  array|string  $with
     * @return static
     */
    public function fresh($with = [])
    {
        if ($this->isEmpty()) {
            return new static([], $this->model);
        }

        $model = $this->first();

        $freshModels = $model->newQueryWithoutScopes()
            ->with(is_string($with) ? func_get_args() : $with)
            ->whereIn($model->getKeyName(), $this->modelKeys())
            ->get()
            ->getDictionary();

        return $this->map(function ($model) use ($freshModels) {
            return $model->exists && isset($freshModels[(string) $model->getKey()])
                ? $freshModels[(string) $model->getKey()] : null;
        });
    }

    /**
     * Diff the collection with the given items.
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function diff($items)
    {
        $diff = new static([], $this->model);

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (!isset($dictionary[(string) $item->getKey()])) {
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function intersect($items)
    {
        $intersect = new static([], $this->model);

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (isset($dictionary[(string) $item->getKey()])) {
                $intersect->add($item);
            }
        }

        return $intersect;
    }

    /**
     * Return only unique items from the collection.
     *
     * @param  string|callable|null  $key
     * @param  bool  $strict
     * @return static|\Illuminate\Support\Collection
     */
    public function unique($key = null, $strict = false)
    {
        if (!is_null($key)) {
            return parent::unique($key, $strict);
        }

        return new static(array_values($this->getDictionary()), $this->model);
    }

    /**
     * Returns only the models from the collection with the specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items, $this->model);
        }

        $dictionary = Arr::only($this->getDictionary(), $keys);

        return new static(array_values($dictionary), $this->model);
    }

    /**
     * Returns all models in the collection except the models with specified keys.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        $dictionary = Arr::except($this->getDictionary(), $keys);

        return new static(array_values($dictionary), $this->model);
    }


    /**
     * Get a dictionary keyed by primary keys.
     *
     * @param  \ArrayAccess|array|null  $items
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[(string) $value->getKey()] = $value;
        }

        return $dictionary;
    }
}
