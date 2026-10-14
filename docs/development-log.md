# Development Log

## 2026-10-13 — Milestone 04: Hierarchy-aware server filtering

- Added client-side hostname/notes search and combinable status, port, skill, and RAM filtering.
- The filter algorithm includes matching descendants and their parent path, without sorting or mutating data.
- Filters expand relevant ancestors and restore the previous expansion state when cleared.
- Added result count and reset, with accessible labels and live status.
- Changed files: `views/explorer.php`, `views/layout.php`, `assets/css/servers.css`, new `assets/js/server-filters.js`, README and CHANGELOG.
- No migration or PHP backend changes.
- Test: name search; note search; combinations; no matches; deep descendant preserves its parents; clearing restores all servers; switching sessions; archived sessions; existing save and copy actions.
- Security: filter data HTML attribute-escaped in PHP; no HTML injection from browser JS; local-only policy unchanged.



## 2026-10-13 — Milestone 03, Part 1: Automatic connection paths

### Implementation
- `app/servers.php`: iterative parent-chain traversal with cycle/missing-parent detection; validates terminal hostnames; generates commands only for routes rooted at `home`.
- `views/explorer.php`: route breadcrumb and read-only command field for the selected server. Shows an explicit unavailable explanation when needed.
- `assets/js/connection-paths.js`: copy-to-clipboard with fallback for local HTTP environments.
- `assets/css/servers.css`: readable responsive connection panel.
- `views/layout.php`: shared script include.

### Testing checklist
- [ ] Direct child: `home; connect n00dles`.
- [ ] Grandchild: `home; connect n00dles; connect max-hardware`.
- [ ] `home` alone generates `home`.
- [ ] Disconnected root shows guidance rather than a false route.
- [ ] Switching sessions changes route source data; archived sessions display routes.
- [ ] Copy works in local browser; if clipboard permission fails, text is selected for Ctrl+C.
- [ ] Changing a server parent updates the route after save.
- [ ] Verify `.env` and exported SQL remain excluded from Git.

### Database
No migration. Route derives from existing `parent_server_id` and `hostname` fields.


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
