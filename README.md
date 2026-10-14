# Bitburner Server Manager

An unofficial, **local-first** companion application for the game Bitburner. The current **Web Edition** is built with PHP 8.3+, MySQL 8+, HTML, CSS and JavaScript. Development and testing use Windows, Laragon and HeidiSQL. This project is MIT-licensed and is not affiliated with the Bitburner developers.

> **Local use only.** This application has no account/login system. Do not expose it to the public Internet.

## Current features

### Gameplay sessions and network explorer

- Create, rename, archive, reactivate and view independent gameplay sessions. Archived sessions remain available for reference and are not editable until reactivated.
- Dashboard counters and server records for the selected session, including access-related work still needed.
- Nested, parent-child server explorer with no arbitrary depth limit; original sibling creation order is preserved.
- Record hostname, parent, root access, backdoor status, ports required, hacking level, RAM and notes. RAM is stored in **GB** (`ram_gb`); the interface accepts GB or TB (1 TB = 1,024 GB).
- Automatically generate a connection route from `home` to a selected recorded server, with copyable `home; connect ...` commands. Missing parents, circular links or disconnected hierarchies are handled as unavailable routes.
- Search and filter by hostname/notes, root/backdoor status, required ports, maximum hacking level and minimum RAM. Matching children retain their ancestor context. The Clear action resets filters and expansion state.
- A shared session selector in the application navigation.

### Backups

Settings can export a single session and its saved server tree as **versioned JSON**. Import validates the file and creates a **new archived session** rather than replacing an existing session or switching the current active run. Server database IDs are remapped while preserving parent relationships and recorded fields. Imports use a transaction and reject malformed data, missing parents, cycles, duplicate hostnames and invalid values. Upload limit: 8 MB. These JSON files are **not** complete MySQL database backups; use HeidiSQL for full-database backup.

### Scripts Library

- **16 bundled helper scripts:** nine regular scripts and seven Dark Net scripts. Their source comes from the supplied game script archive; `dnet-heartbleed.js` was completed using the subsequently supplied source.
- Three expandable sections: **Regular Scripts**, **Dark Net Scripts**, and **My Scripts**, with category/collection filters and live search. Searching opens matching sections.
- Per-script beginner documentation: purpose, difficulty, example run command, arguments/prerequisites, other script dependencies, and warnings.
- Protected built-in originals: view, copy, download as `.js`, or duplicate into a personal editable copy. The application rejects editing/deleting built-ins on the server.
- Create, rename, edit, save, duplicate and delete personal scripts. Deleting a personal script also deletes its saved revisions after confirmation.
- Each save retains the previous code as a revision; the 20 most recent revisions are shown. **Restore this revision** recovers older code while first preserving the currently saved code. Only code is restored; metadata such as the filename and description is unchanged.
- **CodeMirror 6** JavaScript/Netscript editor with line numbers, matching brackets, indentation, search, undo/redo and a **VS Code Dark+-inspired** palette. CodeMirror is built into a local bundle and needs no CDN at runtime; the original textarea remains a fallback if the bundle is missing.
- Scripts are shared across gameplay sessions, and code executes **only inside Bitburner**, never in this PHP web application.

#### Bundled regular scripts

`auto-run.js`, `best-target.js`, `deploy.js`, `find-contracts.js`, `hack.js`, `launch.js`, `money-monitor.js`, `network-map.js`, `root-all.js`.

#### Bundled Dark Net scripts

`dnet-auth.js`, `dnet-enter.js`, `dnet-explorer.js`, `dnet-heartbleed.js`, `dnet-info.js`, `dnet-recon.js`, `dnet-scan.js`.

**Important limitations:** Bundled source has been inspected and preserved, but not every helper has been independently tested in-game in the current Bitburner version. `auto-run.js` terminates existing `hack.js` worker processes before restarting them; some scripts require companion script files. The `dnet-*` helpers depend on in-game `ns.dnet` interfaces and game-state/version availability. Consult each script's guidance before running it.

## Requirements

