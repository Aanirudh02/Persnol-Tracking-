# Open Issues — LifeTracker

Audit of the codebase on 2026-10-07. Every item lists **where**, **what goes wrong**, and **the fix**.
Line numbers refer to the code at commit `6dbc943`.

Status legend: ✅ fixed · 🔴 High (open) · 🟠 Medium · 🟡 Low · 🔍 needs a decision from you before fixing

---

> **Read the status table at the end first.** Items marked ✅ FIXED are done — do not change them again. Line numbers below are from commit `6dbc943` and may have shifted; locate code by file + method name.

## 0. Already fixed in commit `6dbc943`

| # | Problem | Where |
|---|---------|-------|
| ✅ H1 | Global search returned **other users'** friend transactions (`orWhere` outside the `user_id` scope) | `SearchController.php` |
| ✅ H2 | Category archive endpoint had no ownership check — anyone could archive anyone's / shared categories | `SettingController::archiveCategory` |
| ✅ H3 | `/storage/{path}` fallback had no path-traversal guard. It was also shadowed by Laravel's signed `storage.local` route, so the public-image fallback never worked | `routes/web.php`, `config/filesystems.php` |
| ✅ | Trip planner map never rendered — active layout had no `@stack('head')`, so Leaflet never loaded | `components/app-layout.blade.php` |
| ✅ | Editing a trip dropped its stops from the distance and from the record | `ScooterController::update` |
| ✅ | Place suggestions read **every user's** trip history | `OsmMapProvider::searchHistoryPlaces` |
| ✅ | Odometer ending leg stored the whole-cycle distance (`readings()->latest('id')` returned the source reading) | `OdometerController::store` |
| ✅ | Odometer log (search/filter) was computed in the controller but never rendered | `odometer/index.blade.php` |
| ✅ | Odometer store threw "Undefined array key trip_name" when the field was not posted | `OdometerController::store` |
| ✅ | Petrol statement mileage mixed vehicles and reset at the period boundary | `PetrolController::statement` |
| ✅ | Dashboard chart ran one SQL query per day (365 for a year; unbounded custom range) | `DashboardController::chartData` |

---

## 1. High — money logic (✅ all fixed in `ecd48fe` — kept for reference, do not re-fix)

These change stored money values, so they need you to confirm the intended behaviour before they are changed.

### ✅ FIXED (`ecd48fe`) — H4 Expense list subtracts the friend's payment twice
- **Where:** `resources/views/finance/expenses/index.blade.php:305-306`, `ExpenseController.php:280-287` and `726-733`
- **Problem:** for split bills the controller already saves only *your* share in `amount`. The list then does `$myPaid = displayAmount - friendsPaid`, so "your spend" shows ₹0 or too low.
- **Example:** bill ₹90, you paid ₹20, friend ₹70 → `amount` = 20 → list shows `max(0, 20 - 70)` = ₹0.
- **Fix:** for new-style rows show `totalAmount()` as your spend; only subtract for legacy rows where `amount` is the full bill.

### ✅ FIXED (`ecd48fe`) — H5 Credit created with "link as expense" is counted twice on close
- **Where:** `CreditDebtController.php:99-133` and `736-758`
- **Problem:** storing a *credit* with `link_as_expense` creates a full-amount "Credit Repayment" expense but records no payment. `close()` then creates a second expense for the remaining amount and overwrites `linked_expense_id`.
- **Fix:** record a matching payment when linking, or make `close()` reuse/adjust the already-linked expense.

### ✅ FIXED (`ecd48fe`) — H6 Editing a credit/debt overwrites partial linked amounts
- **Where:** `CreditDebtController.php:182-215`
- **Problem:** `update()` sets linked Expense / PersonalExpense / Income `amount` to the full credit amount, even when they were filed for a partial amount (`recordAsIncome`, `recordAsExpense`, `close()` remainder). Editing just the description rewrites them.
- **Fix:** sync only date / payment method, or sync the amount only when the link covered the full amount.

---

## 2. 🟠 Medium

### Security & access

**M1. Global settings writable by any user**
- **Where:** `SettingController.php:207-274`, `DailyBalanceController.php:369-379`
- **Problem:** `Setting` has no `user_id`; any non-admin can change `app_timezone`, `allow_statement_deletion`, `finance_dashboard_sections`, `daily_register_categories` for everyone. Values are not validated.
- **Fix:** make these per-user settings or restrict to `role:Admin`; validate values (`timezone` rule etc.).

**M2. Any user can run the friends resync**
- **Where:** `routes/web.php` (`settings.resync-friends`), `SettingController.php:374-395`
- **Problem:** `?force=1` runs `finance:resync-friends`, which rewrites **every user's** splits and transactions.
- **Fix:** add `->middleware('role:Admin')`.

