<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class PurgeStaleApplications extends Command
{
    protected $signature = 'privacy:purge-applications {--months=18 : Retention window in months}';

    protected $description = 'Delete applications older than the configured retention window (GDPR data minimisation).';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoff = now()->subMonths($months);

        $count = Application::query()
            ->where('updated_at', '<', $cutoff)
            ->get()
            ->each(fn (Application $application) => $application->delete())
            ->count();

        $this->info("Sterse {$count} aplicari mai vechi de {$months} luni.");

        return self::SUCCESS;
    }
}
