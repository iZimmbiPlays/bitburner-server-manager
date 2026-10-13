# Development Log

## 2026-10-13 — Milestone 02, Part 1: Gameplay Session Management

### Scope
- Create named gameplay sessions, optional BitNode labels, and notes.
- Automatically archive the previously active session when a new game is created.
- View and edit metadata for saved sessions; switch old runs back to active and archive runs without deletion.
- Display a session selector on the left and details editor on the right.
- Preserve the existing `servers` table for the subsequent hierarchy milestone.

### Implementation
- `app/sessions.php`: browser-session and CSRF utilities, validated form inputs, parameterized SQL, session workflows.
- `index.php`: session manager forms and navigation with output escaping and POST/redirect/GET.
- `assets/css/sessions.css`: split-pane forms, session list, and status styles layered onto original `app.css`.
- No new SQL migration: `database/schema.sql` already includes the necessary `game_sessions` table.

### Safeguards and limitations
- This is an unauthenticated local-only app; do not expose it to the internet.
- CSRF token is required for all changes; SQL inputs are parameterized; outputs are HTML-escaped.
- Only one active session is intended at a time, although advanced multi-user concurrency constraints are not yet implemented.
- Archive preserves records; there is no destructive delete operation.
- Archived session metadata can still be edited. Future server editing will be restricted to the active session.
- App assumes the PHP `mbstring` extension is enabled (typical with Laragon).

### Test checklist
- [ ] Open the app, confirm Database Connected.
- [ ] Create a run called `BitNode 1 — Run #001`.
- [ ] Verify the new run appears and is marked active.
- [ ] Change its name, BitNode, and notes; reload and confirm persistence.
- [ ] Create a second run; confirm the first is archived.
- [ ] Select the older run and make it active; confirm the newer run is archived.
- [ ] Confirm records remain in HeidiSQL `game_sessions`.
- [ ] Verify a refresh does not duplicate a submitted form.
- [ ] Check Git diff and confirm `.env` remains excluded.

### Next
Implement server CRUD with session boundaries, parent validation, depth limits, and the interactive hierarchy.

## 2026-10-13 — Milestone 01: Project Foundation

### Purpose
Build a local-first, GitHub-ready tool that preserves Bitburner server maps across separate runs.

### Stack
PHP 8.3+, MySQL 8+, vanilla HTML/CSS/JavaScript, Apache via Laragon, Git/GitHub.

### Decisions
- Maintain separate `game_sessions` so new runs never overwrite historical runs.
- Model server nesting using a nullable self-referencing `parent_server_id`, not separate tables per level.
- Reserve hierarchy validation (cycle detection, cross-session parent validation, 25-level limit) for the server CRUD milestone.
- Keep runtime data in MySQL; never commit `.env` or database exports containing user data.
- Begin with a deliberately read-only placeholder UI and connection check before implementing mutations.
- Local use only during development; public GitHub source does not imply a publicly accessible hosted application.

### Verification checklist
- [x] Execute `database/schema.sql` successfully in HeidiSQL.
- [x] Create `.env` from `.env.example` and verify credentials.
- [x] Open application in Laragon; verify the status displays `Connected`.
- [x] Initialize Git and create initial commit.
- [x] Create the public GitHub repository and push `main`.

### Next milestone
Implement session creation, selection, editing, and archiving using parameterized SQL and CSRF protection.
