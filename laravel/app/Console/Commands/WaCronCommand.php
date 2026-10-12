<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class WaCronCommand extends Command
{
    protected $signature = 'wa:cron {action=run : The action to run (run, morning, afternoon, scheduled)}';
    protected $description = 'Run WhatsApp notification cron tasks (morning reminder, afternoon report, scheduled reports)';

    public function handle()
    {
        $action = $this->argument('action');
        $this->info("Running WhatsApp cron task with action: {$action}");

        $result = WhatsAppService::runCron($action);

        if (!empty($result['success'])) {
            $this->info("Cron task completed successfully: " . json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->warn("Cron task returned: " . ($result['message'] ?? 'Failed'));
        }

        return 0;
    }
}
