<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spectivis Cloud sample: Laravel</title>
    <link rel="stylesheet" href="{{ asset('starter.css') }}">
</head>
<body>
    <header class="bar">
        <div class="wrap">
            <a class="wordmark" href="https://spectivis.com">Spectivis <span>Cloud</span></a>
            <nav><a href="https://spectivis.com">spectivis.com</a></nav>
        </div>
    </header>

    <main class="wrap">
        <section class="intro">
            <p class="eyebrow">Laravel {{ app()->version() }}</p>
            <h1>Your starter Laravel app, hosted by Spectivis</h1>
            <p class="lead">A small guestbook that uses the database, the queue worker, the scheduler, file uploads and Reverb. Change it or replace it with your own app.</p>
        </section>

        <section class="block">
            <header><h2>Next steps</h2></header>
            <div class="block-body">
                <ol class="steps">
                    <li>
                        <h3>Edit this page</h3>
                        <p>It lives in <code>resources/views/<wbr>guestbook.blade.php</code>. Routes are in <code>routes/web.php</code>.</p>
                    </li>
                    <li>
                        <h3>Push to deploy</h3>
                        <p>Each push to the connected branch builds and redeploys the app.</p>
                    </li>
                    <li>
                        <h3>Read the logs</h3>
                        <p>Requests, the queue worker and the scheduler all write to the Logs tab.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="block">
            <header>
                <h2>Checks</h2>
                <p>What this app uses on Spectivis Cloud.</p>
            </header>
            <div class="block-body">
                <dl class="status">
                    <div>
                        <dt>Database</dt>
                        <dd><span class="state ok">Connected.</span> {{ $entries->count() }} {{ Str::plural('entry', $entries->count()) }} shown.</dd>
                    </div>
                    <div>
                        <dt>Queue</dt>
                        <dd>Each new entry shows "processed" once the queue worker has handled it.</dd>
                    </div>
                    <div>
                        <dt>Scheduler</dt>
                        <dd>
                            @if ($heartbeat)
                                <span class="state ok">Running.</span> Last heartbeat {{ \Illuminate\Support\Carbon::parse($heartbeat)->toDayDateTimeString() }} UTC.
                            @else
                                <span class="state wait">Waiting.</span> No heartbeat yet. It appears within a minute of the scheduler starting.
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Uploads</dt>
                        <dd>Attach a photo to an entry. It is saved under <code>storage/app/public</code>.</dd>
                    </div>
                    <div>
                        <dt>Broadcasting</dt>
                        <dd>
                            @if ($reverb)
                                <span class="state wait" id="live">Connecting to Reverb.</span> New entries turn "processed" on this page without a reload.
                            @else
                                <span class="state">Off.</span> Turn on Reverb in the environment's settings and redeploy to see entries turn "processed" without a reload.
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="block">
            <header>
                <h2>Sign the guestbook</h2>
                <p>Entries are stored in the database.</p>
            </header>
            <div class="block-body">
                <form class="form" method="post" action="{{ route('entries.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="field">Name <input name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="name"></label>
                    <label class="field">Message <textarea name="message" required maxlength="500">{{ old('message') }}</textarea></label>
                    <label class="field">Photo <small>Optional</small> <input type="file" name="photo" accept="image/*"></label>
                    @foreach ($errors->all() as $error)
                        <p class="error">{{ $error }}</p>
                    @endforeach
                    <button class="button" type="submit">Add entry</button>
                </form>
            </div>
        </section>

        <section class="block">
            <header><h2>Entries</h2></header>
            <div class="block-body">
                <ul class="list">
                    @forelse ($entries as $entry)
                        <li>
                            <strong>{{ $entry->name }}</strong>
                            <span class="meta">{{ $entry->created_at->toDayDateTimeString() }} UTC,
                                <span data-entry-status="{{ $entry->id }}">{{ $entry->processed_at ? 'processed' : 'waiting for the queue worker' }}</span></span>
                            <p>{{ $entry->message }}</p>
                            @if ($entry->photo_path)
                                <img src="{{ asset('storage/'.$entry->photo_path) }}" alt="Photo from {{ $entry->name }}" loading="lazy">
                            @endif
                        </li>
                    @empty
                        <li class="empty">No entries yet.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </main>

    <footer class="foot">
        <div class="wrap">
            <span>Hosted on Spectivis Cloud</span>
            <a href="https://spectivis.com">spectivis.com</a>
        </div>
    </footer>

    @if ($reverb)
        {{-- Reverb speaks the Pusher protocol, so the plain Pusher client is enough for a public channel. --}}
        <script src="{{ asset('vendor/pusher-8.4.0.min.js') }}"></script>
        <script>
            const reverb = @json($reverb);
            const live = document.getElementById('live');
            const pusher = new Pusher(reverb.key, {
                wsHost: reverb.host,
                wsPort: reverb.port,
                wssPort: reverb.port,
                forceTLS: reverb.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                cluster: '',
            });
            pusher.connection.bind('state_change', ({ current }) => {
                live.textContent = current === 'connected' ? 'Connected to Reverb.' : `${current[0].toUpperCase()}${current.slice(1)}.`;
                live.className = current === 'connected' ? 'state ok' : 'state wait';
            });
            pusher.subscribe('guestbook').bind('entry.processed', ({ id }) => {
                const status = document.querySelector(`[data-entry-status="${id}"]`);
                if (status) status.textContent = 'processed';
            });
        </script>
    @endif
</body>
</html>