**M3. Audit log shows every user's activity**
- **Where:** `SettingController.php:126`, `settings/index.blade.php:593`
- **Problem:** not scoped to `user_id`; the view prints `auditable_type#auditable_id` but the columns are `module` / `record_id`, so it renders `#`.
- **Fix:** scope to the user and use the right columns.

**M4. Category lists are not scoped to the user**
- **Where:** `ExpenseCategory::query()` in `ExpenseController.php:90,229,675`, `StatementController.php:36,84`, `ExpenseClassificationController.php:77`; `PersonalExpenseCategory` in `PersonalExpenseController.php:96,119,130`; `IncomeCategory::all()` in `IncomeController.php:41,49,104`; `FoodCategory` `FoodController.php:57`; `MistakeCategory::all()` `MistakeController.php:138,145,215`
- **Problem:** dropdowns show other users' categories; `exists:` rules accept another user's category id.
- **Fix:** `whereNull('user_id')->orWhere('user_id', me)` and `Rule::exists(...)->where(...)`.

**M5. Shared personal categories editable by anyone**
- **Where:** `CategoryController.php:129,150`
- **Problem:** `if ($category->user_id && …)` skips the check when `user_id` is null, so anyone can rename/delete shared categories.
- **Fix:** shared categories Admin-only (same rule now used for archive).

**M6. Most finance routes have no permission middleware**
- **Where:** `routes/web.php` — expenses, income, payments, savings, credits, mistakes, notes, reports, settings export/POST
- **Problem:** seeders define `expenses.*`, `income.*`, `payments.*`, `mistakes.*` permissions but only `finance.index` and `payments.reconcile` use them.
- **Fix:** apply `permission:` middleware like the food/activities routes.

**M7. SVG uploads allowed (stored XSS)**
- **Where:** `MediaUploadController.php:113-114`
- **Problem:** SVGs served same-origin via `/storage` can run script.
- **Fix:** drop `svg` from allowed types.

### Money correctness

**✅ FIXED (`069d0f7`) — M8. Locked/reconciled records can still be deleted**
- **Where:** `ExpenseController::destroy` (845), `IncomeController::destroy` (262), `PaymentController::destroy` (224)
- **Fix:** run the same `FinanceService::canEdit` check used for edit.

**✅ FIXED (`069d0f7`, existing orphans not cleaned) — M9. Orphaned income tallies inflate "tallied" totals**
- **Where:** `IncomeController.php:269`, `Expense::totalTalliedAmount()` (`Expense.php:110-117`), `DashboardController.php` (tally total)
- **Problem:** soft-deleting an Income/Expense leaves its `IncomeExpenseTally` rows, which are still counted.
- **Fix:** delete tallies on delete, or only count tallies whose parents are alive.

**✅ FIXED (`069d0f7`, single-friend splits) — M10. Restoring an expense loses its friend link**
- **Where:** `ExpenseController.php:821, 830-843` (restore) vs `864` (destroy)
- **Fix:** call `syncExpenseFriendLink` on restore.

**✅ FIXED (`069d0f7`) — M11. Deleting an expense-linked friend split leaves the expense dangling**
- **Where:** `FriendSplitController.php:53-62`
- **Fix:** block delete when `expense_id` is set (as `update` already does at line 105).

**✅ FIXED (`069d0f7`) — M12. Personal expenses double-counted on the dashboard**
- **Where:** `DashboardController.php:88-102`
- **Problem:** with `show_personal_expenses_in_dashboard` on, personal expenses that were "recorded as normal" (they also created an `Expense`) are counted twice; archived personal rows are included.
- **Fix:** exclude rows with `expense_id` set and archived rows. (The chart builder added in `6dbc943` already excludes archived rows.)

**🟡 PARTLY FIXED (dashboard only; Analytics open) — M13. Archived expenses handled inconsistently**
- **Where:** `DashboardController.php:99` (period total) vs the weekly/monthly cards; `AnalyticsController` excludes archived nowhere
- **Problem:** the same period shows different totals on the dashboard cards, the chart and Analytics.
- **Fix:** one shared scope, e.g. `Expense::scopeActive()`, used everywhere.

**M14. Voluntary-spend totals include sub-items**
- **Where:** `FinanceService.php:132-137, 179-183`
- **Fix:** add `whereNull('parent_id')` and the archived filter.

**✅ FIXED (`ecd48fe`) — M15. Friend-paid expenses deducted from your cash register**
- **Where:** `DailyBalanceController.php:175`, `DailyRegisterService.php:133`
- **Problem:** filters `paid_by != 'friend'`, but `paid_by` holds names/"Me"; the type lives in `paid_by_type`.
- **Fix:** filter on `paid_by_type`.

