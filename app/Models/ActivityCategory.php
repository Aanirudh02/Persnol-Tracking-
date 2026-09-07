<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'icon', 'color'];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'category_id');
    }
}
