<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MistakeCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'color'];

    public function mistakes(): HasMany
    {
        return $this->hasMany(Mistake::class, 'category_id');
    }
}
