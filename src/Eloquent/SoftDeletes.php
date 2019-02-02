<?php

namespace lroman242\LaravelCassandra\Eloquent;

use Exception;

trait SoftDeletes
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        throw new Exception('Not implemented');
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * @inheritdoc
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->getDeletedAtColumn();
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return $this->{$this->getDeletedAtColumn()} != '';
    }

}
