<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="dark"><title>Bitburner Server Manager</title><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/sessions.css"><link rel="stylesheet" href="assets/css/servers.css"><link rel="stylesheet" href="assets/css/navigation.css"></head>
<body><main class="shell">
<header class="topbar"><div class="brand"><span class="brand-icon">&gt;_</span><div><strong>Bitburner Server Manager</strong><small>LOCAL SERVER EXPLORER</small></div></div><span class="tag">v0.2.0-dev</span></header>
<nav class="main-nav" aria-label="Primary navigation">
<?php foreach (['index.php' => 'Dashboard', 'sessions.php' => 'Sessions', 'explorer.php' => 'Server Explorer', 'settings.php' => 'Settings'] as $url => $label): ?>
<a href="<?= $url ?><?= $url === 'explorer.php' && $selected !== null ? '?session=' . (int)$selected['id'] : '' ?>" <?= $page === $url || ($page === 'dashboard.php' && $url === 'index.php') ? 'aria-current="page"' : '' ?>><?= escape($label) ?></a>
<?php endforeach; ?></nav>
<?php if ($sessions): ?>
<form class="session-switcher" method="get" action="<?= escape($page === 'dashboard.php' ? 'index.php' : $page) ?>"><label for="current-session">Viewing session</label><select id="current-session" name="session" onchange="this.form.submit()">
<?php foreach ($sessions as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $selected && (int)$selected['id'] === (int)$s['id'] ? 'selected' : '' ?>><?= escape($s['name']) ?><?= $s['status'] === 'archived' ? ' (archived)' : '' ?></option><?php endforeach; ?></select><noscript><button type="submit">Switch</button></noscript></form>
<?php endif; ?>
<?php if ($error !== null): ?><div class="notice notice-error" role="alert"><?= escape($error) ?></div><?php endif; ?>
<?php if ($flash !== null): ?><div class="notice <?= $flash['type'] === 'success' ? 'notice-success' : 'notice-error' ?>" role="status"><?= escape((string)$flash['message']) ?></div><?php endif; ?>
<?php require __DIR__ . '/' . (in_array($page, ['index.php', 'dashboard.php'], true) ? 'dashboard' : pathinfo($page, PATHINFO_FILENAME)) . '.php'; ?>
<footer>Unofficial fan-made Bitburner companion tool. Local use only.</footer></main></body></html>
