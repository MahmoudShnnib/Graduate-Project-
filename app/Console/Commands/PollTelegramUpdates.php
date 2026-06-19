<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\TelegramVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PollTelegramUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--loop : Long-poll continuously (dev/realtime) instead of a single drain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Telegram bot updates (share-contact verifications). Single drain by default; --loop runs continuously.';

    private const OFFSET_KEY = 'telegram:poll:offset';

    public function handle(TelegramService $telegram, TelegramVerificationService $verification): int
    {
        if (! $telegram->isConfigured()) {
            $this->warn('Telegram bot is not configured (TELEGRAM_BOT_TOKEN missing).');

            return self::SUCCESS;
        }

        $loop = (bool) $this->option('loop');

        do {
            try {
                $processed = $this->drain($telegram, $verification, $loop ? 50 : 0);

                if (! $loop && $processed === 0) {
                    break;
                }
            } catch (\Throwable $e) {
                // A transient network blip (e.g. cURL 28 timeout reaching Telegram)
                // must not kill a long-running --loop worker — log and retry. A
                // single-drain run just stops; the next scheduled run picks up.
                report($e);
                $this->warn('telegram:poll transient error, retrying: '.$e->getMessage());

                if (! $loop) {
                    break;
                }

                sleep(3);
            }
        } while ($loop);

        return self::SUCCESS;
    }

    private function drain(TelegramService $telegram, TelegramVerificationService $verification, int $timeout): int
    {
        $offset = (int) Cache::get(self::OFFSET_KEY, 0);
        $updates = $telegram->getUpdates($offset, $timeout);
        $count = 0;

        foreach ($updates as $update) {
            try {
                $verification->processUpdate($update);
            } catch (\Throwable $e) {
                report($e);
            }

            if (isset($update['update_id'])) {
                Cache::forever(self::OFFSET_KEY, ((int) $update['update_id']) + 1);
            }

            $count++;
        }

        return $count;
    }
}
