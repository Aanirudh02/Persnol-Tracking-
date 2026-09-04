<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'icon', 'color'];

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class, 'category_id');
    }
}
