# Changelog

## Unreleased
- Expanded Required tools checks for HEIC troubleshooting by reporting Fedora/RPM package presence for `libheif-freeworld` and `libheif-tools`.

## 3.1.1 - 2026-05-04

- Added a fuller normal-user manual in `docs/felhasznaloi-kezikonyv.md`.
- Replaced the in-app Help page with the expanded user guide content.
- Localized the Help page so it follows the profile UI language setting (Hungarian/English).
- Added localization seed/update SQL for the expanded Help content.

## 3.0.1 - 2026-03-18

- Added a localized Help page with main navigation entry and `/help` route.
- Added localized help content seed/update SQL, including Hungarian translations and revised English defaults for existing installs.

## 3.0.0 - 2026-03-18

- Finalized the WebAlbum 3.0 typed-tag foundation and released it as `3.0.0`.
- Added semantic tag management with type + parent hierarchy and manual media-tag relations for all supported media object types.
- Added typed-tag search, descendant-aware parent search, and user-facing typed tag browsing.
- Added admin Tag Tree and Cleanup Tags maintenance views.
- Added search-context filter chips with click-to-remove behavior and automatic refresh.
- Improved search-state persistence so browser-side last search is isolated per user.
- Improved typed-tag rename propagation so active/persisted typed-tag search criteria stay consistent after rename.

## 3.0.0b - 2026-03-17

- Added the typed-tag foundation for WebAlbum 3.0.
- Added semantic tag management, manual media-tag links, typed-tag search, parent hierarchy, and descendant search.
- Added exact all-results typed-tag assignment scope and recursive folder-aware resolution.

## 2.2.0 - 2026-03-14

WebAlbum 2.2.0

Major improvements

- Database-driven localization system
- Hungarian UI translation (100% coverage)
- User-selectable interface language
- Localization admin interface
- Dynamic language management
- Translation fallback to English
- Normalized API error handling
- Centralized frontend error/toast translation layer
- Removal of hardcoded UI strings

Internal improvements

- UI string key system
- Translation status (missing / draft / final)
- Improved error messaging across the application

## 2.1.0 - 2026-03-13
- Added cross-result slideshow controls in preview modals for images, videos, audio, and document assets.
- Slideshow timing is user-selectable for static previews, while videos and audio now advance after playback ends.
- Fixed mixed search pagination for page 2+ by applying the final page slice only after merging media and asset result sets.
- Fixed manual search behavior to reset to page 1 and corrected invalid result summaries when narrowing a search from a later page.
- Added admin-only direct media tag editing foundation with one-time original backup, MariaDB audit tracking, worker execution, and single-file reindex/restore support.
- Added preview-modal `Restore original` action for admin tag editing.

## 1.5.7 - 2026-03-05
- Multi-page TIFF/HEIC runtime handling added in viewer with per-page preview caching.

## 1.5.6 - 2026-03-05
- Version bump only.

## 1.5.5 - 2026-02-23
- Fixed image rotate persistence path end-to-end: added rotate route wiring in backend router and ensured requests hit authenticated rotate controller.
- Hardened media rotation result consistency by normalizing image EXIF orientation after rotate and clearing video rotate metadata.
- Improved rotated media freshness by cache-busting in search UI after save and by using revalidation cache headers on thumb/file/video endpoints.
- Added Admin `Manage thumbs` action panel with `Purge placeholder thumbs` and new `Clear all thumbs` maintenance action.
- Added backend endpoint `POST /api/admin/maintenance/clear-all-thumbs` with audit logging and summary report.

## 1.5.4 - 2026-02-23
- Added Required tools check for ImageMagick HEIC delegate support (`imagemagick-heic`) and warning when unavailable.
- Increased Required tools modal width for better readability.
- Added safer runtime env handling for tool checks (`HOME`, `XDG_CACHE_HOME`, `XDG_CONFIG_HOME`, `TMPDIR`) to avoid dconf cache permission warnings under Apache context.

## 1.5.3 - 2026-02-22
- Added Admin audit log CSV export (`Export logs (CSV)`) with current filter support.
- Added backend endpoint `GET /api/admin/audit-logs/export` (and `/api/admin/logs/export`) for admin-only CSV download.

## 1.5.2 - 2026-02-22
- Tags admin list now excludes tags with zero linked images by default, aligning it with tag search/autocomplete behavior.

