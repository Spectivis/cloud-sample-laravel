# Spectivis Cloud sample: Laravel

A small Laravel 13 guestbook that shows the parts of an app Spectivis Cloud runs for you.

| Part | How the page shows it |
|---|---|
| Database | Entries are rows in a MySQL table, created by a migration and a seeder. |
| Queue | Saving an entry queues a job. The entry reads "processed" once the queue worker has run it. |
| Scheduler | A task runs every minute and records the time. The page shows the last heartbeat. |
| File uploads | An entry can have a photo, saved in `storage/app/public` and served through the `storage:link` link. |
| Health check | `/up` is Laravel's built-in health route. |
| Broadcasting | With Reverb on, the queue job broadcasts `entry.processed` and an open page marks the entry processed without a reload. |

There is no Redis and no Node build step. The page uses a copy of the Pusher client in `public/vendor`, since Reverb speaks the Pusher protocol.

## Deploy it on Spectivis Cloud

1. In Spectivis Cloud, choose New app, then "Deploy a sample", then Laravel.
2. Leave the Laravel preset on. It sets the release command to `php artisan migrate --force`, and turns on the queue worker and the scheduler.
3. Open the app. Add an entry, and within a minute the entry shows as processed and the heartbeat time appears.

To see broadcasting, turn on Reverb in the environment's settings and redeploy. Cloud sets `BROADCAST_CONNECTION=reverb` and the `REVERB_*` variables, runs Reverb as a third process and sends `/app/*` and `/apps/*` to it. An open page keeps the environment awake.

While an environment is asleep, queue jobs and scheduled tasks pause. Entries wait and the heartbeat stops until the next visit wakes it. Turn off "Sleep when idle" to keep them running.

## Run it locally

You need PHP 8.4 or newer, Composer and a MySQL database.

```
composer install
cp .env.example .env
php artisan key:generate
# set the DB_* values in .env
php artisan migrate --seed
php artisan storage:link
php artisan serve
php artisan queue:work      # in a second terminal
php artisan schedule:work   # in a third terminal
```

For broadcasting locally, set `BROADCAST_CONNECTION=reverb`, the `REVERB_APP_*` values, `REVERB_HOST=localhost`, `REVERB_PORT=8080` and `REVERB_SCHEME=http`, then run `php artisan reverb:start` in a fourth terminal.

## Test it

```
php artisan test
```

The tests use an in-memory SQLite database, so they need no MySQL server.

## Build check

`railpack plan .` with Railpack 0.43.0 detects the PHP provider with Laravel, on PHP 8.4. The PHP version comes from the platform data in `composer.lock`.
