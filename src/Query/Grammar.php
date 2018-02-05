<?php

namespace fuitad\LaravelCassandra\Query;

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
        'allowFiltering',
        'groups',
    ];

    public function compileAllowFiltering(Builder $query, $bool)
    {
        return (bool) $bool ? 'ALLOW FILTERING' : '';
    }
}
