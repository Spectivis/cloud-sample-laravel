<?php

namespace App\Http\Controllers;

use App\Console\Commands\RecordHeartbeat;
use App\Jobs\ProcessEntry;
use App\Models\Entry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class EntryController extends Controller
{
    public function index(): View
    {
        return view('guestbook', [
            'entries' => Entry::query()->latest('id')->limit(20)->get(),
            'heartbeat' => Cache::get(RecordHeartbeat::CACHE_KEY),
            'reverb' => $this->reverbClient(),
        ]);
    }

    /**
     * What the browser needs to connect to Reverb, or null while broadcasting is off.
     * Read at request time, so the page works whatever was set when it was built.
     *
     * @return array{key: string, host: string, port: int, scheme: string}|null
     */
    private function reverbClient(): ?array
    {
        $options = config('broadcasting.connections.reverb.options');

        if (config('broadcasting.default') !== 'reverb' || blank(config('broadcasting.connections.reverb.key')) || blank($options['host'] ?? null)) {
            return null;
        }

        return [
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => (string) $options['host'],
            'port' => (int) $options['port'],
            'scheme' => (string) $options['scheme'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $entry = Entry::create([
            'name' => $data['name'],
            'message' => $data['message'],
            'photo_path' => $request->file('photo')?->store('photos', 'public'),
        ]);

        ProcessEntry::dispatch($entry);

        return redirect()->route('guestbook');
    }
}
