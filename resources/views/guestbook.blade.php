<!DOCTYPE html>
<html lang="en" data-framework="laravel">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spectivis Cloud sample: Laravel</title>
    <link rel="stylesheet" href="{{ asset('starter.css') }}">
</head>
<body>
<div class="page">
    <header class="top">
        <span class="host">{{ request()->getHost() }}</span>
        <nav>
            <a href="#guestbook">Guestbook</a>
            <a class="outline" href="https://spectivis.com">Spectivis Cloud</a>
        </nav>
    </header>

    <main>
        <section class="welcome">
            <div class="welcome-copy">
                <h1>Your starter Laravel app</h1>
                <p class="intro">Hosted on Spectivis Cloud. This guestbook uses each part of the platform. Edit <code>resources/views/<wbr>guestbook.blade.php</code>, push, and the new version goes live.</p>

                <ul class="services">
                    <li>
                        <span class="name">Database</span>
                        <span class="detail"><span class="state ok">Connected</span> &middot; {{ $entries->count() }} {{ Str::plural('entry', $entries->count()) }} shown</span>
                    </li>
                    <li>
                        <span class="name">Queue</span>
                        <span class="detail">New entries show "processed" once the worker handles them.</span>
                    </li>
                    <li>
                        <span class="name">Scheduler</span>
                        <span class="detail">
                            @if ($heartbeat)
                                <span class="state ok">Running</span> &middot; last heartbeat {{ \Illuminate\Support\Carbon::parse($heartbeat)->format('H:i') }} UTC
                            @else
                                <span class="state wait">Waiting</span> &middot; the first heartbeat arrives within a minute
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="name">Uploads</span>
                        <span class="detail">Photos are saved under <code>storage/app/public</code>.</span>
                    </li>
                    <li>
                        <span class="name">Reverb</span>
                        <span class="detail">
                            @if ($reverb)
                                <span class="state wait" id="live">Connecting</span> &middot; entries turn "processed" without a reload
                            @else
                                <span class="state">Off</span> &middot; Turn on Reverb in the environment settings and redeploy
                            @endif
                        </span>
                    </li>
                </ul>

                <div class="actions">
                    <a class="button" href="#guestbook">Sign the guestbook</a>
                    <a class="button quiet" href="https://spectivis.com">Open Spectivis Cloud</a>
                </div>
            </div>

            <div class="panel" aria-hidden="true">
                <dl class="panel-meta">
                    <div><dt>Framework</dt><dd>Laravel {{ app()->version() }}</dd></div>
                    <div><dt>Runtime</dt><dd>PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</dd></div>
                </dl>
                <div class="panel-word">Laravel</div>
            </div>
        </section>

        <section class="guestbook" id="guestbook">
            <div>
                <h2>Sign the guestbook</h2>
                <p class="sub">Entries are stored in the database and passed to the queue worker.</p>
                <form class="form" method="post" action="{{ route('entries.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="field">Name <input name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="name"></label>
                    <label class="field">Message <textarea name="message" required maxlength="500">{{ old('message') }}</textarea></label>
                    <label class="field">Photo <small>Optional, up to 2 MB</small><input type="file" name="photo" accept="image/*"></label>
                    @foreach ($errors->all() as $error)
                        <p class="error">{{ $error }}</p>
                    @endforeach
                    <button class="button" type="submit">Add entry</button>
                </form>
            </div>

            <div>
                <h2>Entries</h2>
                <p class="sub">Newest first.</p>
                @if ($entries->isEmpty())
                    <p class="empty">No entries yet.</p>
                @else
                    <ul class="entries">
                        @foreach ($entries as $entry)
                            <li>
                                <div class="who"><strong>{{ $entry->name }}</strong><span class="when">{{ $entry->created_at->format('M j, H:i') }} UTC</span></div>
                                <p>{{ $entry->message }}</p>
                                @if ($entry->photo_path)
                                    <img src="{{ asset('storage/'.$entry->photo_path) }}" alt="Photo from {{ $entry->name }}" loading="lazy">
                                @endif
                                <span class="tag" data-entry-status="{{ $entry->id }}">{{ $entry->processed_at ? 'Processed' : 'Waiting for the queue worker' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </main>

    <footer class="foot">
        <span>Laravel {{ app()->version() }} on PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</span>
        <a href="https://spectivis.com">spectivis.com</a>
    </footer>
</div>

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
                live.textContent = current === 'connected' ? 'Connected' : `${current[0].toUpperCase()}${current.slice(1)}`;
                live.className = current === 'connected' ? 'state ok' : 'state wait';
            });
            pusher.subscribe('guestbook').bind('entry.processed', ({ id }) => {
                const status = document.querySelector(`[data-entry-status="${id}"]`);
                if (status) status.textContent = 'Processed';
            });
        </script>
    @endif
</body>
</html>
