@props(['text' => null])

@if(filled($text))
    <p {{ $attributes->class('mt-1 whitespace-pre-line break-words text-[11px] font-normal text-slate-500') }}>📝 {{ $text }}</p>
@endif
