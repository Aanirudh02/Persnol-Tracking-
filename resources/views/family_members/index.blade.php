<x-app-layout title="Family Members">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <span>👨‍👩‍👧‍👦</span> Family Members
                </h1>
                <p class="text-xs text-slate-500">Manage names and details of your family members for personal expenses and records.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openAddFamilyModal()" class="px-4 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-500 text-white font-bold text-xs shadow-md shadow-pink-600/20 flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                    <span>+</span> Add Family Member
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-pink-100 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
            <form action="{{ route('family-members.index') }}" method="GET" class="w-full sm:w-80 flex items-center gap-2">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, relation, details..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-pink-500 transition">
                </div>
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 text-white dark:bg-slate-700 hover:bg-slate-800 text-xs font-semibold cursor-pointer">
                    Search
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('family-members.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold transition">
                        Reset
                    </a>
                @endif
            </form>
            <div class="text-xs text-slate-500">
                Total: <span class="font-bold text-pink-600 dark:text-pink-400">{{ $members->total() }}</span> member(s)
            </div>
        </div>

        <!-- Members Table / Cards -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            @if($members->isEmpty())
                <div class="text-center py-16 text-slate-400 text-xs">
                    <span class="text-4xl block mb-2">👨‍👩‍👧‍👦</span>
                    @if(request()->filled('search'))
                        No family members matched your search.
                    @else
                        No family members added yet. Click "+ Add Family Member" to get started.
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="py-3.5 px-4">Name</th>
                                <th class="py-3.5 px-4">Relationship</th>
                                <th class="py-3.5 px-4">Details / Notes</th>
                                <th class="py-3.5 px-4">Phone</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($members as $m)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-pink-100 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 font-bold flex items-center justify-center text-xs">
                                            {{ strtoupper(substr($m->name, 0, 1)) }}
                                        </div>
                                        <span>{{ $m->name }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($m->relationship)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-pink-50 dark:bg-pink-950/40 text-pink-700 dark:text-pink-300 border border-pink-200/60 dark:border-pink-900/60">
                                                {{ $m->relationship }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">--</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                        {{ $m->details ?: '--' }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400 font-mono">
                                        {{ $m->phone ?: '--' }}
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <button type="button" onclick="openEditFamilyModal({{ json_encode($m) }})" class="mr-2 text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 font-semibold cursor-pointer">
                                            Edit
                                        </button>
                                        <form action="{{ route('family-members.destroy', $m) }}" method="POST" class="inline" onsubmit="return confirm('Remove {{ addslashes($m->name) }} from family members?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-500 font-semibold cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $members->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>
    </div>

    <!-- ADD / EDIT MODAL -->
    <div id="family-member-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target === this) closeFamilyModal()">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 id="family-modal-title" class="font-bold text-base text-slate-900 dark:text-white">Add Family Member</h3>
                <button type="button" onclick="closeFamilyModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer">&times;</button>
            </div>
            <form id="family-form" action="{{ route('family-members.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="_method" id="family-method-input" value="POST">

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Name *</label>
                    <input type="text" name="name" id="family-name-input" required placeholder="e.g. Mother, Father, Brother, Priya" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Relationship (Optional)</label>
                        <input type="text" name="relationship" id="family-rel-input" placeholder="e.g. Mother, Sister" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone (Optional)</label>
                        <input type="text" name="phone" id="family-phone-input" placeholder="e.g. 9876543210" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Details / Notes (Optional)</label>
                    <textarea name="details" id="family-details-input" rows="3" placeholder="Optional details, birthday, notes..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:bg-white focus:ring-2 focus:ring-pink-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeFamilyModal()" class="px-4 py-2.5 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-semibold shadow-md transition cursor-pointer">
                        Save Member
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddFamilyModal() {
            document.getElementById('family-modal-title').textContent = 'Add Family Member';
            document.getElementById('family-form').action = "{{ route('family-members.store') }}";
            document.getElementById('family-method-input').value = 'POST';
            document.getElementById('family-name-input').value = '';
            document.getElementById('family-rel-input').value = '';
            document.getElementById('family-phone-input').value = '';
            document.getElementById('family-details-input').value = '';
            document.getElementById('family-member-modal').classList.remove('hidden');
            document.getElementById('family-name-input').focus();
        }

        function openEditFamilyModal(m) {
            document.getElementById('family-modal-title').textContent = 'Edit Family Member';
            document.getElementById('family-form').action = '/family-members/' + m.id;
            document.getElementById('family-method-input').value = 'PUT';
            document.getElementById('family-name-input').value = m.name || '';
            document.getElementById('family-rel-input').value = m.relationship || '';
            document.getElementById('family-phone-input').value = m.phone || '';
            document.getElementById('family-details-input').value = m.details || '';
            document.getElementById('family-member-modal').classList.remove('hidden');
            document.getElementById('family-name-input').focus();
        }

        function closeFamilyModal() {
            document.getElementById('family-member-modal').classList.add('hidden');
        }
    </script>
</x-app-layout>
