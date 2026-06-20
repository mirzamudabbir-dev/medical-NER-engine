<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'type'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
