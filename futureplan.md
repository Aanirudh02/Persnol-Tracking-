# Future Plan — LifeTracker

Plan written 2026-10-07. Covers the new modules you asked for, the navigation regroup, and further suggestions.
Bug fixes are tracked separately in [`mediumissue.md`](mediumissue.md).

**Already delivered (commit `6dbc943`):** per-vehicle odometer log with previous reading and "this entry" distance, petrol fill log with previous odometer/distance/mileage, chart click → category breakdown, trip planner fixes, 3 high security fixes.

---

## 1. Navigation regroup (same design, grouped dropdowns)

Desktop sidebar: each group is a collapsible dropdown (`<details>`), auto-opened when one of its pages is active. Mobile: the bottom bar stays (Home · Calendar · + · Finance · More); the **More** drawer shows the same groups as headed sections.

```
Dashboard
General ▾                     Calendar & Timeline · Food · Snacks · Sleep & Wake · Activities · Search

Finance Hub
Statements ▾                  Statements · Take Statement · Classifications · All Expenses
                              Payments & Reconcile · Daily Cash Register · Savings & Funds

Normal Expenses ▾             Expenses · Income (Money Received) · Friends & Splits
                              Credits (I owe) · Debits (owe me) · Notes · Mistakes
                              ── By category (dynamic) ──  top categories as shortcuts → Expenses filtered by category

Personal Expenses ▾           Personal Expenses · Personal Income · Personal Transfers (between us)
                              Personal Credits · Personal Debits
                              (+ suggested: Savings Goals · Subscriptions · Budget · Wishlist — see §6)

Family ▾                      Family Expenses · Family Transfers (who gave to whom) · Family Members

Categories ▾                  Category groups & sub-groups · Expense categories · Personal categories · Income categories

Vehicle ▾                     Vehicles · Odometer & Mileage · Petrol / Fuel · Petrol Statement · Trips · Log Trip · Service log (new)

Parties & People ▾            People (friends / parents / custom roles) · Parties ledger (credit & debit, normal + personal)

Insights ▾                    Analytics · Reports & Export
Settings
```

**Implementation**
- New `x-nav-group` Blade component (`title`, `icon`, `:active`) wrapping `<details>`; reuse `x-nav-link` for items.
- "By category" shortcuts come from a view composer that caches the user's top 6 expense categories (5 min, key per user).
- Delete the unused `resources/views/layouts/app.blade.php` so there is only one nav to maintain.

---

## 2. People module (friends, parents, custom roles)

Re-use the existing `friends` table — it already has a free-text `role` column (`Friend` / `Not a Friend`), and credits, splits and settlements already point at it. That keeps every existing balance working.

| Change | Detail |
|---|---|
| Roles | Preset list: Friend · Parent · Sibling · Spouse · Child · Relative · Colleague · Not a Friend · **Custom** (type your own). Stored in `friends.role`; presets come from lookup options type `person_role` so you can add more in Settings. |
| New columns | `relationship_group` (`friends` / `family` / `work` / `other`), `avatar_color`, `is_archived` |
| Pages | `people.index` — cards grouped by role, filter by role, search; `people.show` — one person's full ledger (see §3) |
| Family Members | Migrate `family_members` rows into `friends` with `relationship_group = family` and `role = relationship`; keep the old route redirecting to `people.index?group=family`. |
| Routes | `Route::resource('people', PeopleController::class)` + `people.archive` |

---

## 3. Parties — credit & debit common to Normal and Personal

- Add `scope` to `credit_debts`: `normal` (default — all existing rows) | `personal`.
- `credits.index` gets a `scope` filter; the nav links for "Personal Credits / Debits" pass `?scope=personal`.
- The **Parties ledger** (`people.show`) shows, per person: open credits, open debits (both scopes, tagged), split balance, transfers given/received, family expenses paid — with one net figure.
- Every query that sums credit/debit must state its scope; add `CreditDebt::scopeNormal()` / `scopePersonal()` and use them in the dashboard and finance hub so personal items never leak into normal totals.

---

## 4. Transfers module (who gave money to whom)

**Table `transfers`**

| column | type | note |
|---|---|---|
| user_id | FK | owner |
| scope | string | `family` (Family Transfers) or `personal` (Personal Transfers — between us) |
| from_person_id | FK friends, nullable | null = **Me** |
| to_person_id | FK friends, nullable | null = **Me** |
| amount | decimal(12,2) | |
| date, time | | |
| payment_method | string | lookup `payment_method` |
| purpose | string | e.g. "Rent share", "Pocket money" |
| reference | string, nullable | UPI ref no. |
| is_returnable | bool | if true, shows as pending until marked returned |
| returned_at | timestamp, nullable | |
| notes, receipt_image | | |

**Pages**
- List with filters (person, direction, month, returnable/pending).
- **Who-owes-whom matrix**: net amount between every pair of people.
- Quick add: "From ▸ To ▸ Amount" with Me pre-selected.
- Optional: "Also record as expense / income" — only when money leaves or enters *your* wallet (from = Me / to = Me).

