<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/sessions.php';
require_once __DIR__ . '/servers.php';
require_once __DIR__ . '/backups.php';

startBrowserSession();
$page = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
if (!in_array($page, ['index.php', 'dashboard.php', 'sessions.php', 'explorer.php', 'settings.php'], true)) {
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

try {
    $db = database();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        // Never accept a POST on an unrelated page.
        $target = isset($_POST['backup_action']) ? 'settings.php' : (isset($_POST['server_action']) ? 'explorer.php' : 'sessions.php');
        if ($page !== $target) {
            http_response_code(400);
            exit('Invalid form destination');
        }
        $redirect = $target;
        try {
            if ($target === 'settings.php') {
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
            $postSession = filter_var($_POST['game_session_id'] ?? $_POST['session_id'] ?? null, FILTER_VALIDATE_INT);
            if (is_int($postSession) && $postSession > 0) {
                $redirect .= '?session=' . $postSession;
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
} catch (PDOException $exception) {
    $error = 'Database unavailable. Check your .env settings and Laragon MySQL service.';
}
$flash = consumeFlash();
$token = csrfToken();
