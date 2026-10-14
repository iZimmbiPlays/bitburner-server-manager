# Development Log

## 2026-10-14 — Milestone 06 Part 3: Collapsible script collections

- Changed collection containers in `views/scripts.php` to native `details`/`summary` controls. They start expanded and can be collapsed independently using mouse or keyboard.
- Search auto-expands groups containing matching scripts without changing protected built-in or personal-script deletion behavior.
- Added disclosure styling in `assets/css/scripts.css`. No SQL migration.
- Local verification: collapse each section, re-expand it, search for an item inside a collapsed section, verify filters and delete controls still work.


## 2026-10-14 — Milestone 06 Part 1: Scripts Library

### Design
- Global scripts, independent of game sessions. Existing PHP/MySQL web edition remains local-only; future desktop edition may use C#/.NET/Avalonia with SQLite.
- Store builtin original source and editable user copies in `script_entries`, snapshots of previous code in `script_revisions`.
- Initial browser code editor is a dependency-free textarea (tab insertion and copy fallback); advanced syntax highlighting deferred.
- 16 imported sources come from supplied `Bitburner_Scripts.zip`; the originally empty `dnet-heartbleed.js` is populated from the exact code supplied in chat.
- Documentation catalog includes category, purpose, usage, arguments, dependencies, safety cautions, and an explicitly unverified-in-game source status.
- Seeding is idempotent and never silently overwrites built-ins. Built-in script rows are immutable through the app's save action.
- Security: CSRF for mutations, parameterized SQL, HTML escaping, filename validation for safe downloads, no remote execution, local-only warning. Script payload limit 256 KB.
- This milestone requires one-time `database/migrations/002_scripts_library.sql`; do not rerun schema.sql.

### Testing checklist
- [ ] HeidiSQL database backup completed.
- [ ] Migration applied successfully once.
- [ ] Built-in import creates 16 entries; repeated import creates no duplicates.
- [ ] Dark Net Heartbleed script has the supplied contents.
- [ ] Built-in code cannot be edited; duplicating creates editable copy.
- [ ] Saved copies retain descriptions, source and revision history.
- [ ] Copy/download and script search work.
- [ ] Existing pages and backup flow remain functional.
- [ ] `php tests/scripts-validation-test.php` passes and Git excludes temporary notes and secrets.

## 2026-10-13 — Milestone 05: JSON gameplay session backups

- Portable single-session JSON export/import, validated before transactional archived-session restore; no migration.
- Tested locally by user: JSON export/import succeeded; backup validation and connection-path tests passed.
- `.gitignore` excludes temporary UPDATE_NOTES.md; prior file removed from tracking without removing local copy.

## 2026-10-13 — Milestone 04: Filters and connection paths

- Server hierarchy filtering with ancestor context, port/skill/RAM/access filters, and one-click reset fix.
- Saved home-to-server connection commands, validation and copying.

## 2026-10-13 — Milestone 02: App foundation and RAM

- Navigation into Dashboard/Sessions/Explorer/Settings, archived sessions, unlimited parent nesting and session switching.
- RAM stored as canonical GB with `001_add_server_ram.sql` one-time migration.

## 2026-10-14 — Milestone 06 Part 2: Script collections and safe deletion

- Users verified bundled installation, protected originals, duplication, and personal script editing.
- Added Regular, Dark Net and My Scripts collection sections plus filter pills without migrating data.
- Added personal-script Delete pill in library rows and Delete button in editor, both with explicit confirmation.
- Enforced CSRF and protected built-in status on the server, with transactional deletion and cascading revision removal.
- Verify cancel, deletion from both entry points, removal of revisions, and rejection of forged built-in deletion.