**M16. Statement totals drift from their rows**
- **Where:** `statements/show.blade.php:55,235`, `StatementController.php:166,176,209-253`
- **Problem:** footer shows stored `total_amount` while rows are re-queried live; the `else` branch at 226-253 is dead for new statements.
- **Fix:** sum the rows shown (or show "snapshot vs live").

**✅ FIXED (`ecd48fe`) — M17. Food entries and auto-created expenses get out of sync**
- **Where:** `FoodController.php:158-224, 282-306`
- **Problem:** no transaction; update/destroy ignore the auto-created expense, leaving stale/orphan expenses that are still counted.
- **Fix:** wrap in a transaction and update/delete expenses created via `auto_create_expense`.

**✅ FIXED (`ecd48fe`) — M18. Deleting a credit/debt orphans linked personal expense and income**
- **Where:** `CreditDebtController.php:284-289`
- **Fix:** handle all three `linked_*_id` links.

**🟡 PARTLY FIXED (see status table) — M19. Missing DB transactions on multi-write operations**
- **Where:** `ExpenseController::update` (759-781), `ExpenseController::store` link sync (461), `CreditDebtController` destroy/addPayment/updatePayment/deletePayment (284-373), `PersonalExpenseController` store/update + `syncNormalExpense` (176-239)
- **Fix:** wrap each in `DB::transaction`.

**M20. Classification values don't match between modules**
- **Where:** expense/personal validation `necessary,unwanted,emergency` (`ExpenseController.php:923`, `PersonalExpenseController.php:171`); credits `necessary,discretionary,luxury` (`CreditDebtController.php:483`); classification page uses lookup names `Necessary/Unnecessary/Luxury/Emergency`, case-sensitive (`ExpenseClassificationController.php:105`)
- **Problem:** report splits "necessary" vs "Necessary"; editing an expense classified "Luxury" silently resets it to "necessary".
- **Fix:** use the `expense_classification` lookup options everywhere.

### Performance

**M21. N+1 queries on list pages**
- `finance/income/index.blade.php:51` — `talliedAmount()` per row → use `withSum('tallies', 'allocated_amount')`
- `finance/expenses/index.blade.php:310` — `isEditableByUser()` per row runs a roles query + uncached `Setting::getVal` → compute once per request
- `expenses/show.blade.php:92-93` — `subItemsExplainedTotal()` called 3× and compared with `amount` without GST → compute once, compare with `totalAmount()`

**M22. Odometer/Petrol pages: per-vehicle queries**
- **Where:** `OdometerController::index` (vehicle snapshots), `PetrolController::index`
- **Problem:** 2 queries per vehicle for snapshots — fine for 2-3 vehicles, worth a single grouped query if the list grows.

### Database portability (production is PostgreSQL / Supabase)

**M23. LIKE on numeric columns and case-sensitive search**
- **Where:** `ExpenseController.php:517`, `PersonalExpenseController.php:289` (`orWhere('amount','like',…)`), plus every `like` search (Search, Notes, Family…)
- **Problem:** fails on numeric columns in Postgres; `LIKE` is case-sensitive there.
- **Fix:** `CAST(amount AS TEXT)` and `whereLike(..., caseSensitive: false)` / `ilike`.

### Side effects on GET

**✅ FIXED (`ecd48fe`) — M24. GET requests that write data**
- `PetrolController::index` runs `Vehicle::firstOrCreate(['name' => 'TVS Pep+'])` on **every visit** — a deleted or renamed TVS Pep+ is silently re-created, and all unassigned fuel rows are reassigned.
- `DashboardController.php:64-74` creates a `DailyRecord` for any `?date=`.
- `ExpenseController::create` (236-248) creates a shared "Snacks" category.
- `GET /daily/dismiss/{type}` changes state without CSRF.
- **Fix:** move to a one-off migration/seeder or POST routes.

---

## 3. 🟡 Low

