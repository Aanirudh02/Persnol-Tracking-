<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use App\Models\CustomQuestion;
use App\Models\CustomAnswer;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\FoodCategory;
use App\Models\ActivityCategory;
use App\Models\PaymentWallet;
use App\Services\WalletService;
use App\Services\OptionsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class SettingController extends Controller
{
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

        return view('settings.index', compact(
            'user', 'settings', 'customQuestions', 'auditLogs', 'users', 'roles', 'permissions',
            'expenseCategories', 'incomeCategories', 'foodCategories', 'activityCategories', 'wallets', 'friendRoles'
        ));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
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
        ];

        foreach ($data as $key => $val) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                continue;
            }
            if (! auth()->user()->isAdmin() && ! in_array($key, $allowedForAll, true)) {
                continue;
            }
            $setting->update(['value' => $val]);
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
        if (!auth()->user()->isAdmin()) abort(403);

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
}
