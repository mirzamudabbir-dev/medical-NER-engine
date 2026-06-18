<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'type'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
