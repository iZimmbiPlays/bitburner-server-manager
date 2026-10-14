# Development Log

This is the cumulative engineering record for the Bitburner Server Manager Web Edition. It distinguishes implemented features, user-confirmed tests, and future plans. Older update notes were sometimes supplied as snippets and were **not manually merged** by the user; this document reconciles the known work from repository source, delivered update packages and user confirmations.

## 2026-10-13 — Milestone 01: Project foundation

**Goal:** Create a locally hosted Bitburner companion app that stores gameplay sessions and discovered servers independently of the game.

**Architecture:** PHP 8.3, MySQL 8, PDO, browser-rendered HTML/CSS/JavaScript, and Laragon/HeidiSQL on Windows. Public Git repository with sensitive local config excluded. Initial SQL at `database/schema.sql` defines `game_sessions` and `servers`.

**Constraints:** No application authentication; strictly local operation. Treat in-game Bitburner scripts as game programs, not host processes. Maintain full README, changelog and development-history files.

## 2026-10-13 — Milestone 02: Session and server management

### Gameplay session model
- Save multiple independent gameplay sessions with names, optional BitNode and notes, and active/archived status.
- Allow archived runs to be viewed but not edited until reactivated.
- Preserve original records instead of overwriting a prior run.

### Server explorer
- Use a self-reference (`servers.parent_server_id`) to represent arbitrary-depth server connections.
- Preserve insertion order among siblings, support nested parent selection and avoid creating cycles or cross-session parents.
- Show server name, required ports, hacking level, root/backdoor state and notes in a split-view interface.
- Extend servers with `ram_gb DECIMAL(14,3)`; support GB and TB entry and normalize TB × 1024. Migration: `database/migrations/001_add_server_ram.sql` for old databases; fresh schema already contains RAM.

### Navigation refactor
- `app/page.php` coordinates shared page state and POST/redirect/GET behavior.
- Dedicated Dashboard (`index.php`), Sessions (`sessions.php`), Server Explorer (`explorer.php`) and Settings (`settings.php`) routes with `views/layout.php` shared navigation and selected-session dropdown.
- Relevant styles in `assets/css/navigation.css` and server-specific stylesheet.

**Database precautions:** Export the database before upgrading; do not run initial schema on an existing installation. Apply RAM migration once only when needed.

## 2026-10-13 — Milestone 03: Auto-generated terminal connection paths

- Added a path calculation from selected server through its parent chain to `home`.
- Format Bitburner commands such as `home; connect n00dles; connect max-hardware`.
- Added detection/reporting for missing parents, cycles, invalid terminal hostnames or branches not rooted at `home` rather than displaying an unusable command.
- Added browser copy control with clipboard fallback; routes can be viewed in archived sessions.
- No schema migration. `tests/connection-paths-test.php` has passed in local CLI testing.

## 2026-10-13 — Milestone 04: Hierarchy-aware search/filter

- Client-side hostname and notes search, plus filters for root/backdoor state, required ports, maximum hacking level and minimum RAM GB.
- Combine criteria using AND; show ancestor context for matching nested servers without counting those ancestors as matches.
- Preserve saved hierarchy/order and expansion state when filters clear.
- Fixed reset button previously needing two clicks by deferring filtering until after browser native form reset.
- No migration; no mutation of server records.

## 2026-10-13 — Milestone 05: JSON session backups

**Implementation:** `app/backups.php`, Settings export/import controls and POST routing in `app/page.php`.

- Portable `bitburner-server-manager-session` JSON format v1, with session metadata and server records including source IDs, parent IDs, hostname, root/backdoor states, ports, level, RAM and notes.
- Export is restricted by CSRF and session validity, delivered as a download.
- Import checks format/version, names and numerical ranges, unique hostnames, valid parent IDs, acyclic relationships and an 8 MB upload cap before inserting records.
- Insert new **archived** session and remap all server references; use a database transaction for rollback. Preserve existing active session unchanged.
- `.gitignore` excludes `UPDATE_NOTES.md` so temporary update instructions do not recur in Git history. Earlier tracked copy was staged for removal from current tree.
- **No migration**.

**User-verified:** JSON download; import as separate archived session and restored hierarchy; existing session untouched; `php tests/backup-validation-test.php` and connection-path tests passed.

## 2026-10-14 — Milestone 06: Beginner-friendly Scripts Library

### Schema and storage
- New one-time `database/migrations/002_scripts_library.sql` migration creates `script_entries` and `script_revisions`.
- Scripts are global, not attached to individual gameplay sessions.
- Store original bundled code with descriptive metadata and a `builtin_key`; keep personal copies separate. Save prior code snapshots on edits.
- User applied migration in HeidiSQL: both tables appeared. MySQL displayed a nonfatal deprecated integer display-width warning.

