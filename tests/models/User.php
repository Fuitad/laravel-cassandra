<?php

use lroman242\LaravelCassandra\Eloquent\Model as Casloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Casloquent implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $dates = ['birthday'];

    protected static $unguarded = true;

    protected function getDateFormat()
    {
        return 'l jS \of F Y h:i:s A';
    }
}
