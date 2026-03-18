# Family memories

Backend and frontend for browsing an indexer-produced SQLite database (read-only).

## Release

- Current version: `3.0.0`
- See `CHANGELOG.md` for release notes.

## Backend

- PHP 8.4, PDO, SQLite read-only
- Entry: `backend/public/index.php`
- Build output: `frontend/dist` -> `backend/public/dist`

### API

`POST /api/search`

`GET /api/health`

`GET /api/tags` (optional `?q=prefix` and `?limit=` query params)

`GET /api/tags/list` (admin list with `q`, `limit`, `offset`)

`GET /api/tree/roots` (top-level indexed folders)

`GET /api/tree?parent_id=<id>` (child folders)

`POST /api/tags/prefs` (body: `{"tag":"...", "is_noise":0|1, "pinned":0|1}`)

`POST /api/admin/assets/scan` (admin: scan docs/audio and enqueue derivatives)

`GET /api/admin/assets` (admin: paged assets list with filters)

`POST /api/admin/assets/requeue` (admin: requeue per-asset derivative jobs)

`GET /api/admin/jobs/status` (admin: queue counts and errors)

`GET /api/asset?id=<asset_id>` (asset metadata)

`GET /api/asset/file?id=<asset_id>` (stream original doc/audio)

`GET /api/asset/view?id=<asset_id>` (PDF viewer source)

`GET /api/asset/thumb?id=<asset_id>` (doc thumb; triggers async generation if missing)

`GET /api/media/{id}/tags`

`POST /api/media/{id}/tags` (admin only, body: `{"tags":["András","Gergely"]}`)

`POST /api/admin/media/{id}/tags/add` (admin only, body: `{"tag":"..."}`)

`POST /api/admin/media/{id}/tags/remove` (admin only, body: `{"tag":"..."}`)

`POST /api/admin/media/{id}/tags/restore` (admin only)

Request body:

```json
{
  "where": {
    "group": "ALL",
    "items": [
      {"field": "tag", "op": "is", "value": "Alice"},
      {"field": "taken", "op": "between", "value": ["2020-01-01", "2020-12-31"]}
    ],
    "folder_rel_path": "2020/2020-01-15 Budapest"
  },
  "sort": {"field": "taken", "dir": "desc"},
  "limit": 200
}
```

Response: paged object with rows (`items`) including `id`, `path`, `taken_ts`, `type`, and optional `entity`/`asset_id` for document/audio assets.

Enable debug SQL output by appending `?debug=1` or setting `WEBALBUM_DEBUG_SQL=1`.

### CLI test

```bash
php backend/bin/search.php --db /path/to/index.db
```

More examples are in `docs/QUERY_MODEL.md`.

## Frontend (Vue 3 + Vite)

```bash
cd frontend
npm install
npm run dev
```

Build:

```bash
npm run build
```

## Config

Set `WA_SQLITE_DB`, `WA_PHOTOS_ROOT`, `WA_THUMBS_ROOT`, `WA_TRASH_ROOT`, `WA_TRASH_THUMBS_ROOT`, `WA_THUMB_MAX`, `WA_THUMB_QUALITY`, `WA_EXIFTOOL_PATH`, `WA_FFMPEG_BIN`, `WA_FFPROBE_BIN`, `WA_SOFFICE_PATH`, `WA_GS_PATH`, `WA_IMAGEMAGICK_BIN`, `WA_PECL_BIN` or edit `backend/config/config.php`.

Example:

```bash
export WA_SQLITE_DB="/Users/bajanp/Projects/images-1.db"
export WA_PHOTOS_ROOT="/Users/bajanp/Projects/indexer-test"
export WA_THUMBS_ROOT="/Users/bajanp/Projects/indexer-test-thumbs"
export WA_TRASH_ROOT="/Users/bajanp/Projects/indexer-test-trash"
export WA_TRASH_THUMBS_ROOT="/Users/bajanp/Projects/indexer-test-thumbs-trash"
export WA_THUMB_MAX="256"
export WA_THUMB_QUALITY="75"
export WA_EXIFTOOL_PATH="exiftool"
export WA_FFMPEG_BIN="ffmpeg"
export WA_FFPROBE_BIN="ffprobe"
export WA_SOFFICE_PATH="soffice"
export WA_GS_PATH="gs"
export WA_IMAGEMAGICK_BIN="magick"
export WA_PECL_BIN="pecl"
```


