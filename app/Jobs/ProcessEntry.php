<?php

namespace App\Jobs;

use App\Events\EntryProcessed;
use App\Models\Entry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessEntry implements ShouldQueue
{
    use Queueable;

    public function __construct(public Entry $entry) {}

    public function handle(): void
    {
        $this->entry->update(['processed_at' => now()]);

        EntryProcessed::dispatch($this->entry);
    }
}
