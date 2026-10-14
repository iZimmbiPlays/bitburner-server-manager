# Changelog

All notable project changes are documented here.

## [Unreleased]

### Added — Milestone 05: JSON backup and restore
- Export an individual gameplay session and its server hierarchy as versioned portable JSON.
- Import a validated session as a new archived copy without overwriting existing sessions or changing the active run.
- Validate parent references, duplicate hostnames, cycles, status fields, numeric ranges, and upload size before transactional restore.
- Add Settings backup controls; no database migration is required.

### Fixed
- Defer filter recalculation until after native form reset so Clear filters works with one click.

### Changed
- Ignore temporary `UPDATE_NOTES.md` files in Git; untrack any previously committed copy without deleting it locally.


### Added — Milestone 04: Server search and filters
- Search server hostnames and notes instantly from Server Explorer.
- Filter by root/backdoor state, ports, maximum hacking level, and minimum RAM in GB.
- Preserve ancestor connection paths and original sibling creation order while filtering.
- Clear filters and restore the previous expanded/collapsed tree state; no database changes.


### Added — Connection paths
- Generate the saved route from `home` for any selected server, including archived runs.
- Provide one-click copying of Bitburner terminal `home; connect ...` commands with a clipboard fallback.
- Explain unavailable routes (missing parents, disconnected branches, cycles, or unsupported hostname characters).
- No SQL migration required.

### Added
- Server RAM amount with GB/TB selector, stored as canonical GB, visible in Server Explorer and dashboard attention list.
- One-time database migration for existing installations and updated fresh-install schema.

- Separate Dashboard, Sessions, Server Explorer, and Settings pages, with a shared navigation bar and global session selector.
- Progress summary and pending-access links on the Dashboard.
- Server Explorer as a dedicated split-view workspace.
- Active and archived session groups remain on the Sessions page.

### Changed
- Moved request handling into `app/page.php` while retaining existing parameterized server and session services.
- Post requests redirect back to the appropriate page.
- Server trees and parent choices preserve creation order with no artificial nesting limit.

### Planned
- JSON import/export, optional Netscript discovery, and automated integration tests.

## [0.1.0] - 2026-10-13

### Added
- Initial PHP and MySQL application foundation, split-view placeholder, schema, and Git documentation.
