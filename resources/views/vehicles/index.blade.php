<x-app-layout title="Vehicles">
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Vehicles</h1>
            <p class="text-sm text-slate-500">Default scooter is TVS Pep+ (2006) at 40 km/L mileage — editable anytime.</p>
        </div>

        <div class="space-y-3">
            @forelse($vehicles as $v)
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-lg text-slate-900">{{ $v->name }} @if($v->is_default)<span class="text-xs font-semibold text-emerald-600">Default</span>@endif</h2>
                            <p class="text-sm text-slate-500">{{ $v->make }} {{ $v->model }} {{ $v->year }} · Mileage {{ $v->effectiveMileage() }} km/L</p>
                            @if($v->actual_mileage_kmpl)
                                <p class="text-sm text-slate-600 mt-1">Measured mileage: {{ $v->actual_mileage_kmpl }} km/L</p>
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('vehicles.update', $v) }}" method="POST" class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Name</label>
                            <input type="text" name="name" value="{{ $v->name }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl" placeholder="Name">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Mileage (km/L)</label>
                            <input type="number" step="0.1" name="default_mileage_kmpl" value="{{ $v->default_mileage_kmpl }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl" placeholder="e.g. 40">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Make</label>
                            <input type="text" name="make" value="{{ $v->make }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl" placeholder="Make">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Model</label>
                            <input type="text" name="model" value="{{ $v->model }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl" placeholder="Model">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
                            <input type="number" name="year" value="{{ $v->year }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl" placeholder="Year">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Reg no (optional)</label>
                            <input type="text" name="registration_number" value="{{ $v->registration_number }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl" placeholder="Reg no">
                        </div>
                        <label class="flex items-center gap-2 col-span-2"><input type="checkbox" name="is_default" value="1" @checked($v->is_default)> Default vehicle</label>
                        <button class="col-span-2 py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Update</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No vehicles yet.</p>
            @endforelse
        </div>

        <form action="{{ route('vehicles.store') }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3 text-sm">
            @csrf
            <h3 class="font-bold text-slate-900">Add vehicle</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Name</label>
                    <input type="text" name="name" required placeholder="Name" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Mileage (km/L)</label>
                    <input type="number" step="0.1" name="default_mileage_kmpl" value="40" required placeholder="e.g. 40" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Make</label>
                    <input type="text" name="make" placeholder="Make" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Model</label>
                    <input type="text" name="model" placeholder="Model" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Year</label>
                    <input type="number" name="year" placeholder="Year" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Purchase date</label>
                    <input type="date" name="purchase_date" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
            </div>
            <button class="w-full py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Add vehicle</button>
        </form>
    </div>
</x-app-layout>
