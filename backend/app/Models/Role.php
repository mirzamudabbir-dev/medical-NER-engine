<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'permissions'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
