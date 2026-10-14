# Bitburner Server Manager

An unofficial local-first Bitburner companion app.

## Status

Milestone 02 Part 3 (navigation refactor), v0.2.0-dev. Separate Dashboard, Sessions, Server Explorer and Settings pages. Sessions and server data remain in MySQL. RAM tracking supports GB and TB inputs and is saved in GB. Automatic home-to-server connection paths are available. JSON backup/import is not yet implemented.

## Installation / upgrade

- PHP 8.3+, PDO MySQL and mbstring; MySQL 8+; local Apache (Laragon supported).
- For an existing installation, copy the supplied PHP, CSS and `views/` files into the project root. Keep `.env` and MySQL untouched. **Do not rerun `database/schema.sql`.**
- The update retains the existing `app/bootstrap.php`, `app/sessions.php`, `app/servers.php` and CSS, and adds `app/page.php`, route PHP files, shared views and `assets/css/navigation.css`.
- Start Laragon and visit `http://bitburner-server-manager.test/`.
- Use Dashboard for progress, Sessions for creating/archiving runs, Server Explorer for server editing and Settings for local configuration details.

## Navigation

- `index.php` / `dashboard.php`: progress counters and servers needing root/backdoor.
- `sessions.php`: create, edit, archive and reactivate gameplay sessions; archived runs stay in a collapsible section.
- `explorer.php`: nested hierarchy and editable server details; archived runs are viewable but cannot be changed.
- `settings.php`: database connection and information; backup tools are forthcoming.
- Top selector switches the viewed game without reactivating it. Use Sessions > Make active to edit an archived game.

## Security

Local-only; no login. Do not expose this application to the public Internet. Forms retain CSRF checks, use prepared SQL, and escape displayed data. `.env` is excluded from Git.

## License

MIT, see `LICENSE`. This fan project is not affiliated with the Bitburner developers.

## RAM tracking upgrade (existing installations)

1. Export a backup of `bitburner_server_manager` in HeidiSQL.
2. Run `database/migrations/001_add_server_ram.sql` **once** in HeidiSQL. Do not rerun it after success.
3. Copy `app/servers.php`, `views/explorer.php`, `views/dashboard.php` and `assets/css/servers.css` into the existing project, keeping all other files.
4. Test a server with `16 GB` and one with `1.5 TB`; inspect the `ram_gb` values (16.000 and 1536.000).

For **new installations only**, use the supplied `database/schema.sql`, which already includes the RAM field. Never rerun that schema as an upgrade on a populated installation. Existing servers have RAM 0 (not recorded), and archived sessions remain unchanged. The saved unit is normalized to GB; the interface displays large values in TB.


## Connection paths

In **Server Explorer**, click a recorded server. The **Connection path** card shows the complete route from `home` and a copyable command sequence such as `home; connect n00dles; connect max-hardware`. The terminal starts from `home` each time, so the route works regardless of your current connection.

If a branch does not lead back to `home`, if a parent is missing, or if an invalid/cyclic relationship exists, the app displays an explanation instead of issuing commands. Routes are calculated from the current session's saved parent relationships and update automatically after server edits. Archived sessions support viewing/copying routes.

No new database columns or migrations are required for this upgrade. Replace `app/servers.php`, `views/explorer.php`, `views/layout.php`, `assets/css/servers.css`, and add `assets/js/connection-paths.js`; also update the project documentation. Existing RAM entries and server records are preserved.

## Searching and filtering servers (Milestone 04)

On **Server Explorer**, enter a hostname or note text and use optional selectors for root access, backdoor installation, required ports, maximum hacking level, and minimum RAM in GB. Filters combine with AND logic. Matching nodes retain their ancestors for network context; ancestors shown only as context do not count as matches. The result counter reports matching servers (not ancestor context). **Clear filters** restores the full tree and its previous expanded state.

All filtering happens in the browser: it does not change MySQL data or saved session records. Archived sessions can be filtered too. No database migration. For existing installations replace `views/explorer.php`, `views/layout.php`, `assets/css/servers.css`, add `assets/js/server-filters.js`, and update documentation.
