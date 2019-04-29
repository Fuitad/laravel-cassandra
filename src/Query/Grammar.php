<?php

namespace lroman242\LaravelCassandra\Query;

use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

class Grammar extends BaseGrammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'wheres',
        'orders',
        'limit',
        'groups',
        'allowFiltering'
    ];

    public function compileAllowFiltering(Builder $query, $bool)
    {
        return (bool) $bool ? 'ALLOW FILTERING' : '';
    }
}
