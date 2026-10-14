<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/sessions.php';
require_once __DIR__ . '/servers.php';
require_once __DIR__ . '/backups.php';
require_once __DIR__ . '/scripts.php';

startBrowserSession();
$page = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
if (!in_array($page, ['index.php', 'dashboard.php', 'sessions.php', 'explorer.php', 'settings.php', 'scripts.php'], true)) {
    http_response_code(404);
    exit('Page not found');
}
$db = null;
$error = null;
$sessions = [];
$selected = null;
$servers = [];
$hierarchy = [];
$selectedServer = null;
$flash = null;
$scriptList = [];
$activeScript = null;
$scriptRevisions = [];
$newScript = false;

try {
    $db = database();
    if ($page === 'scripts.php' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['download'])) {
        downloadScript($db);
        exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $target = isset($_POST['script_action']) ? 'scripts.php' : (isset($_POST['backup_action']) ? 'settings.php' : (isset($_POST['server_action']) ? 'explorer.php' : 'sessions.php'));
        if ($page !== $target) {
            http_response_code(400);
            exit('Invalid form destination');
        }
        $redirect = $target;
        try {
            if ($target === 'scripts.php') {
                $redirect = processScriptPost($db);
            } elseif ($target === 'settings.php') {
                $action = $_POST['backup_action'] ?? '';
                if ($action === 'export') {
                    $sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if (!$sessionId) {
                        throw new InvalidArgumentException('Select a valid gameplay session.');
                    }
                    exportGameBackup($db, (int) $sessionId);
                    exit;
                } elseif ($action === 'import') {
                    $newId = importGameBackup($db);
                    setFlash('success', 'Backup imported as a new archived session.');
                    $redirect = 'sessions.php?session=' . $newId;
                } else {
                    throw new InvalidArgumentException('Unknown backup action.');
                }
            } elseif ($target === 'explorer.php') {
                $serverId = processServerPost($db);
                $sessionId = (int) ($_POST['game_session_id'] ?? 0);
                $redirect = 'explorer.php?session=' . $sessionId . '&server=' . $serverId;
                setFlash('success', 'Server saved successfully.');
            } else {
                $requestedSessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT);
                $action = $_POST['action'] ?? '';
                processSessionPost($db);
                if (is_int($requestedSessionId) && $requestedSessionId > 0 && $action !== 'create') {
                    $redirect .= '?session=' . $requestedSessionId;
                }
            }
        } catch (InvalidArgumentException $exception) {
            setFlash('error', $exception->getMessage());
            if ($target === 'explorer.php' || $target === 'sessions.php') {
                $postSession = filter_var($_POST['game_session_id'] ?? $_POST['session_id'] ?? null, FILTER_VALIDATE_INT);
                if (is_int($postSession) && $postSession > 0) {
                    $redirect .= '?session=' . $postSession;
                }
            } elseif ($target === 'scripts.php') {
                $scriptId = filter_var($_POST['script_id'] ?? null, FILTER_VALIDATE_INT);
                if ($scriptId) $redirect .= '?script=' . (int) $scriptId;
            }
        }
        header('Location: ' . $redirect, true, 303);
        exit;
    }
    $sessions = $db->query("SELECT id, name, bitnode, notes, status, created_at, updated_at FROM game_sessions ORDER BY (status = 'active') DESC, created_at DESC, id DESC")->fetchAll();
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
    if ($selected !== null) {
        $fetchServers = $db->prepare('SELECT * FROM servers WHERE game_session_id = ? ORDER BY id ASC');
        $fetchServers->execute([(int) $selected['id']]);
        $servers = $fetchServers->fetchAll();
        $hierarchy = orderedServerHierarchy($servers);
        $requestedServer = filter_input(INPUT_GET, 'server', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        foreach ($servers as $server) {
            if ($requestedServer && (int) $server['id'] === (int) $requestedServer) {
                $selectedServer = $server;
                break;
            }
        }
    }
    if ($page === 'scripts.php') {
        $scriptList = $db->query('SELECT id, filename, category, description, is_builtin, builtin_key FROM script_entries ORDER BY is_builtin DESC, category, filename, id')->fetchAll();
        $newScript = isset($_GET['new']);
        $scriptId = filter_input(INPUT_GET, 'script', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$newScript && $scriptId) {
            $activeScript = loadScript($db, (int) $scriptId);
        }
        if (!$newScript && !$activeScript && $scriptList) {
            $activeScript = loadScript($db, (int) $scriptList[0]['id']);
        }
        if ($activeScript && !(int) $activeScript['is_builtin']) {
            $revisionsQuery = $db->prepare('SELECT id, version_label, code, created_at FROM script_revisions WHERE script_id = ? ORDER BY id DESC LIMIT 20');
            $revisionsQuery->execute([(int) $activeScript['id']]);
            $scriptRevisions = $revisionsQuery->fetchAll();
        }
    }
} catch (PDOException $exception) {
    $error = $page === 'scripts.php' ? 'Database unavailable or Scripts migration not applied. Run database/migrations/002_scripts_library.sql once in HeidiSQL.' : 'Database unavailable. Check your .env settings and Laragon MySQL service.';
}
$flash = consumeFlash();
$token = csrfToken();
