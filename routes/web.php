<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AllExpensesController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditDebtController;
use App\Http\Controllers\DailyBalanceController;
use App\Http\Controllers\DailyRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseClassificationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\FriendSplitController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\MistakeController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OdometerController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonalExpenseController;
use App\Http\Controllers\PetrolController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\ScooterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SleepController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

// Authentication (Private App: Strictly NO Public Registration)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Application Routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:dashboard.view');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data')->middleware('permission:dashboard.view');

    // Daily Life & Prompts
    Route::post('/daily/wakeup', [DailyRecordController::class, 'recordWakeup'])->name('daily.wakeup');
    Route::post('/daily/sleep', [DailyRecordController::class, 'recordSleep'])->name('daily.sleep');
    Route::get('/daily/dismiss/{type}', [DailyRecordController::class, 'dismissPrompt'])->name('daily.dismiss');
    Route::get('/calendar', [DailyRecordController::class, 'calendar'])->name('calendar')->middleware('permission:calendar.view');
    Route::get('/timeline/{date?}', [DailyRecordController::class, 'timeline'])->name('timeline')->middleware('permission:calendar.view');

    // Finance Module
    Route::prefix('finance')->group(function () {
        Route::get('/', [FinanceDashboardController::class, 'index'])->name('finance.index')->middleware('permission:finance.view');

        // All Expenses overview
        Route::get('/all-expenses', [AllExpensesController::class, 'index'])->name('all-expenses.index');

        // Personal Expenses
        Route::get('/personal-expense-groups/unassigned-expenses', [PersonalExpenseController::class, 'unassignedExpenses'])->name('personal-expense-groups.unassigned');
        Route::post('/personal-expense-groups/{personalExpenseGroup}/expenses/attach', [PersonalExpenseController::class, 'attachExpenses'])->name('personal-expense-groups.expenses.attach');
        Route::post('/personal-expenses/{personalExpense}/convert-to-normal', [PersonalExpenseController::class, 'convertToNormal'])->name('personal-expenses.convert-to-normal');
        Route::post('/personal-expenses/group', [PersonalExpenseController::class, 'group'])->name('personal-expenses.group');
        Route::put('/personal-expense-groups/{personalExpenseGroup}', [PersonalExpenseController::class, 'renameGroup'])->name('personal-expense-groups.update');
        Route::delete('/personal-expense-groups/{personalExpenseGroup}', [PersonalExpenseController::class, 'ungroup'])->name('personal-expense-groups.destroy');
        Route::delete('/personal-expense-groups/{personalExpenseGroup}/expenses/{personalExpense}', [PersonalExpenseController::class, 'detachFromGroup'])->name('personal-expense-groups.expenses.detach');
        Route::post('/personal-expenses/{personalExpense}/archive', [PersonalExpenseController::class, 'archive'])->name('personal-expenses.archive');
        Route::post('/personal-expenses/{personalExpense}/restore', [PersonalExpenseController::class, 'restore'])->name('personal-expenses.restore');
        Route::resource('personal-expenses', PersonalExpenseController::class)->names('personal-expenses');

        // Statements Module
        Route::resource('statements', StatementController::class)->names('statements');

        // Expense Classification Module
        Route::get('/classification', [ExpenseClassificationController::class, 'index'])->name('classification.index');
        Route::post('/classification/save', [ExpenseClassificationController::class, 'save'])->name('classification.save');
        Route::get('/classification/print', [ExpenseClassificationController::class, 'printReport'])->name('classification.print');

        // Expenses
        Route::get('/expense-groups/unassigned-expenses', [ExpenseController::class, 'unassignedExpenses'])->name('expense-groups.unassigned');
        Route::post('/expense-groups/{expenseGroup}/expenses/attach', [ExpenseController::class, 'attachExpenses'])->name('expense-groups.expenses.attach');
        Route::get('/expenses/breakdown', [ExpenseController::class, 'breakdown'])->name('expenses.breakdown');
        Route::get('/expenses/calculate-category', [ExpenseController::class, 'calculateByCategory'])->name('expenses.calculate-category');
        Route::resource('expenses', ExpenseController::class)->withTrashed(['show', 'edit', 'update', 'restore', 'destroy'])->names('expenses');
        Route::post('/expenses/group', [ExpenseController::class, 'group'])->name('expenses.group');
        Route::put('/expense-groups/{expenseGroup}', [ExpenseController::class, 'renameGroup'])->name('expense-groups.update');
        Route::delete('/expense-groups/{expenseGroup}', [ExpenseController::class, 'ungroup'])->name('expense-groups.destroy');
        Route::delete('/expense-groups/{expenseGroup}/expenses/{expense}', [ExpenseController::class, 'detachFromGroup'])->name('expense-groups.expenses.detach');
        Route::post('/expenses/{expense}/archive', [ExpenseController::class, 'archive'])->name('expenses.archive')->withTrashed();
        Route::post('/expenses/{expense}/restore', [ExpenseController::class, 'restore'])->name('expenses.restore')->withTrashed();
        Route::put('/expense-groups/{expenseGroup}/expenses/{expense}/payment-method', [ExpenseController::class, 'updateGroupPaymentMethod'])->name('expense-groups.expenses.payment-method');
        Route::post('/expenses/{expense}/link-food', [ExpenseController::class, 'linkFood'])->name('expenses.link-food');

        // Savings Module
        Route::resource('savings', SavingsController::class)->names('savings');
        Route::post('/savings/{saving}/withdraw', [SavingsController::class, 'withdraw'])->name('savings.withdraw');

        // Daily Balances & Cash Register
        Route::get('/daily-balances', [DailyBalanceController::class, 'index'])->name('daily-balances.index');
        Route::get('/daily-balances/today', [DailyBalanceController::class, 'today'])->name('daily-balances.today');
        Route::post('/daily-balances', [DailyBalanceController::class, 'update'])->name('daily-balances.update');
        Route::post('/daily-balances/categories', [DailyBalanceController::class, 'updateCategories'])->name('daily-balances.categories');

        // Income
        Route::resource('income', IncomeController::class)->names('income');
        Route::post('/income/{income}/tally', [IncomeController::class, 'tallyExpense'])->name('income.tally');
        Route::delete('/income/{income}/tally/{tally}', [IncomeController::class, 'untallyExpense'])->name('income.untally');

        // Payments & Reconciliation
        Route::resource('payments', PaymentController::class)->names('payments');
        Route::post('/payments/{payment}/reconcile', [PaymentController::class, 'reconcile'])->name('payments.reconcile')->middleware('permission:payments.reconcile');

        // Friends & Settlements
        Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
        Route::post('/friends/store', [FriendController::class, 'storeFriend'])->name('friends.store');
        Route::post('/friends/transactions', [FriendController::class, 'storeTransaction'])->name('friends.transactions.store');
        Route::post('/friends/{friend}/settle', [FriendController::class, 'settle'])->name('friends.settle');
        Route::resource('friend-splits', FriendSplitController::class)->except(['index', 'show', 'create']);

        // Credits & Debts
        Route::get('/credits', [CreditDebtController::class, 'index'])->name('credits.index');
        Route::post('/credits', [CreditDebtController::class, 'store'])->name('credits.store');
        Route::get('/credits/{creditDebt}/edit', [CreditDebtController::class, 'edit'])->name('credits.edit');
        Route::put('/credits/{creditDebt}', [CreditDebtController::class, 'update'])->name('credits.update');
        Route::delete('/credits/{creditDebt}', [CreditDebtController::class, 'destroy'])->name('credits.destroy');
        Route::post('/credits/{creditDebt}/payments', [CreditDebtController::class, 'addPayment'])->name('credits.payments');
        Route::put('/credits/{creditDebt}/payments/{payment}', [CreditDebtController::class, 'updatePayment'])->name('credits.payments.update');
        Route::delete('/credits/{creditDebt}/payments/{payment}', [CreditDebtController::class, 'deletePayment'])->name('credits.payments.destroy');
        Route::post('/credits/{creditDebt}/record-as-expense', [CreditDebtController::class, 'recordAsExpense'])->name('credits.record-as-expense');
        Route::post('/credits/{creditDebt}/record-as-personal-expense', [CreditDebtController::class, 'recordAsPersonalExpense'])->name('credits.record-as-personal-expense');
        Route::post('/credits/{creditDebt}/record-as-income', [CreditDebtController::class, 'recordAsIncome'])->name('credits.record-as-income');
        Route::post('/credits/{creditDebt}/settle-discounted', [CreditDebtController::class, 'settleDiscounted'])->name('credits.settle-discounted');
        Route::post('/credits/{creditDebt}/settle-no-pay', [CreditDebtController::class, 'settleNoPay'])->name('credits.settle-no-pay');
        Route::post('/credits/{creditDebt}/close', [CreditDebtController::class, 'close'])->name('credits.close');
    });

    // Petrol Statement Route
    Route::get('/petrol/statement', [PetrolController::class, 'statement'])->name('petrol.statement');

    // Categories (user-defined)
    Route::post('/categories/expense', [CategoryController::class, 'storeExpense'])->name('categories.expense.store');
    Route::delete('/categories/expense/{category}', [CategoryController::class, 'destroyExpense'])->name('categories.expense.destroy');
    Route::post('/categories/personal', [CategoryController::class, 'storePersonal'])->name('categories.personal.store');
    Route::put('/categories/personal/{category}', [CategoryController::class, 'updatePersonal'])->name('categories.personal.update');
    Route::delete('/categories/personal/{category}', [CategoryController::class, 'destroyPersonal'])->name('categories.personal.destroy');
    Route::post('/categories/food', [CategoryController::class, 'storeFood'])->name('categories.food.store');
    Route::delete('/categories/food/{category}', [CategoryController::class, 'destroyFood'])->name('categories.food.destroy');
    Route::post('/categories/activity', [CategoryController::class, 'storeActivity'])->name('categories.activity.store');
    Route::delete('/categories/activity/{category}', [CategoryController::class, 'destroyActivity'])->name('categories.activity.destroy');
    Route::post('/categories/{type}/{id}/archive', [SettingController::class, 'archiveCategory'])->name('categories.archive');

    Route::post('/options', [OptionController::class, 'store'])->name('options.store');
    Route::put('/options/{option}', [OptionController::class, 'update'])->name('options.update');
    Route::delete('/options/{option}', [OptionController::class, 'destroy'])->name('options.destroy');
    Route::post('/wallets', [WalletController::class, 'update'])->name('wallets.update');

    Route::get('/geo/search', [GeoController::class, 'search'])->name('geo.search');
    Route::get('/geo/route', [GeoController::class, 'route'])->name('geo.route');
    Route::get('/geo/reverse', [GeoController::class, 'reverse'])->name('geo.reverse');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::resource('family-members', FamilyMemberController::class)->names('family-members');

    Route::get('/food', [FoodController::class, 'index'])->name('food.index')->middleware('permission:food.view');
    Route::post('/food', [FoodController::class, 'store'])->name('food.store')->middleware('permission:food.create');
    Route::put('/food/{food}', [FoodController::class, 'update'])->name('food.update')->middleware('permission:food.edit');
    Route::delete('/food/{food}', [FoodController::class, 'destroy'])->name('food.destroy')->middleware('permission:food.delete');

    Route::get('/sleep', [SleepController::class, 'index'])->name('sleep.index');
    Route::post('/sleep', [SleepController::class, 'store'])->name('sleep.store');

    // Activities
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index')->middleware('permission:activities.view');
    Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store')->middleware('permission:activities.create');
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy')->middleware('permission:activities.delete');

    // Scooter & Geolocation
    Route::get('/scooter', [ScooterController::class, 'index'])->name('scooter.index')->middleware('permission:scooter.view');
    Route::get('/scooter/plan', [ScooterController::class, 'plan'])->name('scooter.plan')->middleware('permission:scooter.create');
    Route::post('/scooter/plan', [ScooterController::class, 'storePlanned'])->name('scooter.plan.store')->middleware('permission:scooter.create');
    Route::get('/scooter/{trip}/edit', [ScooterController::class, 'edit'])->name('scooter.edit')->middleware('permission:scooter.edit');
    Route::put('/scooter/{trip}', [ScooterController::class, 'update'])->name('scooter.update')->middleware('permission:scooter.edit');
    Route::delete('/scooter/{trip}', [ScooterController::class, 'destroy'])->name('scooter.destroy')->middleware('permission:scooter.delete');
    Route::post('/scooter/start', [ScooterController::class, 'startTrip'])->name('scooter.start')->middleware('permission:scooter.create');
    Route::post('/scooter/{trip}/end', [ScooterController::class, 'endTrip'])->name('scooter.end')->middleware('permission:scooter.edit');

    // Petrol
    Route::post('/petrol/{petrol}/link-expense', [PetrolController::class, 'linkExpense'])->name('petrol.link-expense');
    Route::resource('petrol', PetrolController::class)->names('petrol');

    // Odometer & Mileage Cycles
    Route::get('/odometer', [OdometerController::class, 'index'])->name('odometer.index');
    Route::post('/odometer', [OdometerController::class, 'store'])->name('odometer.store');
    Route::get('/odometer/{group}', [OdometerController::class, 'show'])->name('odometer.show');
    Route::put('/odometer/readings/{reading}', [OdometerController::class, 'updateReading'])->name('odometer.readings.update');
    Route::delete('/odometer/readings/{reading}', [OdometerController::class, 'destroyReading'])->name('odometer.readings.destroy');
    Route::put('/odometer/groups/{group}', [OdometerController::class, 'updateGroup'])->name('odometer.groups.update');
    Route::delete('/odometer/groups/{group}', [OdometerController::class, 'destroyGroup'])->name('odometer.groups.destroy');

    // Mistakes & Lessons
    Route::resource('mistakes', MistakeController::class)->names('mistakes');

    // Notes
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('/notes/{note}/pin', [NoteController::class, 'togglePin'])->name('notes.pin');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index')->middleware('permission:analytics.view');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

    // Global Media Upload (Cloudinary)
    Route::post('/media/upload', [\App\Http\Controllers\MediaUploadController::class, 'upload'])->name('media.upload');

    // Global Search
    Route::get('/search', [SearchController::class, 'search'])->name('search');

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('settings.index')->middleware('permission:settings.view');
        Route::get('/export/json', [SettingController::class, 'exportJson'])->name('settings.export.json');
        Route::get('/export/csv', [SettingController::class, 'exportCsv'])->name('settings.export.csv');
        Route::post('/profile', [SettingController::class, 'updateProfile'])->name('settings.profile');
        Route::post('/password', [SettingController::class, 'updatePassword'])->name('settings.password');
        Route::post('/system', [SettingController::class, 'updateSettings'])->name('settings.system');
        Route::post('/test-cloudinary', [SettingController::class, 'testCloudinary'])->name('settings.test-cloudinary');
        Route::post('/resync-friends', [SettingController::class, 'resyncFriends'])->name('settings.resync-friends');
        Route::post('/custom-answer/{question}', [SettingController::class, 'saveCustomAnswer'])->name('settings.custom-answer');
        Route::post('/users', [SettingController::class, 'storeUser'])->name('settings.users')->middleware('role:Admin');
        Route::post('/roles', [SettingController::class, 'storeRole'])->name('settings.roles.store')->middleware('role:Admin');
        Route::post('/roles/{role}/permissions', [SettingController::class, 'updateRolePermissions'])->name('settings.roles.permissions')->middleware('role:Admin');
    });
});

// Fallback image route for storage files when public/storage symlink is missing in container
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*')->name('storage.local');
