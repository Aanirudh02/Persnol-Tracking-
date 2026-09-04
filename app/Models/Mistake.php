<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mistake extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'daily_record_id',
        'category_id',
        'date',
        'time',
        'title',
        'what_happened',
        'why_happened',
        'what_should_have_done',
        'lesson_learned',
        'prevention_plan',
        'severity', // Low, Medium, High, Critical
        'status',   // Open, Working On It, Resolved, Learned
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

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MistakeCategory::class, 'category_id');
    }

    /**
     * Check if this mistake has repeated patterns in the same category or with same tags
     */
    public function getRepeatedCount(): int
    {
        $query = self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id);

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        return $query->count();
    }
}