## System tool checks

- On backend startup/first use, Webalbum checks `ffmpeg`, `ffprobe`, `exiftool`, `soffice`, `gs`, `imagemagick`, `pecl`, and `php-imagick` and caches the result in `backend/var/external_tools_status.json` (rechecked on admin login and when opening Required tools).
- Tool checks are forced on every successful admin login.
- Admin can inspect paths + versions + overrides from UI (`Admin ▾` -> `Required tools`) or API: `GET /api/admin/tools/status`.
- Admin can set manual absolute tool paths via `POST /api/admin/tools/configure` and recheck via `POST /api/admin/tools/recheck`.
- `soffice` is required for Office/TXT -> PDF conversions.
- `gs` (Ghostscript) is required for PDF page rendering used by document thumbnails.
- `imagemagick` + PHP `imagick` extension are required for reliable document thumbnail rendering on server workers.
- `pecl` is checked for diagnostics/operations visibility; runtime conversion itself does not require `pecl`.


## Security notes

- Media paths from SQLite are enforced to stay inside `WA_PHOTOS_ROOT` before any stream, thumbnail generation, or ZIP download.
- Requests that reference files outside `WA_PHOTOS_ROOT` are rejected.
- `GET /api/health` is admin-only.
- Login throttling is enabled on `/api/auth/login` (per IP + username) and emits `auth_throttle` audit events when blocked.
- Session cookie flags are hardened: `HttpOnly`, `SameSite=Lax`, and `Secure` is forced in production (`WEBALBUM_ENV=prod` or `APP_ENV=production`) or when HTTPS / `X-Forwarded-Proto=https` is detected.
- Deployment expectation: terminate TLS in front of app and forward `X-Forwarded-Proto` correctly when using reverse proxies.

## MySQL Tag Prefs

Run the migrations in `backend/sql/mysql/001_tag_prefs.sql` and `backend/sql/mysql/002_user_prefs.sql`.

## Users MVP

- Create users with `backend/migrations/001_users.sql` then apply `backend/sql/mysql/006_users_auth.sql` for auth fields.
- For first-time setup, visit `/setup` and create the admin account (only available when `wa_users` is empty).
- To add users, use the admin endpoints or insert manually into `wa_users`.
- Seeded users in `backend/sql/mysql/003_seed_users.sql` use the password `changeme1234`.

## Favorites

- Create table via `backend/migrations/002_favorites.sql` or `backend/sql/mysql/004_favorites.sql`.
- Favorites are per-user and require a logged-in user.

## Auth & Setup

