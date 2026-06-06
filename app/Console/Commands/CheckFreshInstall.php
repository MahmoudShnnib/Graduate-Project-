<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckFreshInstall extends Command
{
    protected $signature = 'app:check-fresh-install';

    protected $description = 'Exit 0 when this is a fresh install that still needs its initial academic + admin seed (no users exist), exit 1 otherwise. Used by docker/entrypoint.sh instead of fragile tinker output parsing.';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('Existing data found — skipping initial seed.');

            return self::FAILURE;
        }

        $this->info('Fresh database — initial seed required.');

        return self::SUCCESS;
    }
}
