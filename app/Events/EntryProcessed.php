<?php

namespace App\Events;

use App\Models\Entry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sent over Reverb when the queue worker has processed an entry, so an open
 * guestbook page marks it processed without a reload.
 */
class EntryProcessed implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public Entry $entry) {}

    public function broadcastOn(): Channel
    {
        return new Channel('guestbook');
    }

    public function broadcastAs(): string
    {
        return 'entry.processed';
    }

    /**
     * @return array{id: int, processed_at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->entry->id,
            'processed_at' => $this->entry->processed_at->toIso8601String(),
        ];
    }
}
