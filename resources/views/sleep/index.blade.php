<x-app-layout title="Sleep & Wake">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Sleep & Wake</h1>
            <p class="text-sm text-slate-500">Enter or backfill sleep and wake times for each day through today. Morning/night popups still work too.</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100">
            @foreach($days as $day)
                @php $r = $day['record']; @endphp
                <form action="{{ route('sleep.store') }}" method="POST" class="p-4 grid grid-cols-1 sm:grid-cols-6 gap-3 items-end text-sm">
                    @csrf
                    <input type="hidden" name="date" value="{{ $day['date'] }}">
                    <div class="sm:col-span-2">
                        <p class="font-semibold text-slate-900">{{ $day['label'] }}</p>
                        @if($r?->sleep_duration_hours)
                            <p class="text-xs text-slate-400">~{{ $r->sleep_duration_hours }} hrs sleep</p>
                        @endif
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Wake</label>
                        <input type="time" name="wake_up_time" value="{{ $r?->wake_up_time ? \Carbon\Carbon::parse($r->wake_up_time)->format('H:i') : '' }}" class="w-full mt-1 px-2 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Sleep</label>
                        <input type="time" name="sleep_time" value="{{ $r?->sleep_time ? \Carbon\Carbon::parse($r->sleep_time)->format('H:i') : '' }}" class="w-full mt-1 px-2 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Day /5</label>
                        <input type="number" min="1" max="5" name="day_rating" value="{{ $r?->day_rating }}" class="w-full mt-1 px-2 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                    </div>
                    <button class="py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Save</button>
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
