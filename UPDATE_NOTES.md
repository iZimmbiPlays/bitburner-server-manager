# Server Explorer improvement — local update notes

## What changed
- Parent dropdown shows depth-first server hierarchy with visually indented names.
- Parent choices exclude the server being edited and all its descendants, preventing invalid move options.
- Server tree shows root access, backdoor access, port count, required hacking level, and notes inline.
- Sibling servers are displayed in ascending database ID (creation) order instead of alphabetically.
- The hierarchy has no artificial maximum depth.

## Installation
Back up the three existing project files. Copy `index.php`, `app/servers.php`, and `assets/css/servers.css` to their matching locations in the project. Do not run database/schema.sql. No database changes are needed.

## Suggested CHANGELOG.md edit
Under `## [Unreleased]`, add a `### Changed` subsection (or append within the existing one):
- Display parent-server options in nested depth-first order.
- Show ports, hacking levels, and notes directly in the server tree.
- Preserve server creation order among siblings instead of sorting alphabetically.
- Exclude edited servers and their descendants from parent choices.

## Suggested docs/development-log.md edit
Insert at the top, immediately after `# Development Log`:

### 2026-10-13 — Server Explorer usability improvements
- Made the parent dropdown reflect network hierarchy, in creation order, with indented descendants.
- Added port count, hacking level, and server notes to each tree entry.
- Fixed server ordering by querying `ORDER BY id ASC`, using IDs as insertion sequence.
- Added `orderedServerHierarchy()` and `excludedParentIds()` for dropdown construction and invalid-parent filtering.
- No database migration; existing records retain their IDs, parents, and notes.
- Verified PHP syntax; local UI and database persistence testing pending.

## Suggested README.md edit
In the Server Explorer features section (or the nearest current-features section), describe that the tree includes inline metadata/notes, siblings retain insertion order, and the parent selector is indented to match the network hierarchy. There is no hard depth limit.

## Manual test plan
1. Open the existing active session. Confirm existing servers and notes are intact.
2. Add `foodnstuff` under `home` after `n00dles`. Confirm `foodnstuff` is shown after `n00dles`.
3. Inspect the parent dropdown: `n00dles` is indented under `home`, `max-hardware` beneath `n00dles`, and later siblings follow insertion order.
4. Change the selected server. Verify root/backdoor, ports, hacking level, and notes are visible inline in the tree.
5. While editing `n00dles`, confirm `n00dles` and its descendants cannot be chosen as parents.
6. Refresh the browser and confirm the structure and order remain.
7. Confirm archived gameplay sessions remain viewable and read-only for server edits.
