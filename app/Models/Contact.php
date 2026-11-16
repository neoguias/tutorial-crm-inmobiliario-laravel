<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'notes'];

    public function demands(): HasMany
    {
        return $this->hasMany(Demand::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
