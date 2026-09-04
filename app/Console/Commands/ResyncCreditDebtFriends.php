<?php

namespace App\Console\Commands;

use App\Models\CreditDebt;
use App\Services\FinanceLinkService;
use Illuminate\Console\Command;

class ResyncCreditDebtFriends extends Command
{
    protected $signature = 'finance:resync-credit-debts';

    protected $description = 'Re-sync all credit/debt friend balances using Credit=I owe, Debt=they owe me';

    public function handle(FinanceLinkService $linkService): int
    {
        $count = 0;
        CreditDebt::query()->orderBy('id')->each(function (CreditDebt $item) use ($linkService, &$count) {
            $linkService->syncCreditDebtToFriend($item);
            $count++;
        });

        $this->info("Re-synced {$count} credit/debt record(s).");

        return self::SUCCESS;
    }
}