- Login endpoints: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`.
- Setup endpoints: `GET /api/setup/status`, `POST /api/setup` (one-time).
- Setup creates `backend/var/setup.lock` to prevent re-running.
- User prefs: apply `backend/sql/mysql/007_user_prefs.sql` and edit in “My Profile”.
- Strong password rules: min 12 chars, upper/lower/number/special.
- Admin-set passwords can be 8+ chars and force a change on first login.
- Password changes require current password and clear the force-change flag.
- Admin user management: open the “Admin ▾” menu and use “User management”.
- Audit log: apply `backend/sql/mysql/008_audit_log.sql` and `backend/sql/mysql/009_audit_log_index.sql`.

## Saved searches

- Create table via `backend/sql/mysql/005_saved_searches.sql`.
- Use the Search page “Save search” button to store the current query.
- Manage saved searches from the “Saved searches” page (run, rename, delete).

## Trash

- Apply migration `backend/sql/mysql/011_media_trash.sql`.
- If upgrading an existing table, run `backend/sql/mysql/012_media_trash_hash.sql` (fallback: `backend/sql/mysql/012_media_trash_hash_fallback.sql`).
- Admin endpoints: `POST /api/admin/trash`, `GET /api/admin/trash`, `POST /api/admin/trash/restore`, `POST /api/admin/trash/purge`, `POST /api/admin/trash/empty`, `GET /api/admin/trash/thumb?id=...`.
- Bulk restore/purge accepts `{"trash_ids":[...]}` (single-item remains `{"trash_id":...}`).
- Maintenance endpoint: `POST /api/admin/maintenance/clean-structure` (removes empty folders with trash blocker rules).
- Admin UI: “Admin ▾” → “Trash”.
- Trashed media is excluded from search and favorites until restored.

## Audit logs

- Admins can open “Admin ▾” → “View logs”.
- Example API call:

```bash
curl -b cookies.txt "http://localhost:8445/api/admin/audit-logs?page=1&page_size=50"
```

## Media Tag Editing

Testing checklist:

- Admin can open viewer, click `Edit Tags`, add/remove tags, and save.
- Non-admin users do not see `Edit Tags`.
- Saving an empty list clears IPTC/XMP person tag fields.
- After save, searching by a newly added tag returns that media (SQLite update applied).
- Audit log contains `media_tags_update` with old/new tags.

## Thumbnails On Demand

Testing checklist:

- Run a search returning images.
- Confirm thumbs directory is created under `WA_THUMBS_ROOT` and mirrors `rel_path`.
- Confirm first request generates thumbs, subsequent requests reuse them.
- Confirm if an image is modified, thumb regenerates (mtime check).
- Confirm it works on mac and Fedora where absolute paths differ (fallback to `WA_PHOTOS_ROOT + rel_path`).


## Tree API examples

```bash
# Top-level folders
curl -b cookies.txt "http://localhost:8445/api/tree/roots"

# Children of a folder
curl -b cookies.txt "http://localhost:8445/api/tree?parent_id=42"

# Search filtered to a folder subtree
curl -b cookies.txt -H "Content-Type: application/json" \
  -X POST "http://localhost:8445/api/search" \
  --data '{"where":{"group":"ALL","items":[{"field":"type","op":"is","value":"image"}],"folder_rel_path":"2020/2020-01-15 Budapest"},"sort":{"field":"path","dir":"asc"},"limit":50,"offset":0}'
```


### Path containment regression check

```bash
php backend/bin/path_guard_check.php
```

This check verifies that an outside path such as `/etc/passwd` is rejected by the shared path guard used by file/thumb/download endpoints.


## Worker files

- `backend/bin/assets_worker.php`: background worker for `wa_jobs` (`doc_pdf_preview`, `doc_thumb`) and `wa_object_transform_jobs` (`rotate`).
- `backend/deploy/systemd/webalbum-assets-worker.service`: systemd service template (batch worker).
- `backend/deploy/systemd/webalbum-assets-worker.timer`: systemd timer template.
- `backend/deploy/systemd/assets-worker.env.example`: environment template for worker runtime.
- `backend/deploy/launchd/com.webalbum.assets-worker.plist.example`: macOS launchd template for continuous worker.
- `backend/deploy/cron/webalbum-assets-worker.cron.example`: cron template for per-minute batch worker.

### Run Worker Continuously

Linux (systemd timer):

```bash
sudo cp backend/deploy/systemd/webalbum-assets-worker.service /etc/systemd/system/
sudo cp backend/deploy/systemd/webalbum-assets-worker.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now webalbum-assets-worker.timer
sudo systemctl status webalbum-assets-worker.timer
```

macOS (launchd, every minute batch):

```bash
ROOT="/Users/bajanp/Projects/webalbum2"
PLIST="$HOME/Library/LaunchAgents/com.webalbum.assets-worker.plist"
SQLITE_DB="/var/lib/webalbum/index/indexer2.db"
PHOTOS_ROOT="/data/photos"
THUMBS_ROOT="/data/photos-thumbs"
sed \
  -e "s|__WEBALBUM_ROOT__|$ROOT|g" \
  -e "s|__WA_SQLITE_DB__|$SQLITE_DB|g" \
  -e "s|__WA_PHOTOS_ROOT__|$PHOTOS_ROOT|g" \
  -e "s|__WA_THUMBS_ROOT__|$THUMBS_ROOT|g" \
  backend/deploy/launchd/com.webalbum.assets-worker.plist.example > "$PLIST"
