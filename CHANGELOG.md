# Changelog

All notable changes to Bitburner Server Manager Web Edition are recorded here. The project remains in development. Features marked verified were tested in the local Windows/Laragon environment; future desktop apps have not been released.

## [Unreleased] — 2026-10-14

### Milestone 09, Part 1 — Repository presentation and documentation
- Reworked the GitHub README into an approachable project overview instead of a development diary.
- Added dedicated Web Edition installation/upgrade, Network Discovery, and architecture guides.
- Reconciled outdated references to the 16-script catalog and unimplemented network import: the current development version includes 17 helpers and a working preview-first synchronization workflow.
- Preserved the project engineering history in the changelog and development log. No application code, database schema or stored user records changed.
- Reserved final platform-specific user manuals and distributable Web/Windows/macOS packages for each edition's release readiness stage.

### Milestone 08, Part 2 — Network Discovery preview and synchronization
- Added Network Discovery navigation and local `.txt`/JSON upload with explicit target gameplay session selection.
- Preview new, changed and unchanged discovered hosts before writing any database records.
- Synchronize only to active sessions, with transactional insert/update, validation, parent mapping and confirmation.
- Preserve manually maintained notes and any recorded servers absent from the scan; never alter other gameplay sessions.
- Added `network-discovery.js` as the 17th protected helper in Regular Scripts, with documentation and a repeat-safe catalog seed action.
- Fixed a confirmation bug where revalidating normalized RAM strings caused synchronization to return to upload without applying. Preserve and revalidate the original uploaded JSON on confirmation; display failed confirmation errors.
- Locally verified: 75 discovered hosts, with 67 new, 3 changed and 5 unchanged on first preview; synchronization then produced 75 unchanged on reimport, with no duplicates and notes preserved.
- Added discovery validation tests; no migration.

### Milestone 08, Part 1 — Read-only in-game scanner
- Added standalone `network-discovery.js` that uses `ns.scan()` and `ns.getServer()` in Bitburner and writes versioned JSON to `network-discovery.txt`.
- Starts at `home`, discovers each hostname once and chooses a reproducible parent route; does not hack, open ports, install backdoors or scan real networks.
- Added simulated network regression test and verified the scanner in game.

### Milestone 07 — Script editor enhancements
- Added one-click restoration of previous personal-script code revisions. Save the current code to revision history before replacing it; built-in originals remain protected.
- Integrated locally bundled CodeMirror 6, with JavaScript highlighting, line numbers, matching brackets, indentation, search and undo/redo.
- Updated editor colors to a VS Code Dark+-inspired palette. Local npm/esbuild build and display tested; no migration.
- Reconciled the complete README, changelog and development log after learning earlier snippets had not been manually applied.

### Milestone 06 — Scripts Library
- Added a global MySQL-backed Scripts Library separate from gameplay sessions, with 16 source-preserved starter scripts (nine regular, seven Dark Net).
- Added beginner documentation, search, categories, expandable collections, copies, downloads, personal script creation and editing, and saved code revisions.
- Protected original helpers against editing/deletion; added confirmed personal script deletion in sidebar and editor, with revision cascade deletion.
- Added `script_entries` and `script_revisions` via one-time `002_scripts_library.sql` migration.
- Locally verified installation, editing, save/reload, duplicate, delete and version history. Test suites passed.

## [0.2.0-dev] — 2026-10-13

### Milestone 05 — JSON gameplay-session backups
- Added versioned export of individual sessions with complete server hierarchy and fields.
- Added validated import as a **new archived session**, remapping IDs without replacing the active run; transactional rollback on errors.
- Added backup validation tests and `.gitignore` protection for temporary `UPDATE_NOTES.md`.
- Local export/import and validation tests passed. No migration.

### Milestone 04 — Server filtering
- Added instant hostname/notes search and combined root/backdoor/port/skill/RAM filters.
- Preserved ancestor context, creation order and tree expansion when filters are cleared.
- Fixed filter Reset requiring two clicks.

### Milestone 03 — Automatic connection paths
- Added generated `home; connect …` terminal routes, copy action and unavailable-route guidance for broken parent chains.
- Added connection-path tests; no migration.

### Milestone 02 — Sessions and Server Explorer
- Added active/archived gameplay sessions, reactivation, notes and independently saved runs.
- Added hierarchical Server Explorer without an arbitrary nesting limit, editable parent selection, and access/hacking metadata.
- Added GB/TB RAM input stored in canonical GB, with one-time migration `001_add_server_ram.sql` for older databases.
- Split the interface into Dashboard, Sessions, Server Explorer and Settings, with shared session selection.

## [0.1.0] — 2026-10-13

### Milestone 01 — Project foundation
- Created the local PHP/MySQL project, initial `game_sessions`/`servers` schema, application foundation and public repository documentation.

## Planned (not implemented)

- Polished Web Edition release preparation, screenshot capture, clean installation and packaged GitHub Releases ZIP.
- Expanded beginner tutorials, integration and security testing.
- Separate Windows and macOS desktop editions in C#/.NET/Avalonia, likely with SQLite, and edition-specific manuals/installers.
