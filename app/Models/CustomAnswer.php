<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CustomAnswer extends Model
{
    protected $fillable = ['user_id', 'custom_question_id', 'answer_encrypted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CustomQuestion::class, 'custom_question_id');
    }

    public function getDecryptedAnswerAttribute(): string
    {
        try {
            return Crypt::decryptString($this->answer_encrypted);
        } catch (\Exception $e) {
            return $this->answer_encrypted;
        }
    }

    public function setAnswerAttribute(string $value): void
    {
        $this->attributes['answer_encrypted'] = Crypt::encryptString($value);
    }
}
