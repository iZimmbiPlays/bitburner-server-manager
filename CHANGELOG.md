# Changelog

All notable changes to Bitburner Server Manager are recorded here. Dates reflect the development sessions; features are marked verified only where locally tested.

## [Unreleased] — 2026-10-14

### Documentation reconciliation
- Rebuilt the full README, changelog and development log using existing repository files, supplied update packages and locally confirmed results from Milestones 01–07.
- Corrected outdated references to the textarea-only editor, incomplete testing checklists and out-of-order milestone entries.
- Clarified that the current web application is local-only, session JSON backups do not include scripts, and future network discovery and desktop editions are not implemented.

### Milestone 07 — Scripts editor improvements
- Added one-click **Restore this revision** for personal scripts with confirmation, ownership checks, built-in protection, transactional replacement and snapshot of the previously current code.
- Added an offline-capable CodeMirror 6 JavaScript editor integration, with line numbers, bracket matching, indentation, search, undo/redo and textarea fallback. Build uses npm and esbuild; no database migration.
- Updated the syntax highlighting and editor chrome to a VS Code Dark+-inspired color scheme. The user confirmed the editor loaded, local bundle build completed, and Dark+ palette looked better.

### Milestone 06 — Scripts Library
- Added Scripts navigation and a global, MySQL-backed script collection independent of gameplay sessions.
- Added `002_scripts_library.sql` to create `script_entries` and `script_revisions` tables; migration applied successfully locally.
- Installed 16 source-preserved built-in examples, including the user-provided replacement source for `dnet-heartbleed.js`, with beginner descriptions, requirements, commands and warnings.
- Added read-only built-in scripts, duplication into personal scripts, personal script creation/editing, `.js` download, code copy, and revision snapshots on save.
- Added Regular Scripts, Dark Net Scripts and My Scripts groupings, collection tabs, search, independent collapsible headings and automatic expansion for matching search results.
- Added confirmed personal-script deletion from a sidebar pill or editor; protect originals and cascade deletion of personal revisions.
- Verified locally: bundle installation, script list, protection, duplication, persistent editing, collection collapse, both deletion paths and revision history.
- Passed PHP scripts, backups and connection-paths test suites after Milestone 06; user confirmed the Milestone 06 commit/push.

## [0.2.0-dev] — 2026-10-13

### Milestone 05 — Portable gameplay session backups
- Added Settings JSON session export/import, format/version checks, file size cap, server topology preservation and server-ID remapping.
- Imports validate full data and use a transaction; restore creates a separate archived session while preserving the active session and existing records.
- Added validation tests and ignored temporary `UPDATE_NOTES.md` files; removed prior tracked copy from the current repository tree without deleting the local file.
- Verified locally: JSON download, import of a new archived session, unchanged active game and backup validation tests.

### Milestone 04 — Server filtering
- Added immediate search by hostname or notes and combinable filters for access status, ports, hacking skill and RAM.
- Kept matching ancestors visible so nested results retain their hierarchy; preserved creation order.
- Fixed Clear filters requiring two clicks by waiting until the native reset completed.

### Milestone 03 — Connection routes
- Generated home-to-server terminal paths from saved hierarchy records, with copy control and explanations for broken, disconnected or circular paths.
- Added regression tests; confirmed paths test passing locally.

### Milestone 02 — Sessions, server explorer and navigation
- Added persistent active/archived gameplay sessions, reactivation and separate run management.
- Added split-view parent-child Server Explorer with unlimited nesting and creation-order sibling sorting.
- Tracked root/backdoor status, ports, hacking level and notes.
- Added GB/TB RAM entry, stored in canonical GB, with one-time `001_add_server_ram.sql` migration and fresh-install schema update.
- Split navigation into Dashboard, Sessions, Server Explorer and Settings, with selected-session switching.

## [0.1.0] — 2026-10-13

### Milestone 01 — Initial foundation
- Created initial PHP/MySQL local companion application, schema, application structure and repository documentation.

## Planned (not released)

- Automatic in-game server discovery and safe network synchronization/preview.
- Expanded beginner tutorials and integration tests.
- Separate cross-platform desktop application in C#/.NET/Avalonia with SQLite and Windows/macOS distribution.
