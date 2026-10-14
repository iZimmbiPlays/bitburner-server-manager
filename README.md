# Bitburner Server Manager

An unofficial, local-first, MIT-licensed Bitburner companion application (PHP 8.3+, MySQL 8+, Laragon).

## Features

- Independent active/archived gameplay sessions, server tree, paths, filters, RAM, root/backdoor status and notes.
- Settings: validated, versioned, single-session JSON backup and restore into new archived copy.
- Scripts Library: 16 preserved beginner helper scripts, searchable descriptions, usage/argument/dependency information, copy and .js download, protected originals, editable duplicates, and prior saved code revisions.

## Milestone 06 upgrade

1. Back up your MySQL database in HeidiSQL.
2. Copy the update ZIP contents into the existing project, merging folders. Retain `.env`, current database, and all other application files.
3. **Run `database/migrations/002_scripts_library.sql` exactly once** against `bitburner_server_manager` in HeidiSQL. Do **not** rerun `database/schema.sql`.
4. Open `http://bitburner-server-manager.test/scripts.php` and choose **Install 16 beginner helpers**.
5. The original scripts are protected. Click **Duplicate to My Scripts** to create an editable copy.
6. Run `php tests/scripts-validation-test.php`, plus the existing backup and connection-path tests.

Scripts are globally shared across gameplay sessions. Initial code editing uses a monospaced textarea rather than syntax-highlighted Monaco/CodeMirror; syntax highlighting is a planned enhancement. Saved edits snapshot the preceding code (most recent 20 revisions are shown). Built-in sources are preserved from the user's upload, with Heartbleed filled from the user's follow-up source. Source inspection is **not** a guarantee of in-game compatibility.

### Safety / dependencies

- `auto-run.js` terminates existing `hack.js` processes before restarting workers; use with care.
- Some scripts require sibling files such as `hack.js`, `launch.js`, or DNet helpers. See each script's dependency note.
- `dnet-*.js` rely on the in-game `ns.dnet` APIs, which may not be available in every version or game state.
- The app never executes Netscript on your computer. Copy or download code and run it inside Bitburner.

## Security

**Local use only:** This web app has no login and must not be exposed publicly. All mutating script actions validate CSRF tokens and use prepared statements; output is HTML escaped. Keep `.env`, database exports and private notes out of Git.

## Existing upgrades

- For new installations, use `database/schema.sql` and then `database/migrations/002_scripts_library.sql`; `schema.sql` already includes the server RAM field.
- For older databases only, `001_add_server_ram.sql` may be required once. Never rerun a migration already applied.
- `UPDATE_NOTES.md` should stay untracked; `.gitignore` excludes it.

## Long-term

Web edition in PHP/MySQL; future separate desktop edition planned in C#/.NET/Avalonia, using SQLite and portable formats.

## License

MIT. Not affiliated with Bitburner developers.

## Scripts Library — collections and personal deletion (Milestone 06 Part 2)

The Scripts page separates bundled Regular Scripts, bundled Dark Net Scripts, and My Scripts (user-created scripts and copies). Collection pills and live search help find scripts. Personal entries have Delete controls in both the sidebar and editor. Deletion requires a browser confirmation and a valid CSRF-protected POST; the server rejects deletion of built-in originals. Deleting a personal script also removes its saved revisions using the existing foreign key. Back up or download personal scripts before deletion. No schema change is required for this update.

## Collapsible Scripts collections (Milestone 06 Part 3)

In Scripts, click or keyboard-activate the Regular Scripts, Dark Net Scripts, or My Scripts heading to independently collapse or expand that collection. All start expanded; text search expands matching collections automatically. This is a display-only update; no SQL migration is needed.
