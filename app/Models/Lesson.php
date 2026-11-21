<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'mistake_id',
        'category_id',
        'date',
        'title',
        'lesson',
        'tags',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mistake(): BelongsTo
    {
        return $this->belongsTo(Mistake::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MistakeCategory::class, 'category_id');
    }
}
