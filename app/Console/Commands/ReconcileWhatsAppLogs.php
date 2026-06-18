<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class ReconcileWhatsAppLogs extends Command
{
    protected $signature = 'whatsapp:reconcile {--limit=200 : Max pending logs to re-check}';

    protected $description = 'Re-check Qonvo for pending WhatsApp logs and flip them to their real sent/failed status.';

    public function handle(WhatsAppService $whatsApp): int
    {
        if (! $whatsApp->isConfigured()) {
            $this->error('WhatsApp (Qonvo) is not configured.');

            return self::FAILURE;
        }

        $updated = $whatsApp->reconcilePendingLogs((int) $this->option('limit'));

        $this->info("Reconciled {$updated} pending WhatsApp log(s).");

        return self::SUCCESS;
    }
}
