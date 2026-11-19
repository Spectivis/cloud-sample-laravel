<?php

namespace Tests\Feature;

use App\Console\Commands\RecordHeartbeat;
use App\Jobs\ProcessEntry;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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
}
