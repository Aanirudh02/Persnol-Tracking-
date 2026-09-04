@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'type' => null,
    'required' => false,
    'placeholder' => 'Select…',
    'allowAdd' => false,
])

@php
    $list = $options instanceof \Illuminate\Support\Collection ? $options : collect($options);
@endphp

<div class="space-y-1" x-data>
    @if($label)
        <label class="block font-semibold text-slate-700 mb-1">{{ $label }}</label>
    @endif
    <select
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($list as $opt)
            @php
                $value = is_array($opt) || is_object($opt) ? (data_get($opt, 'name') ?? data_get($opt, 'id')) : $opt;
                $text = is_array($opt) || is_object($opt) ? (data_get($opt, 'icon') && data_get($opt, 'type') === 'credit_status' ? data_get($opt, 'icon') : (data_get($opt, 'name') ?? $value)) : $opt;
                $idVal = is_object($opt) && isset($opt->id) && !isset($opt->type) ? $opt->id : $value;
            @endphp
            <option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</div>
