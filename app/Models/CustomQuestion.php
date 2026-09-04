<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomQuestion extends Model
{
    protected $fillable = ['question', 'type', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(CustomAnswer::class);
    }
}
