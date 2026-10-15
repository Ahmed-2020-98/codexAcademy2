<?php

namespace App\Console\Commands;

use App\Services\Crm\LeadPipeline;
use Illuminate\Console\Command;

class CheckLeadSla extends Command
{
    protected $signature = 'crm:check-sla';

    protected $description = 'Move pending leads that passed the 24H SLA to the overdue list and alert management';

    public function handle(LeadPipeline $pipeline): int
    {
        $count = $pipeline->flagSlaBreaches();
        $this->info("Flagged {$count} overdue lead(s).");

        return self::SUCCESS;
    }
}
