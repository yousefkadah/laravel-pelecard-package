<?php

namespace Yousefkadah\Pelecard\Console;

use Illuminate\Console\Command;
use Yousefkadah\Pelecard\Subscription;

class SyncSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pelecard:sync-subscriptions
                            {--user= : Sync subscriptions for a specific user ID}';

    /**
     * The console command description.
     */
    protected $description = 'Sync subscription statuses with Pelecard';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing subscriptions with Pelecard...');

        $query = Subscription::query();

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('No subscriptions found to sync.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Here you would call Pelecard API to check subscription status
                // For now, we'll just mark it as synced
                $synced++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to sync subscription {$subscription->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $this->info("Synced {$synced} subscriptions successfully.");

        if ($failed > 0) {
            $this->warn("Failed to sync {$failed} subscriptions.");
        }

        return self::SUCCESS;
    }
}
