@php
    $name = fn (string $field): string => "rows[{$index}][{$field}]";
    $fieldError = fn (string $field): bool => $errors->has("rows.{$index}.{$field}");
    $cellClass = fn (string $field): string => 'w-full rounded-lg border bg-white px-2 py-1.5 text-xs dark:bg-slate-800 dark:text-white '
        .($fieldError($field) ? 'border-rose-400 ring-1 ring-rose-300' : 'border-slate-200 dark:border-slate-700');
    $selectedCategory = (string) ($row['category_id'] ?? '');
@endphp
<td class="px-2 py-1.5 font-mono text-slate-400" data-field="number">{{ is_numeric($index) ? $index + 1 : '' }}</td>
<td class="px-2 py-1.5">
    <select name="{{ $name('type') }}" data-field="type" class="{{ $cellClass('type') }} font-semibold">
        <option value="expense" @selected($rowType === 'expense')>Expense</option>
        <option value="personal" @selected($rowType === 'personal')>Personal</option>
        <option value="income" @selected($rowType === 'income')>Income</option>
    </select>
</td>
<td class="px-2 py-1.5"><input type="date" name="{{ $name('date') }}" data-field="date" value="{{ $row['date'] ?? now()->toDateString() }}" class="{{ $cellClass('date') }}"></td>
<td class="px-2 py-1.5"><input type="time" name="{{ $name('time') }}" data-field="time" value="{{ $row['time'] ?? '' }}" class="{{ $cellClass('time') }}"></td>
<td class="px-2 py-1.5"><input type="text" name="{{ $name('description') }}" data-field="description" value="{{ $row['description'] ?? '' }}" maxlength="255" placeholder="What was it?" class="{{ $cellClass('description') }}"></td>
<td class="px-2 py-1.5">
    <select name="{{ $name('category_id') }}" data-field="category_id" class="{{ $cellClass('category_id') }}">
        <option value="">— General —</option>
        @foreach($categoriesByType[$rowType] ?? [] as $category)
            <option value="{{ $category['id'] }}" @selected($selectedCategory === (string) $category['id'])>{{ $category['name'] }}</option>
        @endforeach
    </select>
</td>
<td class="px-2 py-1.5"><input type="number" step="0.01" min="0" name="{{ $name('amount') }}" data-field="amount" value="{{ $row['amount'] ?? '' }}" placeholder="0.00" class="{{ $cellClass('amount') }} font-bold"></td>
<td class="px-2 py-1.5">
    <select name="{{ $name('payment_method') }}" data-field="payment_method" class="{{ $cellClass('payment_method') }}">
        @foreach($paymentMethods as $method)
            <option value="{{ $method }}" @selected(($row['payment_method'] ?? $paymentMethods[0]) === $method)>{{ $method }}</option>
        @endforeach
    </select>
</td>
<td class="px-2 py-1.5 space-y-1">
    <div data-only="personal" class="{{ $rowType === 'personal' ? '' : 'hidden' }} grid grid-cols-2 gap-1">
        <input type="text" name="{{ $name('done_by') }}" value="{{ $row['done_by'] ?? '' }}" list="bulk-family-members" placeholder="Done by" class="{{ $cellClass('done_by') }}">
        <input type="text" name="{{ $name('done_to') }}" value="{{ $row['done_to'] ?? '' }}" list="bulk-family-members" placeholder="Done to" class="{{ $cellClass('done_to') }}">
    </div>
    <label data-only="personal" class="{{ $rowType === 'personal' ? '' : 'hidden' }} flex items-center gap-1.5 text-[11px] text-slate-600">
        <input type="hidden" name="{{ $name('add_to_main') }}" value="0">
        <input type="checkbox" name="{{ $name('add_to_main') }}" value="1" data-field="add_to_main" @checked(($row['add_to_main'] ?? '1') === '1') class="rounded border-slate-300">
        Add to main expenses
    </label>
    <label data-only="expense" class="{{ $rowType === 'expense' ? '' : 'hidden' }} flex items-center gap-1.5 text-[11px] text-slate-600">
        <input type="checkbox" name="{{ $name('is_unlogged') }}" value="1" data-field="is_unlogged" @checked(($row['is_unlogged'] ?? '') === '1') class="rounded border-slate-300">
        Unlogged (forgotten)
    </label>
    <label data-only="income" class="{{ $rowType === 'income' ? '' : 'hidden' }} flex items-center gap-1.5 text-[11px] text-slate-600">
        <input type="checkbox" name="{{ $name('keep_separate') }}" value="1" data-field="keep_separate" @checked(($row['keep_separate'] ?? '') === '1') class="rounded border-slate-300">
        Keep separate (not carried)
    </label>
</td>
<td class="px-2 py-1.5 text-right">
    <button type="button" data-remove class="text-base font-bold text-slate-300 hover:text-rose-600 cursor-pointer" aria-label="Remove row">&times;</button>
</td>
