<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $fillable = ['username', 'email'];
}
