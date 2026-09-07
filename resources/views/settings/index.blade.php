<x-app-layout title="Settings">
    <div class="space-y-8">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Settings</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage your profile, security, and system preferences.</p>
        </div>

        <!-- Profile Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile Information
                </h2>
            </div>
            <form action="{{ route('settings.profile') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                    </div>
                    <div>
                        <label for="phone" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Phone Number</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                    </div>
                    <div>
                        <label for="timezone" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Timezone</label>
                        <select name="timezone" id="timezone" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition">
                            <option value="Asia/Kolkata" {{ $user->timezone === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (IST)</option>
                            <option value="UTC" {{ $user->timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ $user->timezone === 'America/New_York' ? 'selected' : '' }}>America/New_York (EST)</option>
                            <option value="Europe/London" {{ $user->timezone === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT)</option>
                        </select>
                    </div>
                    <div>
                        <label for="currency" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Currency</label>
                        <select name="currency" id="currency" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition">
                            <option value="INR" {{ $user->currency === 'INR' ? 'selected' : '' }}>₹ INR</option>
                            <option value="USD" {{ $user->currency === 'USD' ? 'selected' : '' }}>$ USD</option>
                            <option value="EUR" {{ $user->currency === 'EUR' ? 'selected' : '' }}>€ EUR</option>
                            <option value="GBP" {{ $user->currency === 'GBP' ? 'selected' : '' }}>£ GBP</option>
                        </select>
                    </div>
                    <div>
                        <label for="profile_photo" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Profile Photo</label>
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/50 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition" />
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-500/20 transition active:scale-95">Save Profile</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Sleep & wake reminders</h2>
                <p class="text-sm text-slate-500 mt-1">Morning (default 7:00–9:00): ask last night's sleep + wake time. Night (default 8:50–10:00 PM): sleep reminder.</p>
            </div>
            <form action="{{ route('settings.system') }}" method="POST" class="p-6 grid grid-cols-2 gap-4 text-sm">
                @csrf
                @php $flat = ($settings ?? collect())->flatten(1)->keyBy('key'); @endphp
                <div>
                    <label class="font-semibold text-slate-700">Morning start</label>
                    <input type="time" name="morning_prompt_start" value="{{ $flat['morning_prompt_start']->value ?? '07:00' }}" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Morning end</label>
                    <input type="time" name="morning_prompt_end" value="{{ $flat['morning_prompt_end']->value ?? '09:00' }}" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Night start</label>
                    <input type="time" name="night_prompt_start" value="{{ $flat['night_prompt_start']->value ?? '20:50' }}" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <div>
                    <label class="font-semibold text-slate-700">Night end</label>
                    <input type="time" name="night_prompt_end" value="{{ $flat['night_prompt_end']->value ?? '22:00' }}" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl">
                </div>
                <button class="col-span-2 py-2.5 rounded-xl bg-slate-900 text-white font-semibold">Save reminder windows</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Wallets & balances</h2>
                <p class="text-sm text-slate-500 mt-1">Each active payment method can have its own opening balance and current wallet calculation.</p>
            </div>
            <div class="p-6 space-y-4">
                @foreach($wallets ?? [] as $wallet)
                    <form action="{{ route('wallets.update') }}" method="POST" class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm items-end border border-slate-100 rounded-xl p-4">
                        @csrf
                        <input type="hidden" name="payment_method" value="{{ $wallet->payment_method }}">
                        <div class="sm:col-span-4 font-bold text-slate-900">{{ $wallet->payment_method }}</div>
                        <label class="flex items-center gap-2"><input type="checkbox" name="is_enabled" value="1" @checked($wallet->is_enabled)> Enable</label>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Opening ₹</label>
                            <input type="number" step="0.01" name="opening_balance" value="{{ $wallet->opening_balance }}" class="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">As of</label>
                            <input type="date" name="opening_as_of" value="{{ optional($wallet->opening_as_of)->toDateString() ?? date('Y-m-d') }}" class="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl">
                        </div>
                        <button class="px-3 py-2 rounded-xl bg-slate-900 text-white font-semibold">Save</button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Payment Types</h2>
                <p class="text-sm text-slate-500 mt-1">Manage the dynamic payment methods used across expenses, credits, payments, and dashboard totals.</p>
            </div>
            <div class="p-6 space-y-4">
                <form action="{{ route('options.store') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="type" value="payment_method">
                    <input type="text" name="name" required placeholder="New payment method..." class="flex-1 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Add</button>
                </form>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($paymentMethods as $method)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                            <div>
                                <span class="font-medium text-slate-800">{{ $method['name'] }}</span>
                                @if($method['is_system'])
                                    <span class="ml-2 text-[10px] text-slate-400">System</span>
                                @endif
                            </div>
                            @if(!$method['is_system'])
                                <form action="{{ route('options.destroy', $method['id']) }}" method="POST" onsubmit="return confirm('Delete this payment method?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Delete</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Finance Dashboard</h2>
                <p class="text-sm text-slate-500 mt-1">Choose which sections show on the finance dashboard.</p>
            </div>
            <form action="{{ route('settings.system') }}" method="POST" class="p-6 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                @csrf
                @foreach([
                    'show_wallet_balances' => 'Wallet balances',
                    'show_total_expense' => 'Total expense card',
                    'show_current_balance' => 'Current balance card',
                    'show_expense_by_payment_type' => 'Expense by payment type',
                    'show_expense_by_category' => 'Expense by category',
                    'show_friend_overview' => 'Friend overview',
                ] as $key => $label)
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <input type="checkbox" name="finance_dashboard_sections[{{ $key }}]" value="1" @checked($financeDashboardSections[$key] ?? false)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
                <button class="sm:col-span-2 rounded-xl bg-slate-900 py-2.5 font-semibold text-white">Save dashboard settings</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Food Expense Mapping</h2>
                <p class="text-sm text-slate-500 mt-1">Use explicit categories for auto-created food and snack expenses instead of name-based guesses.</p>
            </div>
            <form action="{{ route('settings.system') }}" method="POST" class="p-6 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @csrf
                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Food default expense category</label>
                    <select name="food_default_expense_category_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="">None</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) $foodDefaultExpenseCategoryId === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block font-semibold text-slate-700">Snack default expense category</label>
                    <select name="snack_default_expense_category_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                        <option value="">None</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) $snackDefaultExpenseCategoryId === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="sm:col-span-2 rounded-xl bg-slate-900 py-2.5 font-semibold text-white">Save food mapping</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Archive categories</h2>
                <p class="text-sm text-slate-500">Archived stay assignable but excluded from totals (expenses & money received).</p>
            </div>
            <div class="p-6 grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <h3 class="font-bold mb-2">Expenses</h3>
                    @foreach($expenseCategories ?? [] as $cat)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                            <span>{{ $cat->name }} @if($cat->is_archived)<span class="text-xs text-slate-400">Archived</span>@endif</span>
                            <form action="{{ route('categories.archive', ['type' => 'expense', 'id' => $cat->id]) }}" method="POST">@csrf
                                <button class="text-xs font-semibold text-slate-600 hover:underline">{{ $cat->is_archived ? 'Unarchive' : 'Archive' }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
                <div>
                    <h3 class="font-bold mb-2">Money received</h3>
                    @foreach($incomeCategories ?? [] as $cat)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                            <span>{{ $cat->name }} @if($cat->is_archived)<span class="text-xs text-slate-400">Archived</span>@endif</span>
                            <form action="{{ route('categories.archive', ['type' => 'income', 'id' => $cat->id]) }}" method="POST">@csrf
                                <button class="text-xs font-semibold text-slate-600 hover:underline">{{ $cat->is_archived ? 'Unarchive' : 'Archive' }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Change Password Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Change Password
                </h2>
            </div>
            <form action="{{ route('settings.password') }}" method="POST" class="p-6 space-y-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="current_password" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">New Password</label>
                        <input type="password" name="password" id="password" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-amber-500/20 transition active:scale-95">Update Password</button>
                </div>
            </form>
        </div>

        <!-- Custom Security Questions -->
        @if($customQuestions->count())
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Security Questions
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @foreach($customQuestions as $question)
                    <form action="{{ route('settings.custom-answer', $question) }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-end">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">{{ $question->question }}</label>
                            <input type="text" name="answer" placeholder="Your answer (encrypted)" value="{{ $question->answers->first()?->decrypted_answer ?? '' }}" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                        </div>
                        <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold transition active:scale-95">Save</button>
                    </form>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Admin: User Management -->
        @if(auth()->user()->isAdmin())
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    User Management
                </h2>
            </div>
            <div class="p-6">
                <!-- Existing Users -->
                @if(is_countable($users) && count($users))
                <div class="mb-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                                <th class="pb-3 pr-4">Name</th>
                                <th class="pb-3 pr-4">Email</th>
                                <th class="pb-3 pr-4">Role</th>
                                <th class="pb-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($users as $u)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white">{{ $u->name }}</td>
                                <td class="py-3 pr-4 text-slate-600 dark:text-slate-400">{{ $u->email }}</td>
                                <td class="py-3 pr-4"><span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 text-xs font-semibold">{{ $u->roles->first()?->name ?? 'None' }}</span></td>
                                <td class="py-3 text-slate-500 dark:text-slate-400">{{ $u->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Add New User -->
                <div class="border-t border-slate-100 dark:border-slate-800 pt-5">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-4">Add New User</h3>
                    <form action="{{ route('settings.users') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        @csrf
                        <input type="text" name="name" placeholder="Name" required class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                        <input type="email" name="email" placeholder="Email" required class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                        <input type="text" name="phone" placeholder="Phone (optional)" class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                        <input type="password" name="password" placeholder="Password" required class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition" />
                        <div class="flex gap-3">
                            <select name="role" required class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white transition">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition active:scale-95 whitespace-nowrap">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Audit Log -->
        @if($auditLogs->count())
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Recent Audit Log
                </h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                            <th class="pb-3 pr-4">User</th>
                            <th class="pb-3 pr-4">Action</th>
                            <th class="pb-3 pr-4">Target</th>
                            <th class="pb-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($auditLogs as $log)
                        <tr>
                            <td class="py-3 pr-4 text-slate-900 dark:text-white font-medium">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="py-3 pr-4"><span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300">{{ $log->action }}</span></td>
                            <td class="py-3 pr-4 text-slate-600 dark:text-slate-400 text-xs">{{ $log->auditable_type }}#{{ $log->auditable_id }}</td>
                            <td class="py-3 text-slate-500 dark:text-slate-400 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Category Management -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                    🏷️ Manage Categories
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Expense categories = money buckets (Food, Snacks, Petrol). Food/Snack categories = meal type on each item (Dinner, Tea/Coffee). Sub-items inherit the parent expense category — do not re-pick it.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Expense Categories -->
                <div>
                    <h3 class="text-sm font-bold text-slate-700 mb-1">💸 Expense Categories</h3>
                    <p class="text-[10px] text-slate-400 mb-3">Money buckets for Finance (Food, Snacks, Petrol…). Used on parent expenses only.</p>
                    <form action="{{ route('categories.expense.store') }}" method="POST" class="flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="name" required placeholder="New category..." class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-indigo-400">
                        <button type="submit" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-500 transition">+ Add</button>
                    </form>
                    <div class="space-y-1.5">
                        @forelse($expenseCategories as $cat)
                            <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-xs font-medium text-slate-700">{{ $cat->icon }} {{ $cat->name }}</span>
                                <form action="{{ route('categories.expense.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-400 hover:text-rose-600 text-xs">✕</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No custom expense categories yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Food Categories -->
                <div>
                    <h3 class="text-sm font-bold text-slate-700 mb-1">🍔 Food / Snack Categories</h3>
                    <p class="text-[10px] text-slate-400 mb-3">Meal types for what you ate (Dinner, Tea/Coffee, Tiffan…). Not money buckets.</p>
                    <form action="{{ route('categories.food.store') }}" method="POST" class="flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="name" required placeholder="New category..." class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-orange-400">
                        <button type="submit" class="px-3 py-2 rounded-xl bg-orange-500 text-white text-xs font-semibold hover:bg-orange-400 transition">+ Add</button>
                    </form>
                    <div class="space-y-1.5">
                        @forelse($foodCategories as $cat)
                            <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-xs font-medium text-slate-700">{{ $cat->icon }} {{ $cat->name }}</span>
                                <form action="{{ route('categories.food.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-400 hover:text-rose-600 text-xs">✕</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No custom food categories yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Activity Categories -->
                <div>
                    <h3 class="text-sm font-bold text-slate-700 mb-3">🎓 Activity Categories</h3>
                    <form action="{{ route('categories.activity.store') }}" method="POST" class="flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="name" required placeholder="New category..." class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-violet-400">
                        <button type="submit" class="px-3 py-2 rounded-xl bg-violet-600 text-white text-xs font-semibold hover:bg-violet-500 transition">+ Add</button>
                    </form>
                    <div class="space-y-1.5">
                        @forelse($activityCategories as $cat)
                            <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-xs font-medium text-slate-700">{{ $cat->icon }} {{ $cat->name }}</span>
                                <form action="{{ route('categories.activity.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-400 hover:text-rose-600 text-xs">✕</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No custom activity categories yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>
