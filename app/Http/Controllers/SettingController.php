<?php

namespace App\Http\Controllers;

use App\Models\ActivityCategory;
use App\Models\AuditLog;
use App\Models\CustomAnswer;
use App\Models\CustomQuestion;
use App\Models\ExpenseCategory;
use App\Models\FoodCategory;
use App\Models\IncomeCategory;
use App\Models\PaymentWallet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\OptionsService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingController extends Controller
{
    /**
     * Download the authenticated user's application data as a portable JSON backup.
     */
    public function exportJson(Request $request): StreamedResponse
    {
        $user = $request->user();
        $excludedTables = [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'password_reset_tokens',
            'sessions',
        ];
        $tables = [];

        foreach (Schema::getTables() as $tableInfo) {
            $table = $tableInfo['name'] ?? $tableInfo['tablename'] ?? null;
            if (! $table || in_array($table, $excludedTables, true)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (in_array('user_id', $columns, true)) {
                $tables[$table] = DB::table($table)
                    ->where('user_id', $user->id)
                    ->get()
                    ->map(fn ($row): array => (array) $row)
                    ->all();
            }
        }

        $payload = [
            'format' => 'lifetracker-json-backup',
            'version' => 1,
            'exported_at' => Carbon::now()->toIso8601String(),
            'database_driver' => DB::connection()->getDriverName(),
            'user' => collect($user->toArray())->except([
                'password',
                'remember_token',
            ])->all(),
            'tables' => $tables,
        ];

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, 'lifetracker-backup-'.now()->format('Y-m-d-His').'.json', [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    /**
     * Download a spreadsheet-friendly CSV for a selected personal data module.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'in:expenses,income,petrol,friends,notes,trips'],
        ]);

        $table = match ($validated['module']) {
            'expenses' => 'expenses',
            'income' => 'incomes',
            'petrol' => 'fuel_entries',
            'friends' => 'friends',
            'notes' => 'notes',
            'trips' => 'scooter_trips',
        };
        $rows = DB::table($table)->where('user_id', $request->user()->id)->get()->map(fn ($row): array => (array) $row);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys($rows->first()));
                foreach ($rows as $row) {
                    fputcsv($handle, array_map(static fn ($value): string => is_scalar($value) || $value === null ? (string) $value : json_encode($value), $row));
                }
            }
            fclose($handle);
        }, 'lifetracker-'.$validated['module'].'-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function index(Request $request, WalletService $walletService, OptionsService $options)
    {
        $user = $request->user();
        $settings = Setting::all()->groupBy('group');

        $customQuestions = CustomQuestion::where('is_active', true)->with(['answers' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])->get();

        $auditLogs = AuditLog::with('user')->orderByDesc('created_at')->take(20)->get();

        $users = $user->isAdmin() ? User::with('roles')->get() : [];
        $roles = $user->isAdmin() ? Role::with('permissions')->get() : [];
        $permissions = $user->isAdmin() ? Permission::all() : [];

        $expenseCategories = ExpenseCategory::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->orderBy('name')->get();
        $incomeCategories = IncomeCategory::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->orderBy('name')->get();
        $foodCategories = FoodCategory::where('user_id', $user->id)->orderBy('name')->get();
        $activityCategories = ActivityCategory::where('user_id', $user->id)->orderBy('name')->get();

        $walletService->ensureDefaults($user->id);
        $wallets = PaymentWallet::where('user_id', $user->id)->orderBy('payment_method')->get();
        $friendRoles = $options->names('friend_role');
        $paymentMethods = $options->for('payment_method', $user->id);
        $financeDashboardSections = array_merge([
            'show_wallet_balances' => true,
            'show_total_expense' => true,
            'show_current_balance' => true,
            'show_expense_by_payment_type' => true,
            'show_expense_by_category' => true,
            'show_friend_overview' => true,
        ], Setting::getVal('finance_dashboard_sections', []));
        $foodDefaultExpenseCategoryId = Setting::getVal('food_default_expense_category_id');
        $snackDefaultExpenseCategoryId = Setting::getVal('snack_default_expense_category_id');
        $friendsResyncedAt = Setting::getVal('friends_resynced_at');

        return view('settings.index', compact(
            'user', 'settings', 'customQuestions', 'auditLogs', 'users', 'roles', 'permissions',
            'expenseCategories', 'incomeCategories', 'foodCategories', 'activityCategories', 'wallets', 'friendRoles',
            'paymentMethods', 'financeDashboardSections', 'foodDefaultExpenseCategoryId', 'snackDefaultExpenseCategoryId',
            'friendsResyncedAt'
        ));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,'.$user->id,
            'timezone' => 'required|string',
            'currency' => 'required|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        $allowedForAll = [
            'morning_prompt_start',
            'morning_prompt_end',
            'night_prompt_start',
            'night_prompt_end',
            'debit_wallet_for_voluntary',
            'weekly_petrol_reminder_enabled',
            'weekly_petrol_reminder_day',
            'weekly_petrol_reminder_time',
            'app_timezone',
            'app_currency',
            'currency_symbol',
            'food_default_expense_category_id',
            'snack_default_expense_category_id',
            'finance_dashboard_sections',
        ];

        foreach ($data as $key => $val) {
            $setting = Setting::where('key', $key)->first();
            if (! auth()->user()->isAdmin() && ! in_array($key, $allowedForAll, true)) {
                continue;
            }

            $storedValue = is_array($val) ? json_encode($val) : $val;
            if ($setting) {
                $setting->update(['value' => $storedValue]);
            } elseif (in_array($key, $allowedForAll, true)) {
                Setting::create([
                    'key' => $key,
                    'value' => $storedValue,
                    'type' => is_array($val) ? 'json' : 'string',
                    'group' => 'finance',
                    'description' => $key,
                ]);
            }
        }

        return back()->with('success', 'Settings updated successfully!');
    }

    public function archiveCategory(Request $request, string $type, int $id)
    {
        $model = match ($type) {
            'expense' => ExpenseCategory::findOrFail($id),
            'income' => IncomeCategory::findOrFail($id),
            default => abort(404),
        };

        $model->update(['is_archived' => ! $model->is_archived]);

        return back()->with('success', ($model->is_archived ? 'Archived' : 'Unarchived').': '.$model->name);
    }

    public function storeRole(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return back()->with('success', 'Role created.');
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return back()->with('success', 'Role permissions updated.');
    }

    public function saveCustomAnswer(Request $request, CustomQuestion $question)
    {
        $request->validate(['answer' => 'required|string']);

        $user = $request->user();

        $answerRecord = CustomAnswer::firstOrNew([
            'user_id' => $user->id,
            'custom_question_id' => $question->id,
        ]);

        $answerRecord->answer_encrypted = Crypt::encryptString($request->answer);
        $answerRecord->save();

        return back()->with('success', "Answer saved securely for: {$question->question}");
    }

    public function storeUser(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $newUser->roles()->sync([$role->id]);
        }

        return back()->with('success', "User {$newUser->name} created successfully!");
    }

    public function resyncFriends(Request $request)
    {
        $alreadyResynced = Setting::getVal('friends_resynced_at');
        if ($alreadyResynced && ! $request->boolean('force')) {
            return back()->with('info', 'Friend transactions have already been resynced on '.$alreadyResynced.'.');
        }

        Artisan::call('finance:resync-friends');
        $output = Artisan::output();

        Setting::updateOrCreate(
            ['key' => 'friends_resynced_at'],
            [
                'value' => now()->toDateTimeString(),
                'type' => 'string',
                'group' => 'system',
                'description' => 'Timestamp of last successful friend data resync execution',
            ]
        );

        return back()->with('success', 'Friend balances and split records successfully resynced!')->with('resync_output', $output);
    }
}
