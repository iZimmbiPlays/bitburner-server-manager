# Bitburner Server Manager

An unofficial, locally hosted Bitburner companion app for tracking server access, nested network connections, and saved gameplay sessions.

## Status

**v0.2.0 development (Milestone 02, Part 1).** Game sessions can be created, selected, renamed, annotated, archived, and reactivated. Server editing and the 25-level tree are upcoming milestones.

## Requirements

- Windows with Laragon (Apache, PHP 8.3+, MySQL 8+), or an equivalent PHP/MySQL local stack
- PHP PDO MySQL and mbstring extensions enabled
- Git for version control

## Local installation (Laragon)

1. Place the project in Laragon's `www` directory, or add a Laragon junction to its existing folder.
2. In HeidiSQL, connect to your local MySQL server and execute `database/schema.sql` for a fresh installation. The existing schema is sufficient for session management.
3. Copy `.env.example` to `.env` in the project root and supply your local database credentials.
4. Start Apache and MySQL in Laragon.
5. Visit `http://bitburner-server-manager.test/` if the local virtual host is enabled, or `http://localhost/bitburner-server-manager/`.
6. Verify **Database: Connected**.

### Upgrading from v0.1.0

Replace `index.php`; add `app/sessions.php` and `assets/css/sessions.css`; update the documentation. **Do not replace your `.env` or rerun the schema** during this upgrade. Existing records are retained.

## Using gameplay sessions

- Enter a name and optionally a BitNode label and notes, then click **Create session**.
- Selecting a saved session opens its detail editor on the right.
- Click **Save changes** to update the title, BitNode, or notes.
- Click **Archive session** to set a run aside without deleting it.
- Click **Make active** on an archived run to return to it. The previously active run is archived automatically.
- All saved runs remain in the `game_sessions` MySQL table.

## Security note

This tool is intended **for local use only**. There is no user authentication. Mutation forms use per-browser-session CSRF tokens, PDO parameterized SQL, and escaped output, but the app must not be exposed to the public internet. The `.env` file is excluded from Git; never commit credentials or gameplay database exports containing personal data. Publishing the source code to GitHub does not publish your local running app or MySQL data.

## Project layout

```text
bitburner-server-manager/
├── app/
│   ├── bootstrap.php       # Environment and PDO connection
│   └── sessions.php        # Session workflows and CSRF utilities
├── assets/
│   └── css/
│       ├── app.css         # Original layout styles
│       └── sessions.css    # Session UI styles
├── database/schema.sql     # MySQL game_sessions and servers tables
├── docs/development-log.md # Technical history and tests
├── .env.example            # Sample configuration
├── .gitignore
├── CHANGELOG.md
├── index.php               # Session management dashboard
├── LICENSE
└── README.md
```

## Roadmap

- Nested server CRUD with parent/child validation and 25-level limit
- Root/backdoor/port/skill fields and full server split-view editor
- Automatic path-to-server commands and filters
- Portable JSON export/import, optional Netscript discovery

## Disclaimer

Unofficial fan-made companion project, not affiliated with or endorsed by Bitburner or its developers.

## License

MIT. See [LICENSE](LICENSE).
