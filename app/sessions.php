<?php

declare(strict_types=1);

/** Session management for a local-only installation. */
function startBrowserSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function csrfToken(): string
{
    startBrowserSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): bool
{
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && hash_equals(csrfToken(), $_POST['csrf_token']);
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function consumeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function sessionInput(string $field, int $maxLength, bool $required = false): string
{
    $value = $_POST[$field] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Invalid input.');
    }
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException($required
            ? "{$field} is required and must not exceed {$maxLength} characters."
            : "{$field} must not exceed {$maxLength} characters.");
    }
    return $value;
}

function postedSessionId(): int
{
    $id = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        throw new InvalidArgumentException('Choose a valid gameplay session.');
    }
    return $id;
}

/** Only one session should be active at any time; older sessions remain in MySQL. */
function activateGameSession(PDO $db, int $id): void
{
    $db->beginTransaction();
    try {
        $lookup = $db->prepare('SELECT id FROM game_sessions WHERE id = ? FOR UPDATE');
        $lookup->execute([$id]);
        if (!$lookup->fetchColumn()) {
            throw new InvalidArgumentException('That gameplay session does not exist.');
        }
        $db->exec("UPDATE game_sessions SET status = 'archived' WHERE status = 'active'");
        $update = $db->prepare("UPDATE game_sessions SET status = 'active' WHERE id = ?");
        $update->execute([$id]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function processSessionPost(PDO $db): void
{
    if (!verifyCsrfToken()) {
        throw new InvalidArgumentException('The form expired. Refresh the page and try again.');
    }
    $action = $_POST['action'] ?? '';
    if (!is_string($action)) {
        throw new InvalidArgumentException('Unknown action.');
    }
    switch ($action) {
        case 'create':
            $name = sessionInput('name', 150, true);
            $bitnode = sessionInput('bitnode', 80);
            $notes = sessionInput('notes', 6000);
            $db->beginTransaction();
            try {
                $db->exec("UPDATE game_sessions SET status = 'archived' WHERE status = 'active'");
                $insert = $db->prepare("INSERT INTO game_sessions (name, bitnode, notes, status) VALUES (?, ?, ?, 'active')");
                $insert->execute([$name, $bitnode ?: null, $notes ?: null]);
                $db->commit();
            } catch (Throwable $exception) {
                $db->rollBack();
                throw $exception;
            }
            setFlash('success', 'New gameplay session created.');
            break;
        case 'update':
            $id = postedSessionId();
            $name = sessionInput('name', 150, true);
            $bitnode = sessionInput('bitnode', 80);
            $notes = sessionInput('notes', 6000);
            $update = $db->prepare('UPDATE game_sessions SET name = ?, bitnode = ?, notes = ? WHERE id = ?');
            $update->execute([$name, $bitnode ?: null, $notes ?: null, $id]);
            setFlash('success', 'Gameplay session saved.');
            break;
        case 'activate':
            activateGameSession($db, postedSessionId());
            setFlash('success', 'Selected gameplay session is now active.');
            break;
        case 'archive':
            $id = postedSessionId();
            $update = $db->prepare("UPDATE game_sessions SET status = 'archived' WHERE id = ?");
            $update->execute([$id]);
            setFlash('success', 'Gameplay session archived.');
            break;
        default:
            throw new InvalidArgumentException('Unknown action.');
    }
}