launchctl bootout "gui/$(id -u)/com.webalbum.assets-worker" 2>/dev/null || true
launchctl bootstrap "gui/$(id -u)" "$PLIST"
launchctl kickstart -kp "gui/$(id -u)/com.webalbum.assets-worker"
launchctl print "gui/$(id -u)/com.webalbum.assets-worker" | head -n 40
```

macOS/Linux (cron, batch every minute):

```bash
ROOT="/Users/bajanp/Projects/webalbum2"
sed "s|__WEBALBUM_ROOT__|$ROOT|g" backend/deploy/cron/webalbum-assets-worker.cron.example | crontab -
crontab -l
```

## Fedora deploy checklist

After copying updated backend/frontend files on Fedora, run:

```bash
sudo restorecon -Rv /var/www/webalbum/backend
sudo restorecon -Rv /var/www/webalbum/backend/public
```

This restores SELinux contexts for newly copied files and prevents runtime `500` errors caused by unlabeled PHP files.

## Documents & Audio Assets

- Apply migration: `backend/sql/mysql/013_assets_and_jobs.sql`.
- Supported extensions:
  - Documents: `pdf`, `txt`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`
  - Audio: `mp3`, `m4a`, `flac`
- Admin actions:
  - `Admin ▾ -> Assets` opens the Assets page (scan, job summary, filters, per-asset requeue).
- Worker:
  - Run once: `php backend/bin/assets_worker.php --once`
  - Run batch: `php backend/bin/assets_worker.php --max-jobs=200` (now exits when queue is empty).
  - Recommended: run via systemd timer.
  - Files: `backend/deploy/systemd/webalbum-assets-worker.service`, `backend/deploy/systemd/webalbum-assets-worker.timer`, `backend/deploy/systemd/assets-worker.env.example`.
  - macOS setup:
    - Use `backend/deploy/launchd/com.webalbum.assets-worker.plist.example` for the worker, or run the worker by hand for testing.
    - For local development, `WA_INDEXER2_ROOT` should point at your local `indexer2` checkout.
    - `WA_INDEXER2_PYTHON` is optional. If unset, WebAlbum uses `python3`.
    - `WA_INDEXER2_CONFIG` is optional. If unset, WebAlbum uses `WA_INDEXER2_ROOT/config.yaml`.
    - Typical local values:
      - `WA_BACKUP_ROOT=/Users/yourname/path/to/webalbum2/backend/var/backups` or another writable backup location
      - `WA_INDEXER2_ROOT=/Users/yourname/path/to/indexer2`
      - `WA_INDEXER2_PYTHON=/usr/bin/python3` or the Python from your virtualenv/toolchain
      - `WA_INDEXER2_CONFIG=/Users/yourname/path/to/indexer2/config.yaml`
    - If `indexer2/config.yaml` uses `errors_log_path`, that file must be writable by the same user running the worker. On macOS launchd, that is normally your own user account.
  - Fedora setup:
    - `sudo mkdir -p /etc/webalbum`
    - `sudo cp backend/deploy/systemd/assets-worker.env.example /etc/webalbum/assets-worker.env`
    - Edit `/etc/webalbum/assets-worker.env` with Fedora paths and DB credentials.
    - Recommended worker env vars for tag editing / restore:
      - `WA_BACKUP_ROOT=/data/photos-backups`
      - `WA_INDEXER2_ROOT=/usr/local/lib/indexer2`
      - `WA_INDEXER2_PYTHON=/usr/bin/python3`
      - `WA_INDEXER2_CONFIG=/usr/local/lib/indexer2/config.yaml`
    - `WA_INDEXER2_PYTHON` may be omitted if `python3` is already on the service `PATH`.
    - `WA_INDEXER2_CONFIG` may be omitted if the config file is at `WA_INDEXER2_ROOT/config.yaml`.
    - `sudo cp backend/deploy/systemd/webalbum-assets-worker.service /etc/systemd/system/`
    - `sudo cp backend/deploy/systemd/webalbum-assets-worker.timer /etc/systemd/system/`
    - Important when deploying v2 alongside v1: verify `/etc/systemd/system/webalbum-assets-worker.service` points to `/var/www/webalbum2/backend/bin/assets_worker.php` and uses `WorkingDirectory=/var/www/webalbum2`.
    - `sudo systemctl daemon-reload`
    - `sudo systemctl enable --now webalbum-assets-worker.timer`
    - Optional immediate run: `sudo systemctl start webalbum-assets-worker.service --no-block`
  - Worker execution model:
    - When you run `indexer2` by hand in a shell, it runs as your current shell user. If you invoke it with `sudo`, it runs as `root`.
    - When WebAlbum invokes `indexer2`, it inherits the worker service user, not `root`.
    - In the bundled Fedora systemd unit, the worker runs as `apache:apache`, so the `indexer2` subprocess also runs as `apache:apache`.
  - Backup and logging requirements:
    - `WA_BACKUP_ROOT` must be writable by the worker user because WebAlbum stores pre-edit file backups there before applying media tag changes.
    - If `indexer2/config.yaml` sets `errors_log_path`, that log file and its parent directory must be writable by the worker user.
    - Example: if Fedora runs the worker as `apache`, then both `WA_BACKUP_ROOT` and the configured `errors_log_path` location must be writable by `apache`.
    - If you want indexer logs under `/var/log/indexer2`, create the directory first and ensure the service user can write there.
