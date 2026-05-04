<?php

/*
|--------------------------------------------------------------------------
| Vercel serverless entry point
|--------------------------------------------------------------------------
|
| Vercel maps every incoming HTTP request to this file (see vercel.json's
| `routes` block). All we do is delegate to Laravel's normal entry point at
| `public/index.php`. Keep this thin — any logic that belongs in Laravel's
| bootstrap stays in `bootstrap/app.php`.
|
| Notes for serverless cold starts (read this before debugging in prod):
|
|   1. SESSIONS, CACHE, QUEUE must NOT use the file driver. Each cold-start
|      invocation gets its own ephemeral filesystem; file-based state is lost
|      between requests. Set in Vercel env:
|         SESSION_DRIVER=database     (or redis if available)
|         CACHE_STORE=database
|         QUEUE_CONNECTION=database   (and run a worker elsewhere — Vercel
|                                      cannot host a long-running worker)
|
|   2. STORAGE: anything Laravel writes to `storage/` is also ephemeral. Use
|      S3 (or R2) for any persistent files: FILESYSTEM_DISK=s3.
|
|   3. RDS: cold starts open new connections every time. Without RDS Proxy
|      or pgBouncer the database will run out of connections under burst.
|      In .env: DB_HOST=<your-rds-proxy-endpoint> (not the raw RDS endpoint).
|
|   4. LOGS: stack/single drivers write to /tmp which is fine, but won't
|      persist between invocations. Use Vercel's log drain or LOG_CHANNEL=stderr.
|
|   5. FILAMENT ADMIN: Livewire makes mid-render server round-trips. They
|      may exceed the 30s function timeout under cold-start + slow query.
|      If admin times out, raise vercel.json's `maxDuration` (max 60s on
|      hobby plan, 300s on pro).
*/

require __DIR__.'/../public/index.php';
