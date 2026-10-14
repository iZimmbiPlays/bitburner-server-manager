# Bitburner Server Manager

**Map your Bitburner network. Track your progress. Keep useful Netscript helpers in one place.**

Bitburner Server Manager is an open-source, local-first companion for [Bitburner](https://github.com/bitburner-official/bitburner). The current **Web Edition** helps you organize game sessions, explore server connections, discover servers automatically, and manage a documented library of Netscript scripts.

> **Development status:** The Web Edition is under active development and is not yet a packaged public release. Windows and macOS desktop editions are planned, not available. This is an unofficial fan project and is not affiliated with the Bitburner developers.

## Features

| Feature | What it offers |
| --- | --- |
| **Gameplay sessions** | Keep separate runs, archive previous sessions, and reactivate a run when needed. |
| **Server Explorer** | Browse nested server connections; record root access, backdoors, ports, hacking levels, RAM, and notes. |
| **Smart filtering** | Search hostnames and notes, filter by access and server requirements, and retain ancestors of matching servers. |
| **Connection paths** | Copy the in-game terminal route from `home` to a saved server. |
| **Network Discovery** | Run a read-only Netscript scanner inside Bitburner, upload its JSON export, preview differences, and explicitly synchronize with a selected active session. |
| **Scripts Library** | Browse **17 bundled helpers**, with usage information and cautions; create and edit personal copies without changing protected originals. |
| **Code editor** | Edit Netscript with CodeMirror 6 and a VS Code Dark+-inspired theme; review and restore earlier saved code. |
| **Session backups** | Export a gameplay session as JSON and import it as a separate archived copy. |

### Screenshots

Screenshots of Dashboard, Server Explorer, Network Discovery, and Scripts Library will be added after the interface and release presentation are finalized. We are not using placeholder or fabricated application screenshots.

## Quick start

The current edition is a **locally hosted PHP/MySQL application**, not an installer. It requires PHP 8.3+, MySQL 8+, and a local web server; the development setup uses Laragon on Windows.

1. Follow the [Web Edition installation guide](docs/guides/web-installation.md) to configure a local database and the application.
2. Open the Dashboard and create a gameplay session.
3. Add servers manually in **Server Explorer**, or use [Network Discovery](docs/guides/network-discovery.md) to scan the in-game network and preview a synchronized import.
4. Visit **Scripts** to install the bundled beginner helpers, browse their documentation, and create personal copies.
5. Export a session backup from **Settings** before major changes.

For updating an existing development installation, use the [upgrade instructions](docs/guides/web-installation.md#upgrading-an-existing-installation). Never rerun the initial SQL schema over a populated database.

## Scripts and network discovery

Built-in scripts are grouped into **Regular Scripts** and **Dark Net Scripts**. Personal scripts live under **My Scripts**. The library provides descriptions, requirements, example commands, dependencies, cautions, source viewing, copying, and `.js` downloads. Bundled originals are read-only; personal copies can be edited, deleted, and restored from code history.

The `network-discovery.js` helper scans the *simulated Bitburner network* with supported in-game Netscript APIs. It writes JSON to an in-game file named `network-discovery.txt`. Despite its `.txt` extension, its contents are JSON. The web app uploads that file, shows **new / changed / unchanged** records, and requires confirmation before applying changes. Existing personal server notes are preserved, and servers absent from the scan are not deleted.

See the [network discovery guide](docs/guides/network-discovery.md) for the complete workflow.

## Technology

**Web Edition:** PHP 8.3+, MySQL 8+, PDO, HTML, CSS, JavaScript, and locally bundled CodeMirror 6 (built with npm and esbuild). No CDN is needed for the prebuilt editor at runtime.

**Desktop plans:** Separate Windows and macOS editions are planned using C#, .NET, Avalonia UI, and likely SQLite. These are future development targets, not released apps.

## Security and limitations

- **Local use only.** There is no login system: do not expose the development web application to the public Internet.
- This app **does not run Netscript** on your computer or automatically access your Bitburner game. Copy/download scripts and run them inside Bitburner.
- Read each helper's warnings and requirements. Some scripts depend on other files or game-specific APIs; not all have been independently verified in every game version.
- JSON **session backups contain session and server information**, but do **not** back up the entire MySQL database or your personal Scripts Library.
- Keep `.env`, database dumps, personal notes, and other private data out of Git commits.

## Documentation

- [Web Edition installation and upgrades](docs/guides/web-installation.md)
- [Network Discovery walkthrough](docs/guides/network-discovery.md)
- [Architecture and data model](docs/architecture/web-edition.md)
- [Changelog](CHANGELOG.md)
- [Engineering development log](docs/development-log.md)

Complete, edition-specific end-user manuals for the Web, Windows, and macOS applications will be completed and audited before each respective public release.

## Development and contributions

Repository: [iZimmbiPlays/bitburner-server-manager](https://github.com/iZimmbiPlays/bitburner-server-manager).

The project is in active development; please review the documentation and existing code before proposing changes. Local development and validation instructions are in the [installation guide](docs/guides/web-installation.md#developer-verification). The full distributable Web Edition ZIP will be created, tested from a clean installation, and published through GitHub Releases **when the Web Edition is ready**, not before.

## License

[MIT License](LICENSE). Unofficial Bitburner fan project; not affiliated with or endorsed by the game developers.
