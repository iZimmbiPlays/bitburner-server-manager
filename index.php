<?php

declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$dbStatus = 'Not connected';
$sessionCount = null;
try {
    $statement = database()->query('SELECT COUNT(*) FROM game_sessions');
    $sessionCount = (int) $statement->fetchColumn();
    $dbStatus = 'Connected';
} catch (PDOException $exception) {
    // Do not display credentials or internal connection details to visitors.
    $dbStatus = 'Setup required';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Bitburner Server Manager</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div class="brand"><span class="brand-icon">&gt;_</span><div><strong>Bitburner Server Manager</strong><small>LOCAL SERVER EXPLORER</small></div></div>
            <span class="tag">FOUNDATION · v0.1.0</span>
        </header>
        <section class="intro">
            <p class="eyebrow">PROJECT INITIALIZED</p>
            <h1>Map every server. Track every run.</h1>
            <p>A split-view workspace for exploring Bitburner's network, updating progress, and revisiting older sessions.</p>
        </section>
        <section class="workspace" aria-label="Application preview">
            <aside class="panel"><div class="panel-heading"><h2>Server explorer</h2><span>LEFT PANE</span></div><div class="empty"><span class="icon">⌘</span><h3>Your server tree goes here</h3><p>The next milestone adds saved sessions and nested servers.</p></div></aside>
            <section class="panel"><div class="panel-heading"><h2>Server details</h2><span>RIGHT PANE</span></div><div class="empty"><span class="icon">⚙</span><h3>Choose a server to edit</h3><p>Root, backdoor, ports, hacking level, and connection path will appear here.</p></div></section>
        </section>
        <section class="status" aria-label="Installation status">
            <div><span>DATABASE</span><strong class="<?= $sessionCount !== null ? 'success' : 'warning' ?>"><?= escape($dbStatus) ?></strong></div>
            <div><span>SAVED SESSIONS</span><strong><?= $sessionCount === null ? '—' : $sessionCount ?></strong></div>
            <div><span>NESTING DESIGN</span><strong>25 levels</strong></div>
        </section>
        <footer>Unofficial community project. Not affiliated with Bitburner or its developers.</footer>
    </main>
</body>
</html>
