# Bitburner Server Manager

An unofficial, locally hosted Bitburner companion app for tracking server access, nested network connections, and saved game sessions.

## Status

**v0.1.0 — Foundation milestone.** The dashboard and database connection check work. The UI is a placeholder; server editing, session creation, and the 25-level hierarchy are not implemented yet.

## Requirements

- Windows with Laragon (Apache, PHP 8.3+, MySQL 8+), or an equivalent PHP/MySQL local stack
- PHP PDO MySQL extension enabled
- Git for source control

## Local installation (Laragon)

1. Extract this project to `C:\laragon\www\bitburner-server-manager`.
2. In HeidiSQL, open your local MySQL connection and execute `database/schema.sql`.
3. Copy `.env.example` to `.env` in the project root.
4. Edit `.env` to match your local database connection (Laragon defaults often use `root` and a blank password, but verify your settings).
5. Start Apache and MySQL in Laragon.
6. Visit `http://localhost/bitburner-server-manager/`. If Laragon's auto virtual hosts are enabled, `http://bitburner-server-manager.test/` may also work.
7. The page should show **Database: Connected** and **Saved sessions: 0**.

If the database status says *Setup required*, verify the schema was imported, the `.env` settings are correct, and PDO MySQL is enabled.

## Security note

This starter is intended **only for local use**. It has no authentication and should not be exposed to the public internet. `.env` is excluded from Git. Before public hosting, add authentication, full input validation, CSRF protection, and deployment-specific access controls. The source code may be public on GitHub without making your running app public.

## Project layout

```text
bitburner-server-manager/
├── app/bootstrap.php         # Environment and PDO database connection
├── assets/css/app.css        # Responsive dark interface
├── assets/js/                 # Future UI interactions
├── database/schema.sql       # Initial MySQL tables
├── docs/development-log.md   # Technical project history
├── .env.example              # Sample local configuration
├── .gitignore                # Files excluded from version control
├── .htaccess                 # Apache access restrictions
├── CHANGELOG.md
├── index.php                 # Initial app dashboard
├── LICENSE
└── README.md
```

## Planned features

- Saved and archived game sessions
- Split view: network tree and editable server details
- 25-level hierarchy, search, filters, and connection paths
- Session-specific server status tracking
- JSON import/export and eventual Netscript discovery

## Disclaimer

This project is an unofficial fan-made companion tool, not affiliated with or endorsed by the Bitburner developers.

## License

MIT. See [LICENSE](LICENSE).
