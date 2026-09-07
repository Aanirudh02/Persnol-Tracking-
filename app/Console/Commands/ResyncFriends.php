<?php

namespace App\Console\Commands;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\FriendSplit;
use App\Models\FriendTransaction;
use App\Services\FinanceLinkService;
use App\Services\FriendTransactionProjector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResyncFriends extends Command
{
    protected $signature = 'finance:resync-friends {--dry-run : Report changes without modifying data}';

    protected $description = 'Backfill friend splits and rebuild derived friend transaction projections safely.';

    public function handle(FinanceLinkService $linkService, FriendTransactionProjector $projector): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changes = [
            'created_splits' => 0,
            'updated_splits' => 0,
            'created_projections' => 0,
            'updated_projections' => 0,
            'deleted_projections' => 0,
        ];
        $ambiguous = [];

        $runner = function () use ($dryRun, $linkService, $projector, &$changes, &$ambiguous): void {
            Expense::query()
                ->with('friendSplit')
                ->where(function ($query) {
                    $query->whereNotNull('split_with_friend_id')
                        ->orWhere(function ($nested) {
                            $nested->where('paid_by_type', 'friend')->whereNotNull('paid_by_friend_id');
                        });
                })
                ->orderBy('id')
                ->chunkById(100, function ($expenses) use ($dryRun, $linkService, &$changes, &$ambiguous): void {
                    foreach ($expenses as $expense) {
                        if ($expense->expense_group_id) {
                            $ambiguous[] = "Expense #{$expense->id}: grouped expense carries legacy friend split fields.";

                            continue;
                        }

                        $total = $expense->totalAmount();
                        $myShare = $expense->split_my_share !== null ? (float) $expense->split_my_share : null;
                        $friendShare = $expense->split_friend_share !== null ? (float) $expense->split_friend_share : null;

                        if ($expense->split_with_friend_id) {
                            if ($myShare === null && $friendShare === null) {
                                $myShare = round($total / 2, 2);
                                $friendShare = round($total - $myShare, 2);
                            }
                            if ($myShare === null || $friendShare === null) {
                                $ambiguous[] = "Expense #{$expense->id}: split shares are incomplete.";

                                continue;
                            }
                        } else {
                            $myShare = $total;
                            $friendShare = 0;
                        }

                        $paidByMode = match ($expense->paid_by_type) {
                            'friend' => 'friend',
                            'me', null, '' => 'me',
                            default => null,
                        };

                        if ($paidByMode === null) {
                            $ambiguous[] = "Expense #{$expense->id}: legacy paid_by_type `{$expense->paid_by_type}` cannot be inferred safely.";

                            continue;
                        }

                        $payload = $linkService->expenseSplitPayload($expense, [
                            'friend_id' => $expense->split_with_friend_id ?: $expense->paid_by_friend_id,
                            'my_share' => $myShare,
                            'friend_share' => $friendShare,
                            'paid_by_mode' => $paidByMode,
                            'paid_by_me_amount' => $paidByMode === 'me' ? $total : 0,
                            'paid_by_friend_amount' => $paidByMode === 'friend' ? $total : 0,
                            'notes' => $expense->notes,
                        ]);

                        if ($payload === null) {
                            continue;
                        }

                        $existing = FriendSplit::query()->where('expense_id', $expense->id)->first();
                        if ($existing === null) {
                            $changes['created_splits']++;
                            if (! $dryRun) {
                                FriendSplit::query()->create([...$payload, 'expense_id' => $expense->id]);
                            }
                        } elseif ($this->splitNeedsUpdate($existing, $payload)) {
                            $changes['updated_splits']++;
                            if (! $dryRun) {
                                $existing->update($payload);
                            }
                        }
                    }
                });

            FriendTransaction::query()
                ->whereNull('source_type')
                ->orderBy('id')
                ->chunkById(100, function ($transactions) use ($dryRun, &$changes, &$ambiguous): void {
                    foreach ($transactions as $transaction) {
                        $referencedByExpense = Expense::query()->where('friend_transaction_id', $transaction->id)->exists();
                        $referencedByCredit = CreditDebt::query()->where('friend_transaction_id', $transaction->id)->exists();
                        if ($referencedByExpense || $referencedByCredit) {
                            continue;
                        }

                        if (FriendSplit::query()->where('legacy_friend_transaction_id', $transaction->id)->exists()) {
                            continue;
                        }

                        $payload = $this->payloadFromLegacyTransaction($transaction);
                        if ($payload === null) {
                            $ambiguous[] = "FriendTransaction #{$transaction->id}: unsupported legacy type.";

                            continue;
                        }

                        $changes['created_splits']++;
                        if (! $dryRun) {
                            FriendSplit::query()->create($payload);
                        }
                    }
                });

            $desiredBySource = [];
            foreach (FriendSplit::query()->orderBy('id')->get() as $split) {
                $desiredBySource['friend_split:'.$split->id] = $projector->payloadForFriendSplit($split);
            }
            foreach (CreditDebt::query()->orderBy('id')->get() as $creditDebt) {
                $payload = $projector->payloadForCreditDebt($creditDebt);
                if ($payload !== null) {
                    $desiredBySource['credit_debt:'.$creditDebt->id] = $payload;
                }
            }

            $currentBySource = FriendTransaction::query()
                ->whereNotNull('source_type')
                ->get()
                ->keyBy(fn ($transaction) => $transaction->source_type.':'.$transaction->source_id);

            foreach ($desiredBySource as $sourceKey => $payload) {
                $current = $currentBySource->get($sourceKey);
                if ($current === null) {
                    $changes['created_projections']++;
                    if (! $dryRun) {
                        FriendTransaction::query()->create($payload);
                    }

                    continue;
                }

                if ($this->projectionNeedsUpdate($current, $payload)) {
                    $changes['updated_projections']++;
                    if (! $dryRun) {
                        $current->update($payload);
                    }
                }
            }

            foreach ($currentBySource as $sourceKey => $current) {
                if (! array_key_exists($sourceKey, $desiredBySource)) {
                    $changes['deleted_projections']++;
                    if (! $dryRun) {
                        $current->delete();
                    }
                }
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        foreach ($changes as $label => $count) {
            $this->line(str_replace('_', ' ', ucfirst($label)).": {$count}");
        }

        if ($ambiguous !== []) {
            $this->warn('Ambiguous records:');
            foreach ($ambiguous as $line) {
                $this->line('- '.$line);
            }
        } else {
            $this->info('No ambiguous records detected.');
        }

        $this->info($dryRun ? 'Dry run complete. No data was changed.' : 'Friend resync complete.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function splitNeedsUpdate(FriendSplit $existing, array $payload): bool
    {
        foreach ($payload as $key => $value) {
            if ((string) $existing->{$key} !== (string) $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payloadFromLegacyTransaction(FriendTransaction $transaction): ?array
    {
        $total = round((float) $transaction->total_amount, 2);
        $paidByMe = $transaction->paid_by_me ? $total : 0.0;
        $paidByFriend = $transaction->paid_by_me ? 0.0 : $total;

        if ($transaction->type === 'paid_for_friend') {
            return [
                'user_id' => $transaction->user_id,
                'friend_id' => $transaction->friend_id,
                'legacy_friend_transaction_id' => $transaction->id,
                'description' => $transaction->description,
                'date' => $transaction->date,
                'payment_method' => $transaction->payment_method,
                'total_amount' => $total,
                'my_share' => 0,
                'friend_share' => $total,
                'paid_by_me_amount' => $total,
                'paid_by_friend_amount' => 0,
                'notes' => 'Migrated from legacy friend transaction',
            ];
        }

        if ($transaction->type === 'friend_paid_for_me') {
            return [
                'user_id' => $transaction->user_id,
                'friend_id' => $transaction->friend_id,
                'legacy_friend_transaction_id' => $transaction->id,
                'description' => $transaction->description,
                'date' => $transaction->date,
                'payment_method' => $transaction->payment_method,
                'total_amount' => $total,
                'my_share' => $total,
                'friend_share' => 0,
                'paid_by_me_amount' => 0,
                'paid_by_friend_amount' => $total,
                'notes' => 'Migrated from legacy friend transaction',
            ];
        }

        if ($transaction->type === 'shared_expense') {
            return [
                'user_id' => $transaction->user_id,
                'friend_id' => $transaction->friend_id,
                'legacy_friend_transaction_id' => $transaction->id,
                'description' => $transaction->description,
                'date' => $transaction->date,
                'payment_method' => $transaction->payment_method,
                'total_amount' => $total,
                'my_share' => $transaction->my_share,
                'friend_share' => $transaction->friend_share,
                'paid_by_me_amount' => $paidByMe,
                'paid_by_friend_amount' => $paidByFriend,
                'notes' => 'Migrated from legacy friend transaction',
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function projectionNeedsUpdate(FriendTransaction $current, array $payload): bool
    {
        foreach ($payload as $key => $value) {
            if ((string) $current->{$key} !== (string) $value) {
                return true;
            }
        }

        return false;
    }
}
