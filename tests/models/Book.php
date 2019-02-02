<?php

use lroman242\LaravelCassandra\Eloquent\Model as Casloquent;

class Book extends Casloquent
{
    protected static $unguarded = true;
    protected $primaryKey = 'title';
}
