# Changelog

All notable project changes are documented here.

## [Unreleased]

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