## 1.5.1 - 2026-02-21
- Search merge now prefers MariaDB asset rows over SQLite `other` rows for asset-managed extensions (`pdf`, `txt`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `mp3`, `m4a`, `flac`) to avoid duplicate/confusing results in `Type = Any`.
- Fixed mixed-source preview behavior where some Office files opened through media rows and showed `Preview not supported for this file type`.

## 1.5.0 - 2026-02-19

- Added rotate controls (clockwise/counterclockwise) in image and video viewer modals.
- Added save rotation action for images and videos in viewer modals.
- Added backend media rotate endpoint (`POST /api/media/{id}/rotate`) using ffmpeg.
- Added progress/wait popup for video rotation while ffmpeg processing is running.

## 1.4.3 - 2026-02-19

- Tags admin: added `Images` column showing per-tag image count.
- Tags admin: added sorting for `Tag` and `Images` columns.

## 1.4.2 - 2026-02-18

- Added Tags CSV export for admins with People/People|* tags excluded.
- Moved tag export action to the Tags admin page and restored Tags in the Admin menu.
- Moved "Re-enable all tags" from Admin dropdown into the Tags admin page.
- Fixed tag export CSV formatting: no PHP deprecation output, always-quoted tag names, and Unix LF line breaks.

## 1.4.1 - 2026-02-16

- Fixed ambiguous duplicate results when filtering by `ext` (e.g. `pdf`) by treating extension-filtered searches as assets-only.
- Added Required tools checks/display for `imagemagick`, `pecl`, and PHP `imagick` extension.
- Added worker deployment templates under `backend/deploy/systemd` and documented Fedora timer/service setup.
- Updated assets worker batch mode so `--max-jobs=N` exits cleanly when the queue is empty.

## 1.4.0 - 2026-02-16

- Completed assets handling (video, documents) with Admin Assets workflow.
- Added documents/audio asset indexing, async derivative jobs, and worker processing.
- Added admin scan/status UX with pending/running/ready/no-processing/failed split and clear-list cleanup.
- Added queue split by job type (`doc_thumb` vs `doc_pdf_preview`) in admin status views.
- Added support for `ppt` and `pptx` document conversion/preview/thumbnail flow.
- Merged doc/audio folders into the sidebar folder tree for browsing/search.

## 1.3.0 - 2026-02-16

- Renamed UI branding from "Webalbum" to "Family memories" (login + main header/title).
- Improved Admin Assets page with automatic refresh and sortable Thumb/Preview status columns.
- Improved asset status display in Admin Assets (audio rows shown as N/A instead of pending).
- Enabled selecting and downloading audio/document assets from search results (still max 20 files per ZIP).

## 1.2.0 - 2026-02-15

- Robust video thumbnail generation
- Fixed a race condition and cache poisoning issue where interrupted video thumbnail generation could permanently store placeholders. Thumbnail generation is now atomic, validated, retry-safe, and resilient to user navigation and session termination.

## 1.1.2 - 2026-02-15

- Changed method of loading env variables.

## 1.1.1 - 2026-02-13

- Enforce WA_PHOTOS_ROOT path containment in file/thumb/download endpoints; harden health endpoint; add login throttling and stricter session cookie handling.

## 1.1.0 - 2026-02-13

- Added folder tree view for browsing the gallery
- Folder selection filters media by subtree
- Folder browsing integrates with existing search builder
- Direct media display respects media type filter (Any / Image / Video)
- Improved navigation for large archives

## 1.0.0 - 2026-02-12

Production release.

### Added
- Session-based authentication and one-time setup/admin bootstrap.
- Admin user management and audit log viewer with filters and paging.
- Saved searches (save, replace, load, rename, delete).
- Per-user favorites and favorites-only search mode.
- Thumbnails on demand for images and videos.
- Grid/List result views, pagination, and image/video overlay viewers.
- Media tag editing (admin) with ExifTool + immediate SQLite sync.
- Admin trash workflow with move/restore/purge/empty, trash thumbnails, and bulk actions.
- Maintenance endpoint to clean empty directory structure with trash blocker rules.

### Changed
- Search API returns paged response (`items`, `total`, `offset`, `limit`).
- Default list limit set to 50.
- Admin/tag/media/user/trash/maintenance actions are logged to `wa_audit_log`.

### Security
- Enforced admin-only access for admin endpoints.
- Added path-safety checks for file, video, thumb, and trash operations.
