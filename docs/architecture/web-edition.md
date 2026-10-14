# Web Edition architecture (developer reference)

## Purpose and scope

Bitburner Server Manager Web Edition is a **local-only** PHP/MySQL companion for storing session-specific server networks and global script resources. This document describes the implementation through Milestone 08. It is not a desktop architecture specification or a production internet-hosting guide.

## Application boundaries

- **Browser:** Server-rendered HTML/CSS and JavaScript for hierarchy filters, copy controls and CodeMirror editing.
- **PHP 8.3+:** Routes, form validation, CSRF, session state, discovery comparison/import, script and gameplay CRUD.
- **MySQL 8+:** Persistent gameplay sessions, servers, script sources and script revisions.
- **Bitburner:** Netscript programs execute *inside the game*. The standalone scanner exports JSON text; PHP never executes Netscript or scans external networks.

## Schema

| Table | Data |
| --- | --- |
| `game_sessions` | Name, optional BitNode, notes, active/archived state and timestamps. |
| `servers` | Gameplay session ID, parent server ID, hostname, root/backdoor states, ports, hacking level, canonical RAM in GB, notes. |
| `script_entries` | Global built-in/personal Netscript code, filename, documentation fields, category, built-in key, version metadata. |
| `script_revisions` | Previous saved code associated with personal scripts; deleted when the parent script is deleted. |

`servers.parent_server_id` represents the network tree without an artificial depth limit. Parent references and import order are validated by application logic; sessions are isolated by `game_session_id`.

## Main application modules

- `app/bootstrap.php`: environment configuration, database connection, HTML escaping.
- `app/sessions.php`: gameplay session lifecycle, browser session, CSRF and flash messages.
- `app/servers.php`: server validation/CRUD, ordering, hierarchy and connection-path logic.
- `app/backups.php`: versioned portable **single-session** JSON export/import.
- `app/scripts.php`: built-in seeding, personal script CRUD, revisions and downloads.
- `app/discovery.php`: discovery document validation, preview comparisons and transactional synchronization (through dedicated `discovery.php` entrypoint).
- `views/`: rendered application screens and shared layout; `assets/` houses styles and JavaScript.
- `resources/`: bundled script sources/catalog and standalone discovery scanner.

## Import and backup rules

**Gameplay backup**: validates data and creates a separate archived gameplay session; original IDs are remapped in a transaction. Script library content is not included.

**Network discovery**: accepts a scanner JSON payload, previews new/changed/unchanged servers, and commits only after confirmation into an active session. It matches hostnames in that session, preserves notes, leaves unscanned records untouched, validates parents and cycles, and uses transactional writes. Preview data is retained temporarily in a PHP session; confirmation revalidates original uploaded JSON (Milestone 08 hotfix).

## Security and compatibility

- **Local-only, no login**: do not publish this deployment to the public Internet.
- Mutations require CSRF checks; SQL statements use parameter binding; displayed content is escaped.
- Built-in scripts are protected by server-side checks; personal script deletion removes its revisions.
- Editor uses a locally bundled CodeMirror 6 JavaScript build; it does not execute code in the app.
- `.env` and runtime data should remain out of the public repository; local MySQL exports can contain private notes.

## Planned separation for desktop editions

The Windows/macOS apps are planned in C#/.NET/Avalonia with SQLite, with their own platform-specific packaging and documentation. Share *portable data formats and feature specifications* where practical rather than implying PHP/MySQL or the existing web runtime will be packaged into the desktop application.

## Testing

CLI coverage currently includes script validation, backup validation, connection-path testing, discovery validation and a Node-based simulated game-network scanner test. Manual Laragon verification has covered session import/export, script editing/history, and 75-host discovery synchronization with duplicate prevention and note preservation. Broader security, clean-install and cross-platform acceptance testing remain release work.
