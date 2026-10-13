<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/sessions.php';

startBrowserSession();
$db = null;
$error = null;
$sessions = [];
$selected = null;

try {
    $db = database();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            processSessionPost($db);
        } catch (InvalidArgumentException $exception) {
            setFlash('error', $exception->getMessage());
        }
        header('Location: index.php', true, 303);
        exit;
    }

    $sessions = $db->query("SELECT id, name, bitnode, notes, status, created_at, updated_at
        FROM game_sessions ORDER BY (status = 'active') DESC, created_at DESC, id DESC")->fetchAll();
    $requestedId = filter_input(INPUT_GET, 'session', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($requestedId) {
        foreach ($sessions as $session) {
            if ((int) $session['id'] === (int) $requestedId) {
                $selected = $session;
                break;
            }
        }
    }
    $selected ??= $sessions[0] ?? null;
} catch (PDOException $exception) {
    $error = 'Database unavailable. Check your .env settings and Laragon MySQL service.';
}

$flash = consumeFlash();
$token = csrfToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Bitburner Server Manager</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/sessions.css">
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div class="brand"><span class="brand-icon">&gt;_</span><div><strong>Bitburner Server Manager</strong><small>LOCAL SERVER EXPLORER</small></div></div>
        <span class="tag">SESSIONS · v0.2.0-dev</span>
    </header>
    <section class="intro">
        <p class="eyebrow">GAMEPLAY SESSION MANAGER</p>
        <h1>Map every server. Track every run.</h1>
        <p>Create a separate saved session for each run. Switch between old games without overwriting their information.</p>
    </section>
    <?php if ($error !== null): ?>
        <div class="notice notice-error" role="alert"><?= escape($error) ?></div>
    <?php endif; ?>
    <?php if ($flash !== null): ?>
        <div class="notice <?= $flash['type'] === 'success' ? 'notice-success' : 'notice-error' ?>" role="status"><?= escape((string) $flash['message']) ?></div>
    <?php endif; ?>
    <section class="workspace" aria-label="Game session workspace">
        <aside class="panel">
            <div class="panel-heading"><h2>Gameplay sessions</h2><span><?= count($sessions) ?> SAVED</span></div>
            <div class="pane-body">
                <h3>Create new session</h3>
                <form method="post" class="session-form">
                    <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                    <input type="hidden" name="action" value="create">
                    <label for="new-name">Session name *</label>
                    <input id="new-name" name="name" maxlength="150" required placeholder="BitNode 1 — Run #001" <?= $db === null ? 'disabled' : '' ?>>
                    <label for="new-bitnode">BitNode (optional)</label>
                    <input id="new-bitnode" name="bitnode" maxlength="80" placeholder="BitNode 1" <?= $db === null ? 'disabled' : '' ?>>
                    <label for="new-notes">Notes (optional)</label>
                    <textarea id="new-notes" name="notes" maxlength="6000" rows="2" placeholder="Goals for this run..." <?= $db === null ? 'disabled' : '' ?>></textarea>
                    <button type="submit" <?= $db === null ? 'disabled' : '' ?>>+ Create session</button>
                </form>
                <h3 class="session-list-title">Saved sessions</h3>
                <?php if (!$sessions): ?>
                    <p class="hint">No sessions yet. Create one above to get started.</p>
                <?php endif; ?>
                <nav class="session-list" aria-label="Saved gameplay sessions">
                    <?php foreach ($sessions as $session): ?>
                        <a class="session-link <?= $selected !== null && $selected['id'] === $session['id'] ? 'is-selected' : '' ?>" href="?session=<?= (int) $session['id'] ?>">
                            <strong><?= escape($session['name']) ?></strong>
                            <span class="session-meta"><?= escape($session['bitnode'] ?: 'Unspecified BitNode') ?> · <?= escape($session['status']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>
        <section class="panel">
            <div class="panel-heading"><h2>Selected session</h2><span>DETAILS</span></div>
            <div class="pane-body">
                <?php if ($selected === null): ?>
                    <div class="empty"><span class="icon">⚙</span><h3>Select or create a session</h3><p>Session details will appear here. Server tracking comes in the next milestone.</p></div>
                <?php else: ?>
                    <div class="selected-heading"><h3><?= escape($selected['name']) ?></h3><span class="state <?= $selected['status'] === 'active' ? 'state-active' : '' ?>"><?= escape($selected['status']) ?></span></div>
                    <p class="hint">Created <?= escape($selected['created_at']) ?> · Last updated <?= escape($selected['updated_at']) ?></p>
                    <form method="post" class="session-form">
                        <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                        <input type="hidden" name="session_id" value="<?= (int) $selected['id'] ?>">
                        <input type="hidden" name="action" value="update">
                        <label for="edit-name">Session name *</label>
                        <input id="edit-name" name="name" maxlength="150" required value="<?= escape($selected['name']) ?>">
                        <label for="edit-bitnode">BitNode</label>
                        <input id="edit-bitnode" name="bitnode" maxlength="80" value="<?= escape($selected['bitnode'] ?? '') ?>">
                        <label for="edit-notes">Notes</label>
                        <textarea id="edit-notes" name="notes" maxlength="6000" rows="5"><?= escape($selected['notes'] ?? '') ?></textarea>
                        <button type="submit">Save changes</button>
                    </form>
                    <div class="session-actions">
                        <?php if ($selected['status'] === 'archived'): ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="session_id" value="<?= (int) $selected['id'] ?>">
                                <button class="secondary-button" type="submit">Make active</button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="session_id" value="<?= (int) $selected['id'] ?>">
                                <button class="secondary-button" type="submit">Archive session</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p class="hint">Archiving preserves the session and its future server data. No sessions are deleted here.</p>
                <?php endif; ?>
            </div>
        </section>
    </section>
    <section class="status" aria-label="Installation status">
        <div><span>DATABASE</span><strong class="<?= $db ? 'success' : 'warning' ?>"><?= $db ? 'Connected' : 'Setup required' ?></strong></div>
        <div><span>SAVED SESSIONS</span><strong><?= $db ? count($sessions) : '—' ?></strong></div>
        <div><span>NESTING DESIGN</span><strong>25 levels</strong></div>
    </section>
    <footer>Unofficial community project. Not affiliated with Bitburner or its developers. Local use only.</footer>
</main>
</body>
</html>
