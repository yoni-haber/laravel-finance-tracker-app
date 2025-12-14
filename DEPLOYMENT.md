# Production Deployment Notes

## Database migrations
- Ensure the core infrastructure tables exist by running migrations in production with `--force`:
  - Cache + cache locks: `php artisan migrate --path=database/migrations/0001_01_01_000001_create_cache_table.php --force`
  - Jobs: `php artisan migrate --path=database/migrations/0001_01_01_000002_create_jobs_table.php --force`
  - Failed jobs: `php artisan migrate --path=database/migrations/0001_01_01_000003_create_failed_jobs_table.php --force`
  - Sessions (required when `SESSION_DRIVER=database`): `php artisan migrate --path=database/migrations/2025_10_01_000000_create_sessions_table.php --force`
- If you have not already run the application migrations on the production database, you can also run them all at once with `php artisan migrate --force` to cover future additions.

## Database-backed sessions
- The default session driver in `config/session.php` falls back to `database` when no `SESSION_DRIVER` is set.
- Keep `SESSION_DRIVER=database` (or set it explicitly in the production `.env`) and ensure the `sessions` table is migrated before serving traffic.
- Rotate stale sessions by clearing rows in the `sessions` table or running `php artisan session:prune` as part of scheduled maintenance.

## Queue worker supervision
- Use a long-running worker instead of `php artisan queue:listen`. A typical Supervisor program entry (stored under `/etc/supervisor/conf.d/laravel-worker.conf`) is:
  ```ini
  [program:laravel-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=/usr/bin/php /var/www/html/artisan queue:work --sleep=3 --tries=3 --backoff=3 --max-jobs=1000 --max-time=3600
  autostart=true
  autorestart=true
  stopasgroup=true
  killasgroup=true
  numprocs=1
  redirect_stderr=true
  stdout_logfile=/var/log/supervisor/laravel-worker.log
  stderr_logfile=/var/log/supervisor/laravel-worker-error.log
  ````
- For a `systemd` alternative, the service `ExecStart` can mirror the same `queue:work` command and use `Restart=always` with a dedicated log target.
- After creating or updating the Supervisor configuration, run `sudo supervisorctl reread && sudo supervisorctl update` and ensure the worker is running with `sudo supervisorctl status`.

## Failed job monitoring
- Inspect failed jobs with `php artisan queue:failed` and requeue with `php artisan queue:retry <job-id>` (or `--all`).
- Clear irrecoverable failures with `php artisan queue:flush` once they are reviewed.
- Consider enabling alerting on the `failed_jobs` table (e.g., via database checks or log-based monitoring) to catch recurring issues quickly.
