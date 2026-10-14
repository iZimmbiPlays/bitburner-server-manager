# Development Log

## 2026-10-13 — Milestone 02, Part 4: Server RAM

- Added numeric RAM input with GB/TB units; persisted as `ram_gb DECIMAL(14,3)` (1 TB = 1024 GB).
- Preserved existing server records using a one-time `ALTER TABLE` migration; old RAM defaults to 0, meaning unrecorded.
- Exposed RAM directly in network tree and dashboard pending-access rows.
- Updated fresh-install schema, README and CHANGELOG.
- Security: server-side range/precision and unit validation, parameterized SQL, escaped output.
- Testing: back up database, run migration once, verify 16 GB = 16.000 and 1.5 TB = 1536.000, edit an existing server, confirm archive unaffected, and verify no PHP syntax errors.
- Do not commit until Laragon/HeidiSQL smoke tests pass.



## 2026-10-13 — Milestone 02, Part 3: Application navigation refactor

### Goal
Replace the single long editor page with dedicated navigation while preserving existing MySQL records and working session/server form actions.

### Files
- `app/page.php`: shared bootstrap, GET selection, POST routing and POST/redirect/GET.
- `views/layout.php`: consistent top bar, navigation, current game selector, notices and footer.
- `views/dashboard.php`, `views/sessions.php`, `views/explorer.php`, `views/settings.php`: individual screens.
- `index.php`, `dashboard.php`, `sessions.php`, `explorer.php`, `settings.php`: thin PHP entrypoints.
- `assets/css/navigation.css`: responsive navigation and new page layouts.

### Retained behavior
- Session creation, renaming, archiving/reactivation and preservation of older runs.
- Server editing, original creation-order sorting, unlimited tree nesting, visible ports/level/notes, safe parent selection.
- Prepared SQL, CSRF checks, same database schema and local-only security assumptions.

### Verification checklist (local testing pending)
- [ ] Dashboard opens with the expected session's counters and pending tasks.
- [ ] Sessions creates a new game, edits an existing name, and archives/reactivates runs.
- [ ] Explorer correctly loads its chosen session, edits a server and keeps nested order.
- [ ] Archived game can be browsed in Explorer without edit controls.
- [ ] Header selector switches to a different saved run on every page.
- [ ] Settings opens; browser refresh does not duplicate a POST.
- [ ] GitHub Desktop shows no local `.env` or MySQL exports staged.

### Database
No migration. Existing game_sessions and servers tables are unchanged.
