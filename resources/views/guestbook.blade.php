<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spectivis Cloud sample: Laravel</title>
    <style>
        body { font: 16px/1.5 system-ui, sans-serif; max-width: 40rem; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
        h1, h2 { text-wrap: balance; }
        p { text-wrap: pretty; }
        form { display: grid; gap: .75rem; margin-bottom: 2rem; }
        input, textarea, button { font: inherit; padding: .5rem; }
        ul { list-style: none; padding: 0; }
        li { border-top: 1px solid #ddd; padding: .75rem 0; }
        small { color: #555; }
        img { max-width: 8rem; display: block; margin-top: .5rem; }
        .error { color: #a00; }
    </style>
</head>
<body>
    <h1>Guestbook</h1>
    <p>This sample shows that the database, the queue worker, the scheduler and file uploads work on Spectivis Cloud.</p>

    <h2>Checks</h2>
    <ul>
        <li>Database: {{ $entries->count() }} {{ Str::plural('entry', $entries->count()) }} shown.</li>
        <li>Queue: each new entry shows "processed" once the queue worker has handled it.</li>
        <li>Scheduler: last heartbeat
            @if ($heartbeat)
                {{ \Illuminate\Support\Carbon::parse($heartbeat)->toDayDateTimeString() }} UTC.
            @else
                not recorded yet. It appears within a minute of the scheduler starting.
            @endif
        </li>
        <li>Uploads: attach a photo to an entry. It is saved under <code>storage/app/public</code>.</li>
    </ul>

    <h2>Sign the guestbook</h2>
    <form method="post" action="{{ route('entries.store') }}" enctype="multipart/form-data">
        @csrf
        <label>Name <input name="name" value="{{ old('name') }}" required maxlength="80"></label>
        <label>Message <textarea name="message" required maxlength="500">{{ old('message') }}</textarea></label>
        <label>Photo (optional) <input type="file" name="photo" accept="image/*"></label>
        @foreach ($errors->all() as $error)
            <span class="error">{{ $error }}</span>
        @endforeach
        <button type="submit">Add entry</button>
    </form>

    <h2>Entries</h2>
    <ul>
        @forelse ($entries as $entry)
            <li>
                <strong>{{ $entry->name }}</strong>
                <small>{{ $entry->created_at->toDayDateTimeString() }} UTC,
                    {{ $entry->processed_at ? 'processed' : 'waiting for the queue worker' }}</small>
                <p>{{ $entry->message }}</p>
                @if ($entry->photo_path)
                    <img src="{{ asset('storage/'.$entry->photo_path) }}" alt="Photo from {{ $entry->name }}">
                @endif
            </li>
        @empty
            <li>No entries yet.</li>
        @endforelse
    </ul>
</body>
</html>
