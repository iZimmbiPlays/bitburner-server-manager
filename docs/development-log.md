# Development Log

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
- [ ] Execute `database/schema.sql` successfully in HeidiSQL.
- [ ] Create `.env` from `.env.example` and verify credentials.
- [ ] Open application in Laragon; verify the status displays `Connected`.
- [ ] Initialize Git and create initial commit.
- [ ] Create the public GitHub repository and push `main`.

### Next milestone
Implement session creation, selection, editing, and archiving using parameterized SQL and CSRF protection.