### Bundled scripts
- Source supplied as `Bitburner_Scripts.zip` (nine regular and seven Dark Net scripts). Initially empty `dnet-heartbleed.js` completed from the separate code supplied by the user.
- Regular: `auto-run.js`, `best-target.js`, `deploy.js`, `find-contracts.js`, `hack.js`, `launch.js`, `money-monitor.js`, `network-map.js`, `root-all.js`.
- Dark Net: `dnet-auth.js`, `dnet-enter.js`, `dnet-explorer.js`, `dnet-heartbleed.js`, `dnet-info.js`, `dnet-recon.js`, `dnet-scan.js`.
- Catalog documents description, difficulty, usage command, arguments, dependencies and cautions. Originals are preserved rather than rewritten as supposedly equivalent alternatives.
- Note `auto-run.js` terminates previously running `hack.js` workers before relaunch; some scripts require other scripts; `dnet-*` require in-game DNet APIs and may be game-version/state dependent. Source inspection alone does not prove in-game operation.
- Seeding is idempotent, adding only missing helpers rather than replacing original records.

### User interface and permissions
- Added `scripts.php` route, Scripts tab, `views/scripts.php`, `app/scripts.php`, `assets/css/scripts.css` and `assets/js/scripts.js`.
- Built-in scripts are immutable through the app; users can read, copy, download or duplicate into personal editable scripts.
- Personal scripts support create, rename, description/usage editing, code editing, save, download and deletion; server uses CSRF and prepared statements.
- Added searchable, category-filterable **Regular Scripts**, **Dark Net Scripts** and **My Scripts** sections; independent collapsible `<details>` headings and search expansion.
- Both sidebar Delete pill and editor Delete button show confirmation; server refuses deletion of built-ins and cascades removal of revisions for a deleted personal script.
- Saved edits snapshot previous code in `script_revisions`; newest 20 appear in the UI.

### Milestone 06 verification
- User installed all 16 built-in helpers and confirmed the Scripts page opens.
- `root-all.js` original was read-only; duplicate was editable. Personal copy renamed `root-all-personal.js`, run command updated and test comment persisted after save/refresh.
- User verified all three collections and expanding/collapsing headings.
- User deleted a personal script using sidebar pill, created a second test script and deleted it using the editor button.
- User created `version-test.js`, saved two versions and confirmed prior code visible in revision history; manual copy/paste recovery worked.
- `php tests/scripts-validation-test.php`, `php tests/backup-validation-test.php`, and `php tests/connection-paths-test.php` all passed.
- User reported successful Milestone 06 GitHub commit and push.

## 2026-10-14 — Milestone 07 Part 1: One-click revision restore

- Added **Restore this revision** to history entries for personal scripts.
- Require confirmation and CSRF; validate selected script is personal and requested revision belongs to that script.
- Transactionally snapshot the current code before replacing it with the older code. Restore code only; preserve filename and documentation metadata.
- No database migration. **User confirmed restoration worked in the local application.**

## 2026-10-14 — Milestone 07 Part 2: CodeMirror integration

- Chose CodeMirror 6 for JavaScript/Netscript syntax highlighting, line numbers, bracket matching, indentation, search and undo/redo.
- Added source at `assets/js/codemirror-source.js`, build configuration in `package.json`, and generated output target `assets/js/codemirror.bundle.js`.
- Build using `npm install` then `npm run build:editor`; the bundle is loaded locally rather than from a CDN. Preserve original textarea as form field and fallback, and keep built-ins read-only.
- User reported `npm install` added 19 packages with zero audit vulnerabilities and a nonfatal esbuild install-script warning. `npm run build:editor` succeeded; bundle size reported 869.3 KB with build time 299 ms.
- User confirmed CodeMirror displayed colored syntax and line numbers.

### VS Code Dark+ palette refinement
- Replaced the original syntax palette with a VS Code Dark+-inspired palette: dark gray editor surface, blue language keywords, orange strings, green comments, yellow functions, and coordinated line-number/selection colors.
- Full update includes CodeMirror source and build configuration; rebuilding the local bundle and refreshing is required.
- **User confirmed the updated theme worked and looked better.** No SQL migration or script reinstallation.

## 2026-10-14 — Documentation reconciliation

- User clarified previous documentation snippets had **not** been applied manually.
- Inspected current GitHub baseline and previously prepared full update package documentation. Identified stale README references, misplaced changelog sections, and checklists still showing pending despite user-confirmed tests.
- Replaced `README.md`, `CHANGELOG.md` and this development log with full coherent cumulative documents, preserving known prior milestones and separating verified behavior from roadmap items.
- No application code or schema modifications are part of the documentation-only reconciliation.

## Remaining work / proposed roadmap

- **Automatic Network Discovery:** implement read-only Netscript scanning in Bitburner; export verified network facts; preview additions/changes; preserve manually maintained notes and avoid blind overwrites. Not yet implemented.
- **Script tooling:** further tutorials, more granular validation, broader in-game tests and automated integration testing. Not yet fully implemented.
- **Desktop Edition:** separate application planned in C#/.NET/Avalonia with SQLite and Windows/macOS distribution; do not describe it as an existing release.
- Keep README, changelog and this log synchronized with complete-file updates, not manually pasted snippets.