- Viewer behavior:
  - Audio opens in an HTML5 audio player modal.
  - Documents open in PDF viewer modal (`/api/asset/view`).
  - Office docs are converted asynchronously to PDF via `soffice --headless`.
- Derivative safety:
  - Derivatives are published atomically (`*.tmp` + rename).
  - `ready` status is set only when the real output file exists and is readable.
  - Placeholder responses are never written as final derivative files.
- Fedora/SELinux note:
  - Ensure the Apache/PHP worker context can execute `soffice` and write to `WA_THUMBS_ROOT` and the tmp dir used by conversion.
  - `WA_BACKUP_ROOT` should be labeled `httpd_sys_rw_content_t` if the worker runs as Apache.
  - If `indexer2/config.yaml` writes logs to a custom directory such as `/var/log/indexer2`, that directory must also allow Apache writes when the worker invokes `indexer2`.
  - For a real log directory on Fedora, prefer `httpd_log_t` on `/var/log/indexer2` rather than `httpd_sys_rw_content_t`.
  - Example:

```bash
sudo install -d -o apache -g apache -m 0775 /data/photos-backups
sudo semanage fcontext -a -t httpd_sys_rw_content_t '/data/photos-backups(/.*)?'
sudo restorecon -Rv /data/photos-backups

sudo install -d -o apache -g apache -m 0775 /var/log/indexer2
sudo semanage fcontext -a -t httpd_log_t '/var/log/indexer2(/.*)?'
sudo restorecon -Rv /var/log/indexer2
```

### ext filter sanity check

Use this after login (cookie file already set) to verify `type=any + ext=pdf` returns assets-only rows:

```bash
curl -s -b /tmp/wa.cookies "http://localhost:5173/api/search" \
  -H "Content-Type: application/json" \
  --data '{"where":{"group":"ALL","items":[{"field":"ext","op":"is","value":"pdf"}]},"sort":{"field":"path","dir":"asc"},"limit":50,"offset":0}' |
  php -r '''$d=json_decode(stream_get_contents(STDIN),true);$rows=$d["items"]??[];$bad=array_filter($rows,fn($r)=>(($r["entity"]??"")!=="asset"));echo "total=".count($rows)." non_asset=".count($bad).PHP_EOL;'''
```
