# Development Log

Cumulative engineering record for **Bitburner Server Manager Web Edition**. Dates reflect project development, not software release dates. Features are distinguished from future plans, and local user confirmations are recorded where available. The complete manuals for Web/Windows/macOS editions will be finalized before the respective public releases.

## 2026-10-13 — Milestone 01: Foundation

- Established a local-first PHP 8.3/MySQL 8/PDO application, developed on Windows with Laragon, HeidiSQL, and GitHub Desktop.
- Created `database/schema.sql` for gameplay sessions and self-related servers; kept environment secrets out of Git.
- Decision: local-only without login/authentication. Application must not be exposed publicly.
- Separate web and future desktop architectures: later C#/.NET/Avalonia/SQLite apps can share data formats and concepts, not necessarily the PHP backend.

## 2026-10-13 — Milestone 02: Sessions, Explorer, RAM and navigation

### Sessions
- Independent sessions have names, optional BitNode and notes, and `active`/`archived` status.
- Archived runs are visible but cannot edit servers until reactivated. Creating/activating runs archives any previously active run.

### Explorer
- `servers.parent_server_id` represents unlimited-depth parent/child relationships; hierarchy and parent selectors preserve insertion order.
- Record hostname, root/backdoor status, ports, hacking requirement, RAM and notes. Validate same-session parent relations and circular ancestry in application logic.
- RAM normalized to GB (`ram_gb DECIMAL(14,3)`); 1 TB equals 1024 GB. `001_add_server_ram.sql` exists only for databases that predate the column; fresh schema already includes it.

### Navigation
- `app/page.php` coordinates common routing and redirect-after-POST behavior.
- Split routes: Dashboard, Sessions, Server Explorer, Settings. `views/layout.php` provides global navigation and session selector.
- Installation rule: database export before upgrades; never run fresh schema over populated database.

## 2026-10-13 — Milestone 03: Connection path generation

- Added parent-chain traversal to construct commands such as `home; connect n00dles; connect max-hardware`.
- If a path lacks `home`, references a missing parent, cycles, or contains invalid terminal hostnames, display an explanation rather than invalid commands.
- Added clipboard copy fallback and archived-session read-only visibility; no schema migration.
- `php tests/connection-paths-test.php` passed in local testing.

## 2026-10-13 — Milestone 04: Search/filter and reset correction

- Client-side hostname/notes search plus root/backdoor, port, maximum hacking level and minimum RAM filters (combined AND).
- Match results preserve ancestor context and recorded sibling order; context ancestors do not count as matches.
- Fixed Clear filters needing two clicks by waiting until native browser reset completes; restore previous expanded/collapsed tree state. No database changes.

## 2026-10-13 — Milestone 05: JSON session backup/restore

- Added `app/backups.php` and Settings upload/download forms for versioned format `bitburner-server-manager-session`, version 1.
- Export session name, BitNode, notes and server IDs, parent references, hostname, access states, ports, level, RAM and notes.
- Import validates format, length/range limits, unique hostnames, valid parents and acyclic hierarchy, and 8 MB maximum file size before inserting.
- Import creates a separate **archived** session transactionally, remaps original IDs and leaves active/current sessions intact.
- Ignored temporary `UPDATE_NOTES.md` via `.gitignore`; prior tracked copy removed from current Git tree, retained locally.
- User confirmed JSON export, import with restored hierarchy and unchanged active game. Backup validation and connection-path tests passed.
- JSON session backup is **not** a full MySQL backup or Scripts Library backup.

## 2026-10-14 — Milestone 06: Scripts Library

### Design and data
- Global scripts independent of gameplay sessions. Run one-time `002_scripts_library.sql` to add `script_entries` and `script_revisions`.
- Source-preserved originals are protected (`is_builtin`, `builtin_key`); personal copies may be edited/deleted. Saved changes snapshot previous code; deleting a personal script cascades to its revisions.
- Bundled 16 scripts initially: regular (`auto-run.js`, `best-target.js`, `deploy.js`, `find-contracts.js`, `hack.js`, `launch.js`, `money-monitor.js`, `network-map.js`, `root-all.js`) and Dark Net (`dnet-auth.js`, `dnet-enter.js`, `dnet-explorer.js`, `dnet-heartbleed.js`, `dnet-info.js`, `dnet-recon.js`, `dnet-scan.js`). Empty Heartbleed original was filled with code explicitly supplied by user.
- Catalog stores beginner purpose, difficulty, usage, arguments, dependencies and cautions. Inspecting source does not guarantee compatibility in current game.
- Caution: `auto-run.js` stops existing `hack.js` workers. Some utilities depend on `hack.js`/`launch.js`; DNet scripts depend on game `ns.dnet` APIs.
- Seeder is idempotent and never silently overwrites installed built-in originals.

### UI and verification
- Added Scripts page, JavaScript view/edit, code copy/download, personal create/save/rename/delete, revision browsing.
- Added searchable Regular/Dark Net/My Scripts collections, collection filter pills and independent keyboard-accessible collapsible headings.
- Sidebar Delete pill and editor Delete control require confirmation; backend refuses built-in deletion and uses CSRF.
- User confirmed two SQL tables in HeidiSQL (nonfatal MySQL display-width warning), all 16 helpers loaded, built-ins read-only, editable copies, persisted script edits, collapsible groups, both deletion controls and revision history/manual recovery.
- Tests `scripts-validation`, `backup-validation`, `connection-paths` passed, followed by successful GitHub push.

## 2026-10-14 — Milestone 07: Revision restoration and CodeMirror

