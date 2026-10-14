<?php

declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/sessions.php';
require_once __DIR__ . '/app/discovery.php';
startBrowserSession();
$error = null;
$preview = null;
$sessions = [];
$selectedId = filter_input(INPUT_GET, 'session', FILTER_VALIDATE_INT) ?: 0;
$summary = null;
try {
    $db = database();
    $sessions = $db->query("SELECT id, name, status FROM game_sessions ORDER BY (status='active') DESC, id DESC")->fetchAll();
    if (!$selectedId) {
        foreach ($sessions as $session) if ($session['status'] === 'active') { $selectedId = (int)$session['id']; break; }
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!verifyCsrfToken()) throw new InvalidArgumentException('Form expired. Refresh and try again.');
        $action = $_POST['discovery_action'] ?? '';
        $id = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) throw new InvalidArgumentException('Choose an active gameplay session.');
        $selectedId = (int)$id;
        $validSession = false;
        foreach ($sessions as $session) if ((int)$session['id'] === $selectedId && $session['status'] === 'active') $validSession = true;
        if (!$validSession) throw new InvalidArgumentException('The target session must be active.');
        if ($action === 'preview') {
            $file = $_FILES['discovery_file'] ?? null;
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_int($file['size'] ?? null) || $file['size'] < 1 || $file['size'] > DISCOVERY_MAX_BYTES || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) throw new InvalidArgumentException('Upload a valid JSON discovery file (maximum 8 MB).');
            try { $data = json_decode((string)file_get_contents($file['tmp_name']), true, 512, JSON_THROW_ON_ERROR); }
            catch (JsonException $e) { throw new InvalidArgumentException('The uploaded file is not valid JSON.'); }
            if (!is_array($data)) throw new InvalidArgumentException('Invalid discovery document.');
            $incoming = validateDiscovery($data);
            $_SESSION['discovery_preview'] = ['session_id'=>$selectedId, 'document'=>$data, 'created'=>time()];
            $preview = discoveryDiff($incoming, discoveryExisting($db, $selectedId));
        } elseif ($action === 'apply') {
            $staged = $_SESSION['discovery_preview'] ?? null;
            if (!is_array($staged) || ($staged['session_id'] ?? 0) !== $selectedId || time()-(int)($staged['created'] ?? 0)>1800) throw new InvalidArgumentException('Preview expired. Upload the discovery data again.');
            // Only the previously validated server-side staged data may be applied.
            if (!isset($staged['document']) || !is_array($staged['document'])) throw new InvalidArgumentException('Preview data is missing. Upload the scan again.');
            // Revalidate the ORIGINAL uploaded document; normalized RAM values are strings for SQL.
            $incoming = validateDiscovery($staged['document']);
            $result = applyDiscovery($db, $selectedId, $incoming);
            unset($_SESSION['discovery_preview']);
            setFlash('success', 'Network synchronized: '.$result['added'].' servers added; '.$result['updated'].' updated. Notes and unscanned servers preserved.');
            header('Location: discovery.php?session='.$selectedId, true, 303); exit;
        } else throw new InvalidArgumentException('Invalid discovery action.');
    }
} catch (InvalidArgumentException | PDOException $e) {
    $error = $e instanceof PDOException ? 'Database synchronization failed. Your session was not updated. Check Laragon/MySQL logs before trying again.' : $e->getMessage();
}
$flash = consumeFlash();
$token = csrfToken();
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Network Discovery — Bitburner Server Manager</title><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/navigation.css"><link rel="stylesheet" href="assets/css/servers.css"><style>.discovery-box{padding:22px;border:1px solid #29433d;border-radius:12px;background:#0d1b20;margin:16px 0}.discovery-box input,.discovery-box select{display:block;max-width:100%;margin:9px 0 14px;padding:9px;color:#e3f2ec;background:#081317;border:1px solid #31534d}.discovery-table{width:100%;border-collapse:collapse;font-size:13px}.discovery-table th,.discovery-table td{border-bottom:1px solid #29433d;padding:10px;text-align:left}.discovery-table th{color:#8fa7a1}.discovery-scroll{overflow:auto}.discovery-actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center}.discovery-actions a{color:#8de1b8}.discovery-badge{font-weight:700;color:#8de1b8}</style></head><body><main class="shell">
<header class="topbar"><div class="brand"><span class="brand-icon">&gt;_</span><div><strong>Bitburner Server Manager</strong><small>LOCAL NETWORK DISCOVERY</small></div></div></header>
<nav class="main-nav" aria-label="Primary navigation"><a href="index.php">Dashboard</a><a href="sessions.php">Sessions</a><a href="explorer.php">Server Explorer</a><a href="discovery.php" aria-current="page">Network Discovery</a><a href="scripts.php">Scripts</a><a href="settings.php">Settings</a></nav>
<section class="page-intro"><p class="eyebrow">MILESTONE 08</p><h1>Network Discovery</h1><p>Review the in-game network scanner output before adding or updating server records. Personal notes are never overwritten and unscanned servers are never deleted.</p></section>
<?php if ($error): ?><div class="notice notice-error" role="alert"><?= escape($error) ?></div><?php endif; ?>
<?php if ($flash): ?><div class="notice <?= $flash['type'] === 'success' ? 'notice-success' : 'notice-error' ?>"><?= escape($flash['message']) ?></div><?php endif; ?>
<section class="discovery-box"><h2>1. Run the scanner in Bitburner</h2><p>Run <code>network-discovery.js</code>, then type <code>cat network-discovery.txt</code>. Copy the entire JSON output into a UTF-8 text file on your computer (for example <code>network-discovery.json</code>). Alternatively use a Bitburner file export method if available.</p><p>The scanner itself is read-only. It uses supported Netscript APIs and writes only the JSON output file in your game.</p></section>
<section class="discovery-box"><h2>2. Preview a discovery file</h2><?php if ($error): ?><p class="notice notice-error" role="alert"><strong>Import not applied:</strong> <?= escape($error) ?></p><?php endif; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= escape($token) ?>"><input type="hidden" name="discovery_action" value="preview"><label for="session">Target gameplay session</label><select id="session" name="session_id" required><?php foreach ($sessions as $s): if ($s['status'] !== 'active') continue; ?><option value="<?= (int)$s['id'] ?>" <?= (int)$s['id']===$selectedId?'selected':'' ?>><?= escape($s['name']) ?></option><?php endforeach; ?></select><label for="discovery-file">Network discovery JSON or TXT (8 MB maximum)</label><input id="discovery-file" name="discovery_file" type="file" accept=".json,.txt,application/json,text/plain" required><button type="submit">Preview changes</button></form></section>
<?php if ($preview !== null): $counts = ['new'=>0,'changed'=>0,'unchanged'=>0]; foreach ($preview as $row) $counts[$row['status']]++; ?>
<section class="discovery-box"><h2>3. Review changes</h2><p><strong><?= $counts['new'] ?> new</strong> · <strong><?= $counts['changed'] ?> changed</strong> · <strong><?= $counts['unchanged'] ?> unchanged</strong></p><p class="hint">Changed fields will be refreshed from the game. Existing server notes remain untouched. Existing servers not in the uploaded scan are kept. No database changes have been made yet.</p><div class="discovery-scroll"><table class="discovery-table"><thead><tr><th>Hostname</th><th>Parent</th><th>Status</th><th>Differences</th></tr></thead><tbody><?php foreach ($preview as $row): ?><tr><td><?= escape($row['item']['hostname']) ?></td><td><?= escape($row['item']['parent'] ?? '—') ?></td><td class="discovery-badge"><?= escape($row['status']) ?></td><td><?= escape(implode(', ', $row['changes']) ?: '—') ?></td></tr><?php endforeach; ?></tbody></table></div><form method="post" class="discovery-actions" data-discovery-confirm><input type="hidden" name="csrf_token" value="<?= escape($token) ?>"><input type="hidden" name="discovery_action" value="apply"><input type="hidden" name="session_id" value="<?= $selectedId ?>"><button type="submit">Confirm and synchronize</button><a href="discovery.php?session=<?= $selectedId ?>">Cancel</a></form></section>
<?php endif; ?><footer>Unofficial local-only Bitburner companion tool.</footer></main><script>document.querySelector('[data-discovery-confirm]')?.addEventListener('submit',event=>{if(!confirm('Apply these network changes to the active session? Existing notes and unscanned servers will be preserved.'))event.preventDefault()})</script></body></html>
