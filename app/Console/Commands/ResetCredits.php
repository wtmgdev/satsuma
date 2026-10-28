<?php

namespace App\Console\Commands;

use App\Services\CreditService;
use Illuminate\Console\Command;

class ResetCredits extends Command
{
    protected $signature = 'credits:reset';

    protected $description = 'Reset monthly credit allowances and initialize new organizations';

    public function handle(CreditService $credits): int
    {
        $reset = $credits->resetDue();

        $this->info("Credit windows processed: {$reset}");

        return self::SUCCESS;
    }
}