**Rules**
- `from_person_id` ≠ `to_person_id`; at least one side may be Me.
- Transfers are **not** expenses — they never change expense totals, only wallet balances when Me is involved.

---

## 5. Family Expense module

**Table `family_expenses`**

| column | note |
|---|---|
| user_id, date, time, amount, description, notes, receipt_image | |
| category | lookup type `family_expense_category` (dynamic: Groceries, Rent, Electricity, School fees, Medical, Festival, …) |
| paid_by_person_id | null = Me |
| for_person_id | nullable — null = whole family |
| payment_method | |
| shared_between | JSON list of person ids (optional) for splitting a household bill |
| expense_id | nullable FK — when you paid, optionally mirror into Normal Expenses |

**Pages:** list + monthly summary by category and by member, "who paid how much this month" chart (bar → click → that member's items, same pattern as the dashboard drilldown).

---

## 6. Personal group — what it can contain

| Item | Status | What it does |
|---|---|---|
| Personal Expenses | exists | — |
| **Personal Income** | new | Table `personal_incomes` (date, amount, source, category lookup `personal_income_category`, payment_method, notes). Separate from Income so the tally / dashboard logic of normal income is untouched. |
| **Personal Transfers (between us)** | new | `transfers` with `scope = personal` (§4) |
| **Personal Credits / Debits** | new | `credit_debts.scope = personal` (§3) |
| Savings Goals | suggested | Target amount + date; contributions linked to Savings |
| Subscriptions | suggested | Recurring items (OTT, phone, gym) with next-due reminder; auto-create the personal expense on due date |
| Budget | suggested | Monthly limit per personal category with progress bars and an alert at 80 % / 100 % |
| Wishlist | suggested | Planned purchases with price tracking; "Buy" converts to a personal expense |
| Personal summary | suggested | Income − expenses − transfers out + transfers in = net for the month |

---

## 7. Categories with grouping and sub-grouping

- Add `parent_id` (self-FK) and `sort_order` to `expense_categories`, `personal_expense_categories`, `income_categories`.
- One **Categories** page with a two-level tree (group → sub-category), drag to reorder, archive, colour/icon.
- Pickers render as `<optgroup>` per group.
- Analytics: bar/pie click on a **group** drills into its sub-categories, a second click into the items.
- Normal Expense categories become fully user-defined (already partly dynamic via `CategoryController`); shared system categories stay Admin-only.

---

## 8. Vehicle hub

- `vehicles.show` per vehicle: latest odometer, active cycle, avg km/L, cost/km, fuel spend this month, trips.
- **Service & maintenance log** (new table `vehicle_services`: date, odometer, type — oil change / tyre / service / repair, cost, garage, next due km/date). Reminder when the odometer passes "next due km".
- Documents & renewals: insurance, PUC, RC with expiry reminders.
- Total cost of ownership: fuel + service + insurance per km.

---

## 9. Other suggestions

**Data quality**
- One `Expense::scopeActive()` (excludes archived + sub-items) used by dashboard, analytics, chart and statements so totals always agree.
- Use the `expense_classification` lookup options everywhere (one set of values).

**Features**
- Recurring transactions (rent, EMI, subscriptions) with auto-post.
- Reminders / notifications: credit due dates, birthdays (friends already store DOB), vehicle service, subscription renewals — `AppNotification` model already exists.
- Bank / UPI statement CSV import with duplicate detection.
- Receipt OCR to pre-fill amount/date from a photo.
- PWA: installable, offline quick-add queue that syncs when back online.
- Global search: include personal expenses, credits/debits, savings, statements, transfers.
- Trash / restore view for soft-deleted records.

**Security & ops**
- Two-factor login (private finance app).
- Scheduled database backups (Supabase export) + "Download all my data".
- GitHub Actions: run `php artisan test` + `pint --test` on every push.
- Error monitoring (Sentry / Laravel Nightwatch).

**Trip planner**
- Rename "Trip Planner" → "Log Trip" (it records past trips), or add real planning for future trips.
- "Saved places" table (Home, Office, College) instead of hard-coded landmarks in `OsmMapProvider`.
- Consider the Google provider (already implemented) when reliability matters more than cost.

---

## 10. Suggested order

| Phase | Scope | Size |
|---|---|---|
| 1 | Fix H4–H6 and M1–M7 from `mediumissue.md` | ~1–2 days |
| 2 | Navigation regroup (§1) + `Expense::scopeActive()` | ~1 day |
| 3 | People module + Parties ledger + `credit_debts.scope` (§2, §3) | ~2 days |
| 4 | Transfers (family + personal) (§4) | ~1–2 days |
| 5 | Family Expenses (§5) + Personal Income (§6) | ~2 days |
| 6 | Category groups & sub-groups (§7) | ~1–2 days |
| 7 | Vehicle hub + service log (§8) | ~1–2 days |
| 8 | Reminders, recurring transactions, budgets, import (§9) | ongoing |

Each phase ships with feature tests and a migration that leaves existing data untouched (new columns default to today's behaviour).
