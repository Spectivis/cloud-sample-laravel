<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordHeartbeat extends Command
{
    public const CACHE_KEY = 'scheduler.heartbeat';

    protected $signature = 'app:record-heartbeat';

    protected $description = 'Record the time the scheduler last ran';

    public function handle(): int
    {
        Cache::forever(self::CACHE_KEY, now()->toIso8601String());

        return self::SUCCESS;
    }
}
