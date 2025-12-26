<?php

namespace Yousefkadah\Pelecard\Console;

use Illuminate\Console\Command;

class WebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pelecard:webhook';

    /**
     * The console command description.
     */
    protected $description = 'Display Pelecard webhook URL and setup instructions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $webhookUrl = route('pelecard.webhook');

        $this->info('Pelecard Webhook Configuration');
        $this->line('');
        $this->line('Webhook URL:');
        $this->line("  <fg=green>{$webhookUrl}</>");
        $this->line('');
        $this->line('Setup Instructions:');
        $this->line('  1. Log in to your Pelecard dashboard');
        $this->line('  2. Navigate to Settings > Webhooks');
        $this->line('  3. Add the webhook URL above');
        $this->line('  4. Select the events you want to receive');
        $this->line('  5. Save the configuration');
        $this->line('');

        if (! config('pelecard.webhook.enabled')) {
            $this->warn('⚠ Webhooks are currently disabled in config/pelecard.php');
            $this->line('  Set PELECARD_WEBHOOK_ENABLED=true to enable webhooks');
        }

        return self::SUCCESS;
    }
}