| # | Where | Problem | Fix |
|---|-------|---------|-----|
| L1 | `routes/web.php` resources | `personal-expenses` create/show/edit, `statements` edit, `savings` create/show, `payments` show, `family-members` create/show/edit have no controller method → 500 if opened | `->only([...])` |
| L2 | `CreditDebtController::updateStatus` (378-404) | No route — dead code | Remove or route it |
| L3 | `DailyBalanceController::index` | `$selectedDayData` overwritten; `totMonth*`, `runningCategoryBalances`, `$allMethodNames` unused | Remove |
| L4 | `DashboardController.php:115` | `$wallets` computed but never rendered | Remove or show it |
| L5 | `ExpenseController` index 98-149 | `'owned'` always equals `'total'`; archived-tab totals lack `withTrashed()` | Fix the formulas |
| L6 | `ExpenseController.php:1381`, `PersonalExpenseController.php:477`, `IncomeController.php:35`, `DailyBalanceController.php:53`, `DashboardController.php:50,148`, `StatementController.php:316` | Unvalidated `month` passed to `Carbon::parse` → 500 on bad input | `date_format:Y-m` |
| L7 | `SavingsController.php:21` | `$request->string('status') !== 'all'` compares an object with a string → `?status=all` returns nothing | `->toString()` |
| L8 | `MistakeController.php:176` | `$validated['category_id']` undefined when omitted | `?? null` |
| L9 | Income/Payment/Mistake/PersonalExpense validation | `'time' => 'nullable'` with no format | `date_format:H:i` |
| L10 | `ReportController.php:98-106,146-157` | `module` and custom dates unvalidated; raw value in `Content-Disposition` filename | Validate / whitelist |
| L11 | `SettingController.php:403` | `env('CLOUDINARY_URL')` is null once config is cached | Read from `config()` |
| L12 | `IncomeController.php:225` | Tally can be over-allocated by ₹0.01, no row lock | Exact max + `lockForUpdate` |
| L13 | `FinanceService::reconcilePayment` (49) | Already-reconciled payments can be reconciled again | Guard |
| L14 | `finance/daily_balances/index.blade.php:477,574,588,632` | `addslashes`/raw values inside JS strings break on newlines/quotes | Use `@js()` |
| L15 | `CreditDebtController.php:589-619, 796` | `settleDiscounted` doesn't check settled + discount ≤ remaining; `status` accepts any string | Validate |
| L16 | `ExpenseClassificationController.php:44-67` | `domain=all` applies one `category_id` to two tables; unpaginated page can exceed `max_input_vars` (1000) on save | Separate filters; paginate |
| L17 | `resources/views/layouts/app.blade.php`, `welcome.blade.php` | Unused (all pages use `components/app-layout`) — edits to it have no effect | Delete |
| L18 | `scooter/petrol/index.blade.php` add modal | No `enctype` / file input although `store` accepts `receipt_image`; no time field | Add both |
| L19 | `ScooterController::storePlanned` | "Trip Planner" can't plan a future trip (`date before_or_equal:today`); start/end time both "now", duration 0 | Rename to "Log Trip" or support planned trips |
| L20 | `OsmMapProvider::localLandmarks` | Hard-coded Coimbatore landmarks; "414-A Tex Park Road" and "IMIK Technologies" share identical coordinates | Move to a user-editable "Saved Places" table |
| L21 | `OsmMapProvider` | Uses public Nominatim/OSRM demo servers (1 req/s policy, no SLA) — the main reason search/route feel flaky | Cache harder, or use a keyed provider (Google provider already exists) |
| L22 | `tests/Feature/FinanceMultiSplitAndDashboardTest.php:123` | Test expects "Total Current Balance" on the dashboard; text no longer exists → suite has 1 failing test | Update the test or restore the label |

---

## Status update — 2026-10-07 (later commits)

| Item | Status | Commit |
|---|---|---|
| H4 Expense list subtracts friend's payment twice | ✅ fixed (old rows fixed automatically — display logic) | `ecd48fe` |
| H5 Credit "link as expense" counted twice on close | ✅ fixed; old duplicates: run `php artisan finance:repair-credit-links` (dry run) then `--fix` | `ecd48fe` |
| H6 Editing credit overwrites linked amounts | ✅ fixed; now asks "update both places?" for payment method / date / amount, both directions | `ecd48fe` |
| M8 Locked records deletable | ✅ fixed for expense / income / payment. Also fixed: the lock check itself never worked (`property_exists` on Eloquent) | next commit |
| M9 Orphaned income tallies | ✅ tallies removed when an income or expense is deleted (existing orphans are not cleaned up yet) | next commit |
| M10 Restored expense loses friend link | ✅ single-friend splits rebuilt on restore (multi-friend splits still need re-entry) | next commit |
| M11 Deleting expense-linked split | ✅ blocked | next commit |
| M12 Personal expenses double-counted on dashboard | ✅ excludes rows already filed as normal expense and archived rows | next commit |
| M13 Archived handling inconsistent | 🟡 dashboard period total fixed; Analytics still includes archived | next commit |
| M15 Cash register deducts friend-paid expenses | ✅ fixed | `ecd48fe` |
| M17 Food ↔ auto-created expense out of sync | ✅ fixed | `ecd48fe` |
| M18 Credit delete orphans linked records | ✅ fixed | `ecd48fe` |
| M19 Missing transactions | 🟡 credit delete, expense delete, income delete, food store/update/destroy done; others open | `ecd48fe` + next |
| M24 GET requests writing data | ✅ fixed (dashboard, prompts, petrol, categories, classifications, wallets; dismiss is POST) | `ecd48fe` |
| M14, M16, M20 and the security items M1–M7 | ⏳ still open | — |

**After pulling:** run `php artisan migrate`, then `php artisan finance:repair-credit-links` (review) and `php artisan finance:repair-credit-links --fix`.
