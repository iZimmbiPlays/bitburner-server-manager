# Web Edition: installation, upgrades and local development

> **Status:** Development setup guide. A public, versioned ZIP installer/package has not yet been produced or tested on a clean machine.

## Requirements

- PHP 8.3+ with PDO MySQL and `mbstring`.
- MySQL 8+.
- A local web server serving PHP; the tested Windows development environment uses Laragon and HeidiSQL.
- Node.js/npm **only if rebuilding** the CodeMirror JavaScript bundle. Users with the prebuilt bundle do not need Node.js.

**Security:** This PHP application has no authentication. Run it only on a local, trusted environment; do not make it publicly reachable.

## New development installation

1. Obtain the source files from the GitHub repository and place them in the local web-server project folder. In the current Windows development setup this is `D:\Bitburner\Bitburner_Server_Manager` with the Laragon host `http://bitburner-server-manager.test`.
2. Create a local MySQL database from `database/schema.sql`. **Run this only for a brand-new installation**; do not run it over any existing application data.
3. Run `database/migrations/002_scripts_library.sql` **once** to create the Scripts Library tables. The initial schema already includes `servers.ram_gb`; do not run `001_add_server_ram.sql` on a new installation.
4. Create/configure a local `.env` file using the project's configuration example if present. `app/bootstrap.php` reads `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`; default host is `127.0.0.1`, default port `3306`, default name `bitburner_server_manager`. Keep credentials private and never commit `.env`.
5. Ensure `assets/js/codemirror.bundle.js` is included. If it is missing, build it using the steps below.
6. Start Apache and MySQL, open `http://bitburner-server-manager.test/`, create a gameplay session, and visit **Scripts → Install missing bundled helpers** to load the 17 protected starter scripts.

These are development setup instructions, not yet a verified end-user installation process for every hosting environment.

## Upgrading an existing installation

1. Back up the full database with HeidiSQL (or another MySQL export tool) and export important gameplay sessions from Settings.
2. Compare locally modified files against the supplied update package; install the **complete replacement files** from the relevant update, preserving `.env` and database contents.
3. Apply only migrations for tables/columns **not yet present**. `001_add_server_ram.sql` is for older databases missing `ram_gb`; `002_scripts_library.sql` creates the two Scripts Library tables. **Do not rerun applied migrations.**
4. Restart or refresh your local service as required, then run the regression tests below and verify key pages.
5. The scanner's 17th library entry can be installed through **Install missing bundled helpers**, which adds missing entries rather than replacing installed originals.

**Do not rerun `database/schema.sql` as an upgrade.** Some MySQL versions warn about deprecated integer display widths when executing the Scripts Library migration; verify whether the tables were created rather than treating that warning alone as a failure.

## Rebuilding the JavaScript editor

From the project root:

```powershell
npm install
npm run build:editor
```

This builds `assets/js/codemirror.bundle.js` from `assets/js/codemirror-source.js`. Refresh the Scripts page with Ctrl+F5. The build currently uses CodeMirror 6, esbuild and a VS Code Dark+-inspired palette. Keep the compiled bundle in a future public distribution so players do not need npm to use the editor.

## Developer verification

From the project root:

```powershell
node tests/network-discovery-test.mjs
php tests/discovery-validation-test.php
php tests/scripts-validation-test.php
php tests/backup-validation-test.php
php tests/connection-paths-test.php
git diff --check
git status --short
```

The five tests passed locally after Milestone 08. They are not a substitute for a clean-install or cross-platform integration test. LF/CRLF line-ending conversion warnings on Windows can appear without representing test failures.

## Backups and portability

Settings exports/imports **one gameplay session** as JSON. Import creates a **new archived session**, remapping server IDs and preserving relationships. It does not back up user-created scripts, saved revisions, or the whole MySQL database. Use a full database export for full local recovery. Network Discovery exports are a separate JSON format for synchronization, **not** gameplay-session backups.

## Troubleshooting

- **Database unavailable:** Check `.env`, Apache/MySQL status, database name, and PHP PDO MySQL extension.
- **Scripts page reports missing migration:** Verify `script_entries` and `script_revisions` exist in the selected database. Only run the migration if they are absent.
- **Editor is plain text:** Check `assets/js/codemirror.bundle.js`; rebuild if missing and hard-refresh.
- **Discovery preview fails:** Confirm the uploaded `.txt` contains the complete JSON generated by the scanner, not terminal prompts or truncated output.
- **Discovery shows results but does not synchronize:** Confirm the selected session is active, review on-page validation errors, and verify the current importer includes the Milestone 08 confirmation hotfix. Do not repeatedly submit until the error is understood.

More troubleshooting will be documented and validated when the public Web Edition installation package is finalized.
