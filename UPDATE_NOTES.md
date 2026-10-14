# Filter reset fix — 2026-10-13

Replace only `assets/js/server-filters.js`. No database migration or PHP changes.

Cause: `reset` fires before the browser restores all input/select defaults; `queueMicrotask` was too early. Use `requestAnimationFrame` to refresh results after reset.

Test: apply combined search/root/backdoor/ports filters, click Clear filters once, verify all servers and original tree expansion return.

CHANGELOG.md: under `## [Unreleased]`, in `### Fixed` (create it if absent), add: `- Fixed Clear filters requiring two clicks to restore all servers and the previous tree expansion state.`

docs/development-log.md: add a new heading at the top, `## 2026-10-13 — Milestone 04: Filter reset correction`, followed by: `Deferred filter recalculation until after native form reset completes, fixing the two-click reset issue. Regression test: clear combined filters with a single click and verify all servers and original expansion state return.`
