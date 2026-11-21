@if($payment->expense_id)
    <a href="{{ route('expenses.show', $payment->expense_id) }}" class="text-[11px] font-semibold text-emerald-700 hover:underline">✅ Expense #{{ $payment->expense_id }}</a>
@elseif($payment->personal_expense_id)
    <span class="text-[11px] font-semibold text-purple-700">💜 Personal expense #{{ $payment->personal_expense_id }}</span>
@elseif($payment->income_id)
    <a href="{{ route('income.show', $payment->income_id) }}" class="text-[11px] font-semibold text-teal-700 hover:underline">💰 Income #{{ $payment->income_id }}</a>
@elseif($item->type === 'credit')
    <form action="{{ route('credits.payments.register-expense', [$item, $payment]) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="text-[11px] font-semibold text-emerald-700 hover:underline cursor-pointer" title="Register this due payment as its own expense, linked to this credit">📗 Register as expense</button>
    </form>
@endif
