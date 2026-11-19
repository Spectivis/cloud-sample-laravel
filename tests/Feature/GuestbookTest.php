<?php

namespace Tests\Feature;

use App\Console\Commands\RecordHeartbeat;
use App\Events\EntryProcessed;
use App\Jobs\ProcessEntry;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_route_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_guestbook_shows_entries(): void
    {
        Entry::factory()->create(['name' => 'Ada']);

        $this->get('/')->assertOk()->assertSee('Ada');
    }

    public function test_signing_the_guestbook_stores_a_row_and_queues_a_job(): void
    {
        Queue::fake();

        $this->post('/entries', ['name' => 'Ada', 'message' => 'Hello'])->assertRedirect('/');

        $this->assertDatabaseHas('entries', ['name' => 'Ada', 'processed_at' => null]);
        Queue::assertPushed(ProcessEntry::class);
    }

    public function test_the_job_marks_the_entry_processed(): void
    {
        $entry = Entry::factory()->create();

        (new ProcessEntry($entry))->handle();

        $this->assertNotNull($entry->fresh()->processed_at);
    }

    public function test_photo_upload_is_stored_on_the_public_disk(): void
    {
        Storage::fake('public');

        $this->post('/entries', [
            'name' => 'Ada',
            'message' => 'Hello',
            'photo' => UploadedFile::fake()->image('me.png'),
        ]);

        $path = Entry::first()->photo_path;
        Storage::disk('public')->assertExists($path);
    }

    public function test_the_heartbeat_command_records_a_time_shown_on_the_page(): void
    {
        $this->artisan('app:record-heartbeat')->assertSuccessful();

        $this->assertNotNull(Cache::get(RecordHeartbeat::CACHE_KEY));
        $this->get('/')->assertDontSee('not recorded yet');
    }

    public function test_the_heartbeat_is_scheduled_every_minute(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('app:record-heartbeat')->assertSuccessful();
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('entries', 1);
    }

    public function test_processing_an_entry_broadcasts_it_on_the_guestbook_channel(): void
    {
        Event::fake([EntryProcessed::class]);
        $entry = Entry::factory()->create();

        (new ProcessEntry($entry))->handle();

        Event::assertDispatched(EntryProcessed::class, function (EntryProcessed $event) use ($entry): bool {
            return $event->broadcastOn()->name === 'guestbook'
                && $event->broadcastAs() === 'entry.processed'
                && $event->broadcastWith()['id'] === $entry->id;
        });
    }

    public function test_the_page_connects_to_reverb_only_when_broadcasting_uses_it(): void
    {
        $this->get('/')->assertOk()->assertSee('Turn on Reverb')->assertDontSee('pusher-8.4.0.min.js');

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'sample-key',
            'broadcasting.connections.reverb.options.host' => 'guestbook.spectivis.app',
            'broadcasting.connections.reverb.options.port' => 443,
            'broadcasting.connections.reverb.options.scheme' => 'https',
        ]);

        $this->get('/')->assertOk()->assertSee('pusher-8.4.0.min.js')->assertSee('guestbook.spectivis.app')->assertSee('sample-key');
    }
}
