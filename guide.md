# Agent Guide — LifeTracker

Read this before changing anything. It says how this codebase works, the traps that already cost time, and how to pick up the open work.

## 1. What this is
Personal finance + life tracker. Laravel 13, PHP 8.4, Blade + Tailwind (no Livewire/Vue), Chart.js, Leaflet.
Local DB: MySQL. Production: PostgreSQL (Supabase). Tests: SQLite in memory.

| Area | Where |
|---|---|
| Routes | `routes/web.php` (everything behind `auth`) |
| Controllers | `app/Http/Controllers` (fat controllers, no Form Requests) |
| Business logic | `app/Services` (`FinanceLinkService`, `CreditDebtLinkService`, `FinanceService`, `WalletService`, …) |
| Active layout | `resources/views/components/app-layout.blade.php` (`<x-app-layout>`). `layouts/app.blade.php` is **unused**. |
| Shared JS | `resources/js/app.js` (trip planner, autocomplete, toasts) |
| Tests | `tests/Feature/*` (PHPUnit) |

## 2. Work loop
1. Read `mediumissue.md` (open bugs) or `futureplan.md` (new features). **Start with the status table at the end of `mediumissue.md`. Never redo anything marked ✅ FIXED.**
2. Find code by file + method name. Line numbers in the docs are out of date.
3. Make the change in the existing style. Read sibling files first.
4. Add or extend a feature test in `tests/Feature`.
5. Run `vendor/bin/pint --dirty --format agent`, then `php artisan test --compact` (about 10 s).
6. Expected: everything passes except `FinanceMultiSplitAndDashboardTest::test_dashboard_period_filter_and_wallet_balances`, which was already failing ("Total Current Balance" text no longer exists). Don't count it as your regression.
7. Commit with a clear message. Don't push unless asked.

## 3. Rules of this codebase
- **Every query is scoped to the user**: `where('user_id', $request->user()->id)`. Shared rows have `user_id = null` and are Admin-only to change.
- **Money**: an expense total is `amount + gst_amount` (use `Expense::totalAmount()`). Expenses with `parent_id` are sub-items and are not counted again. Archived rows (`is_archived`) are excluded from live totals. What *you* spent is `Expense::myShareAmount()`; never subtract friends' payments yourself.
- **Multi-row writes** go in `DB::transaction(...)`.
- **GET requests must not write.** No `create`/`firstOrCreate`/`update` in index/show/create handlers. One-time defaults belong in a migration or the `User::booted()` created hook.
- **Linked records** (credit/debt ↔ expense / personal expense / income / payments):
  - Links are stored in `credit_debt_id` on expenses, personal expenses and incomes, and `expense_id` / `personal_expense_id` / `income_id` on `credit_debt_payments`.
  - Never copy amounts across blindly. Use `CreditDebtLinkService` (`syncFromCreditDebt`, `syncCreditFromRecord`), and only for fields the user ticked in the `<x-linked-sync-prompt>` modal (posted as `sync_linked[]`).
  - A credit/debt must never be expensed for more than its amount. Check `CreditDebt::unexpensedAmount()`.
- **Locks**: check `FinanceService::canEdit($module, $record)` before both edit and delete.
- **Dropdown values** (payment methods, classifications, roles) come from `OptionsService` lookup options, not hard-coded lists.
- **Style**:
  - Curly braces always, typed params and returns, constructor property promotion.
  - Prefer PHPDoc to inline comments.
  - Create files with `php artisan make:* --no-interaction`.
  - Don't add dependencies or new top-level folders.

## 4. Traps that already bit
| Trap | What to do |
|---|---|
| SQLite stores `date` columns as `Y-m-d 00:00:00`, so `whereBetween('date', [d, d])` / `where('date', d)` miss rows in tests | Use `whereDate('date', '>=', $start)->whereDate('date', '<=', $end)` |
| `@json($model->only(['a', 'b']))` breaks because Blade splits the argument on commas | Build the value in `@php` first, then `@json($var)` |
| Some files use CRLF line endings, so `sed` replacements silently don't apply | Use a real editor or the Edit tool and verify the diff |
| `property_exists($model, 'attr')` is always false for Eloquent attributes | Use `$model->attr ?? null` |
| A relation with `orderBy` + `->latest()` keeps the first order | Use `->reorder()->latest('id')` |
| `$validated['optional_field']` throws when the field wasn't posted | Use `$validated['field'] ?? null` |
| Laravel's local disk `'serve' => true` registers a signed `/storage/{path}` route that shadows the app's | Keep `serve => false` (`config/filesystems.php`) |
| Postgres: `LIKE` is case-sensitive and fails on numeric columns | Use `whereLike(..., caseSensitive: false)`; `CAST(x AS TEXT)` for numbers |
| Public OSM/OSRM geocoders are rate limited | Keep calls few and cached (`OsmMapProvider`) |

## 5. Open work, in priority order
Details are in `mediumissue.md`. Each item says when it's done.

| Item | Done when |
|---|---|
| **M2** resync-friends | `settings.resync-friends` route has `role:Admin`; a non-admin POST gets 403 |
| **M7** SVG upload | `svg` removed from `MediaUploadController` allowed types; an SVG upload is rejected |
| **M4/M5** categories | Every category list and `exists:` rule is limited to the user's own and shared categories; a test shows another user's category id is rejected |
| **M6** permissions | Finance routes use `permission:` middleware like the food/activities routes; a user without the permission gets 403 |
| **M3** audit log | Settings shows only your own audit rows, using the `module` / `record_id` columns |
| **M14** voluntary spend | `FinanceService` voluntary sums add `whereNull('parent_id')` and the archived filter; a test with a sub-item isn't double counted |
| **M13** Analytics archived | Analytics totals match the dashboard for the same period |
| **M16** statement drift | The statement footer equals the sum of the rows it shows |
| **M19** transactions | `ExpenseController::update`, credit payment add/update/delete and personal expense store/update run in `DB::transaction` |
| **M21** N+1 queries | Income list uses `withSum`; expense list calls `isEditableByUser` once per request |
| **L1–L22** | One-line fixes listed in the table in `mediumissue.md` |
| **Features** | `futureplan.md` §10 order: navigation → People/Parties → Transfers → Family Expense + Personal Income → Categories tree → Vehicle hub. Each phase gets a migration that leaves existing data untouched, plus feature tests |

## 6. Ask the owner before doing these
- **M1:** should settings be per-user or Admin-only?
- **M20:** which classification list is canonical (`necessary/unwanted/emergency` vs `Necessary/Unnecessary/Luxury/Emergency`)? Existing rows must be migrated to it.
- **New modules:** confirm the table designs in `futureplan.md` before creating migrations.
- Anything that deletes or rewrites existing money rows (make it a dry-run command first, like `finance:repair-credit-links`).

## 7. After deploying any pulled commits
```
php artisan migrate
php artisan finance:repair-credit-links         # dry run, review output
php artisan finance:repair-credit-links --fix   # repair old duplicates / overwritten amounts
npm run build                                   # Docker builds assets automatically
```