- Added one-click **Restore this revision** for personal scripts. Revalidate revision ownership; take a transactional snapshot of currently active code before restoring. Preserve metadata and protect built-ins.
- User confirmed restoration worked.
- Integrated CodeMirror 6 through locally bundled `assets/js/codemirror.bundle.js` compiled from `assets/js/codemirror-source.js` via `npm install` and `npm run build:editor`. CodeMirror provides highlighting, line numbers, matching brackets, indentation, search and undo/redo; original textarea is fallback and retains form submission.
- User's npm installation completed with 0 reported vulnerabilities and a nonfatal esbuild install-script warning; bundle compiled at ~869 KB. Editor rendered correctly in Laragon.
- Refined syntax palette to VS Code Dark+: blue keywords, orange strings, green comments, yellow functions, dark gray chrome. User confirmed improved appearance.
- No new database migration. Automated scripts/backup/path tests passed before user committed and pushed.

### Documentation correction
- User clarified earlier documentation snippets were **not manually merged**. Full replacement `README.md`, `CHANGELOG.md`, and this log were reconciled from prior work and GitHub; no snippets assumed applied.

## 2026-10-14 — Milestone 08 Part 1: Read-only Netscript network export

- Created `resources/discovery/network-discovery.js`: breadth-first `ns.scan()` traversal from `home`, deduplicate hostnames, preserve first-discovery parent, read `ns.getServer()` fields (`hasAdminRights`, `backdoorInstalled`, `numOpenPortsRequired`, `requiredHackingSkill`, `maxRam`).
- Version 1 export `bitburner-server-manager-network` includes generated timestamp, root and server data (hostname, parent, root/backdoor, ports, hacking level, RAM GB); `ns.write()` outputs `network-discovery.txt` in Bitburner.
- Scanner does not hack, open ports, connect to remote real-world networks or install backdoors.
- `tests/network-discovery-test.mjs` simulates cyclic/cross-linked game connections and passed. User confirmed in-game scan/export functioned.

## 2026-10-14 — Milestone 08 Part 2: Preview-first discovery import

### Implementation
- Added Network Discovery route/page and uploaded `.txt` or `.json` scanner data with active-session selection.
- Server-side validate format/version, hostname uniqueness, parent connections, cycles, scalar types, RAM and record limits. Require explicit preview and subsequent confirmation.
- Compare incoming game data with same-session stored hostnames; report new, changed or unchanged values before MySQL writes.
- Apply new servers parent-first and update existing game-derived fields in a single transaction. Never remove unscanned records. Preserve existing server notes. Archived sessions are read-only and other sessions unaffected.
- A second browser confirmation is required before applying. Preview state is temporary in PHP browser session (up to 30 minutes).
- Added scanner as **17th built-in Regular helper**, with descriptions and requirements, without changing the previous 16.

### Confirmation hotfix
- Initial preview succeeded but Confirm returned to upload without writes: normalized SQL RAM values (e.g. `16.000`) were revalidated as though raw JSON numbers.
- Fixed by retaining/revalidating original uploaded JSON in session, then using validated normalized values in the transaction. Improved on-page failure reporting. Added regression coverage.

### User verification
- Initial screenshot preview: **75 total**, **67 new**, **3 changed**, **5 unchanged**.
- First confirmation failed without writing; hotfix was installed. User then confirmed all records appeared.
- Repeat preview: **0 new, 0 changed, 75 unchanged**, confirming no duplicates. Existing server notes survived re-sync.
- Added/installed scanner as 17th built-in helper successfully.
- `node tests/network-discovery-test.mjs`, `php tests/discovery-validation-test.php`, `php tests/scripts-validation-test.php`, `php tests/backup-validation-test.php`, `php tests/connection-paths-test.php` all passed. `git diff --check` emitted Windows LF/CRLF conversion warnings but no whitespace failures.
- User confirmed Milestone 08 committed/pushed.

## 2026-10-14 — Milestone 09 Part 1: Documentation and repository presentation

### Problem
- README had become an accumulated milestone/upgrade log, including stale statements that importer and 17th helper were unavailable despite successful local tests.

### Documentation separation
- Rebuilt README as project overview with clear features, local security warning, quick start, docs links, technology stack, roadmap and planned screenshot placeholders (no fabricated images).
- Added `docs/guides/web-installation.md` for fresh development setup, upgrades, editor rebuild, migrations, tests and troubleshooting.
- Added `docs/guides/network-discovery.md` explaining in-game scanner, JSON `.txt` export, transfer, preview, explicit synchronization, preservation rules and errors.
- Added `docs/architecture/web-edition.md` for application boundaries, data model, file layout, security and desktop design separation.
- Reconciled complete `CHANGELOG.md` and this log, preserving earlier milestones and distinguishing verified functionality from planned features.
- No PHP, JavaScript, or database modifications; no migration. User verification of the new docs/repository presentation is pending.

## Future work / release requirements

- Complete Web Edition features, accessibility, beginner onboarding, validation, tests and security/clean installation checks.
- Capture genuine UI screenshots and polish README presentation.
- Create a complete, versioned Web Edition distributable ZIP **at release readiness**, including precompiled editor, setup/schema and documentation but excluding secrets/personal database records. Publish through GitHub Releases after clean-install verification.
- Build **separate** Windows and macOS editions with C#/.NET/Avalonia and likely SQLite; maintain architecture and shared data-format notes while developing. Prepare full, distinct platform installation/use/troubleshooting manuals **before each app's release**.
- Always provide **full replacement code and documentation files** in project update ZIPs; never assume manually supplied snippets were applied.
