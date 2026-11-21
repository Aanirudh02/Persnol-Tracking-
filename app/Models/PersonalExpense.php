<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'expense_id',
        'personal_expense_group_id',
        'amount',
        'gst_amount',
        'date',
        'time',
        'description',
        'payment_method',
        'done_by',
        'done_to',
        'notes',
        'receipt_image',
        'is_voluntary',
        'is_archived',
        'classification',
        'credit_debt_id',
        'is_closed',
        'closed_at',
        'closed_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'date' => 'date',
        'is_voluntary' => 'boolean',
        'is_archived' => 'boolean',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * The credit this personal expense was filed from.
     */
    public function creditDebt(): BelongsTo
    {
        return $this->belongsTo(CreditDebt::class);
    }

    /**
     * Credit payments that were filed as this personal expense.
     */
    public function creditDebtPayments(): HasMany
    {
        return $this->hasMany(CreditDebtPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalExpenseCategory::class, 'category_id');
    }

    public function personalExpenseGroup(): BelongsTo
    {
        return $this->belongsTo(PersonalExpenseGroup::class, 'personal_expense_group_id');
    }

    public function linkedExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    /**
     * Mark this personal expense as settled and closed.
     *
     * @param  string  $reason  'manual' or 'debt_closed' (closed automatically with its debt)
     */
    public function markClosed(string $reason = 'manual'): void
    {
        $this->update(['is_closed' => true, 'closed_at' => now(), 'closed_reason' => $reason]);
    }

    public function reopen(): void
    {
        $this->update(['is_closed' => false, 'closed_at' => null, 'closed_reason' => null]);
    }

    /**
     * Record (or refresh) this personal expense in main expenses so the Daily Cash Register sees it.
     */
    public function syncLinkedNormalExpense(): Expense
    {
        $categoryName = $this->category?->name ?? 'Personal';
        $normalCategory = ExpenseCategory::firstOrCreate(
            ['name' => $categoryName, 'user_id' => null],
            ['icon' => 'user', 'color' => '#3b82f6', 'is_archived' => false]
        );

        $values = [
            'category_id' => $normalCategory->id,
            'amount' => $this->amount,
            'gst_amount' => $this->gst_amount,
            'date' => $this->date->toDateString(),
            'time' => $this->time ?: now()->format('H:i'),
            'description' => '[Personal] '.($this->description ?: $categoryName),
            'payment_method' => $this->payment_method ?: 'Cash',
            'notes' => $this->notes,
            'is_voluntary' => $this->is_voluntary,
        ];

        if ($this->linkedExpense) {
            $this->linkedExpense->update($values);

            return $this->linkedExpense;
        }

        $dayRecord = DailyRecord::forUserOnDate($this->user_id, $this->date);

        $expense = Expense::create([
            ...$values,
            'user_id' => $this->user_id,
            'daily_record_id' => $dayRecord->id,
        ]);

        $this->update(['expense_id' => $expense->id]);

        return $expense;
    }

    public function totalAmount(): float
    {
        return (float) $this->amount + (float) ($this->gst_amount ?? 0);
    }
}