- PHP 8.3+ with PDO MySQL and mbstring.
- MySQL 8+ and a local web server (Laragon is the tested Windows setup).
- For rebuilding the CodeMirror bundle: Node.js, npm and an internet connection **at installation time** to fetch build dependencies; npm/esbuild bundle assets are then served locally.

## Setup: existing development installation

1. Back up the entire `bitburner_server_manager` database using HeidiSQL before upgrading.
2. Extract supplied **complete-file update packages** into the existing application directory, preserving `.env`, existing user data, and files unrelated to the update. Compare locally modified files before overwriting them.
3. Use **only migrations not yet applied**. Never rerun `database/schema.sql` against an existing populated database.
4. Start Laragon Apache and MySQL; open `http://bitburner-server-manager.test/`.
5. Use Settings to export session backups periodically, especially before testing database changes.

### Database migrations

| File | Purpose | When to run |
| --- | --- | --- |
| `database/schema.sql` | Initial sessions/servers schema, including RAM | New database only |
| `database/migrations/001_add_server_ram.sql` | Add RAM to an older pre-RAM `servers` table | Existing databases missing `ram_gb`, once only |
| `database/migrations/002_scripts_library.sql` | Create `script_entries` and `script_revisions` | Existing/new installations missing the scripts tables, once only |

The user has already confirmed that the Scripts tables exist locally and the 16 helper scripts were installed successfully. **Do not rerun migration 002 or reinstall the originals just to update documentation or editor styling.** The MySQL deprecation warning about integer display width during migration 002 was nonfatal; its tables were created successfully.

### Build or rebuild the editor

From the project root in PowerShell:

```powershell
cd "D:\Bitburner\Bitburner_Server_Manager"
npm install
npm run build:editor
```

The build produces `assets/js/codemirror.bundle.js`. Reload the Scripts page using **Ctrl+F5**. The VS Code Dark+ palette is configured in `assets/js/codemirror-source.js`; rebuilding is required after changing it. The user has verified that the bundle compiles and that the updated Dark+ colors render correctly. Do not commit `node_modules/`; include the browser bundle with packaged releases if intended to work without a build step.

## Verification

From the project root:

```powershell
php tests/scripts-validation-test.php
php tests/backup-validation-test.php
php tests/connection-paths-test.php
git status --short
```

All three automated validation tests **passed locally** after the Milestone 06 update. The JSON export/import, server connection paths, built-in protection, editable copies, creating and deleting personal scripts, searchable/collapsible collections, revision history and the one-click restore action were also verified through the local interface. **No complete cross-platform or end-to-end automated suite has been reported.**

## Data model and architecture

- `game_sessions`: independent runs, including active/archived status and metadata.
- `servers`: per-session server data and self-referencing parent connection.
- `script_entries`: global built-in or personal script sources and documentation.
- `script_revisions`: previous saved code snapshots, cascaded on personal-script deletion.
- PHP request coordination uses `app/page.php`; shared views render the UI and PDO prepared statements handle MySQL operations.
- Application forms use CSRF tokens; displayed user data is HTML-escaped. Backups and personal scripts can contain user notes: keep private data out of public Git commits.
- JSON session backups are portable but currently cover **gameplay sessions only**, not the entire Scripts Library.

## Development workflow and security

Local project: `D:\Bitburner\Bitburner_Server_Manager`. GitHub repository: `iZimmbiPlays/bitburner-server-manager` (main branch). Test using Laragon/HeidiSQL and the CLI tests before committing and pushing using GitHub Desktop.

`.env`, vendor/dependency folders, database exports, and other private/local artifacts should not be committed. Temporary `UPDATE_NOTES.md` files are excluded from Git via `.gitignore`; they are not authoritative project documentation. Always inspect `git status --short` before committing.

## Roadmap (not yet implemented)

- Automated in-game network discovery using supported Netscript APIs, a portable export, preview/diff before import, and preservation of existing notes and records.
- Further beginner tutorials and integration tests for the script library.
- A separate **Desktop Edition** in **C# / .NET / Avalonia UI**, likely with SQLite. Proposed Windows and macOS releases will use the existing Web Edition as a functional reference; the desktop edition is **planned, not built**.

## License

MIT; see `LICENSE`. Unofficial fan project, not affiliated with the Bitburner developers.
