<?php

use lroman242\LaravelCassandra\Eloquent\Model as Casloquent;
use lroman242\LaravelCassandra\Eloquent\SoftDeletes;

class Soft extends Casloquent
{
    use SoftDeletes;

    protected $table = 'soft';
    protected static $unguarded = true;
    protected $dates = ['deleted_at'];
}
