<?php

declare(strict_types=1);

/** Local-first server editor. Database enforces unique hostnames per run. */
function serverField(string $name, int $max = 255, bool $required = false): string
{
    $value = $_POST[$name] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Invalid server field.');
    }
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $max) {
        throw new InvalidArgumentException("Invalid {$name} (maximum {$max} characters).");
    }
    return $value;
}

function serverNumber(string $name, int $min, int $max): int
{
    $raw = $_POST[$name] ?? null;
    if (!is_string($raw) || !preg_match('/^\d+$/D', $raw)) {
        throw new InvalidArgumentException("Invalid {$name}.");
    }
    $value = filter_var($raw, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new InvalidArgumentException("{$name} must be between {$min} and {$max}.");
    }
    return $value;
}

function serverOptionalId(string $field): ?int
{
    $value = $_POST[$field] ?? '';
    if ($value === '' || $value === null) {
        return null;
    }
    if (!is_string($value) || !ctype_digit($value) || (int) $value < 1) {
        throw new InvalidArgumentException('Invalid server relationship.');
    }
    return (int) $value;
}

function sessionForServerWrite(PDO $db, int $sessionId): void
{
    $query = $db->prepare('SELECT status FROM game_sessions WHERE id = ?');
    $query->execute([$sessionId]);
    if ($query->fetchColumn() !== 'active') {
        throw new InvalidArgumentException('Only an active gameplay session can be edited.');
    }
}

function validateServerParent(PDO $db, int $sessionId, ?int $parentId, ?int $serverId): void
{
    if ($parentId === null) {
        return;
    }
    $query = $db->prepare('SELECT id, parent_server_id FROM servers WHERE id = ? AND game_session_id = ?');
    $seen = [];
    $cursor = $parentId;
    while ($cursor !== null) {
        if (isset($seen[$cursor]) || ($serverId !== null && $cursor === $serverId)) {
            throw new InvalidArgumentException('A server cannot be nested inside itself or one of its descendants.');
        }
        $seen[$cursor] = true;
        $query->execute([$cursor, $sessionId]);
        $row = $query->fetch();
        if (!$row) {
            throw new InvalidArgumentException('The parent server must belong to this gameplay session.');
        }
        $cursor = $row['parent_server_id'] === null ? null : (int) $row['parent_server_id'];
    }
}

/** Validate entered RAM and convert it into the canonical GB value. */
function serverRamGb(): string
{
    $raw = $_POST['ram_amount'] ?? null;
    $unit = $_POST['ram_unit'] ?? null;
    if (!is_string($raw) || !preg_match('/^(?:\d+)(?:\.\d{1,3})?$/D', $raw)
        || !is_string($unit) || !in_array($unit, ['GB', 'TB'], true)) {
        throw new InvalidArgumentException('Enter a valid RAM amount (up to 3 decimal places) and choose GB or TB.');
    }
    $value = (float) $raw;
    $gb = $unit === 'TB' ? $value * 1024 : $value;
    if (!is_finite($gb) || $gb < 0 || $gb > 99999999999.999) {
        throw new InvalidArgumentException('RAM amount is outside the supported range.');
    }
    return number_format($gb, 3, '.', '');
}

/** Show large values in TB, while keeping small values in GB. */
function formatServerRam(string|int|float|null $value): string
{
    $gb = (float) ($value ?? 0);
    if ($gb <= 0) {
        return 'Not recorded';
    }
    $unit = $gb >= 1024 ? 'TB' : 'GB';
    $amount = $gb >= 1024 ? $gb / 1024 : $gb;
    return rtrim(rtrim(number_format($amount, 3, '.', ''), '0'), '.') . ' ' . $unit;
}

/** Returns new/updated server id; no hard-coded nesting limit. */
function processServerPost(PDO $db): int
{
    if (!verifyCsrfToken()) {
        throw new InvalidArgumentException('Your form expired. Refresh and try again.');
    }
    $sessionId = serverNumber('game_session_id', 1, PHP_INT_MAX);
    $db->beginTransaction();
    try {
        sessionForServerWrite($db, $sessionId);
        $action = $_POST['server_action'] ?? '';
        if (!in_array($action, ['create', 'update'], true)) {
            throw new InvalidArgumentException('Unknown server action.');
        }
        $id = $action === 'update' ? serverNumber('server_id', 1, PHP_INT_MAX) : null;
        if ($id !== null) {
            $check = $db->prepare('SELECT id FROM servers WHERE id = ? AND game_session_id = ?');
            $check->execute([$id, $sessionId]);
            if (!$check->fetchColumn()) {
                throw new InvalidArgumentException('This server does not belong to the selected session.');
            }
        }
        $hostname = serverField('hostname', 255, true);
        $parentId = serverOptionalId('parent_server_id');
        if (strcasecmp($hostname, 'home') === 0 && $parentId !== null) {
            throw new InvalidArgumentException('The home server must be at the root of the tree.');
        }
        validateServerParent($db, $sessionId, $parentId, $id);
        $root = isset($_POST['has_root_access']) ? 1 : 0;
        $backdoor = isset($_POST['has_backdoor']) ? 1 : 0;
        $ports = serverNumber('ports_required', 0, 5);
        $level = serverNumber('hacking_level', 1, 2147483647);
        $notes = serverField('notes', 6000);
        $ramGb = serverRamGb();
        if ($id === null) {
            $insert = $db->prepare('INSERT INTO servers (game_session_id, parent_server_id, hostname, has_root_access, has_backdoor, ports_required, hacking_level, ram_gb, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$sessionId, $parentId, $hostname, $root, $backdoor, $ports, $level, $ramGb, $notes ?: null]);
            $id = (int) $db->lastInsertId();
        } else {
            $update = $db->prepare('UPDATE servers SET parent_server_id = ?, hostname = ?, has_root_access = ?, has_backdoor = ?, ports_required = ?, hacking_level = ?, ram_gb = ?, notes = ? WHERE id = ? AND game_session_id = ?');
            $update->execute([$parentId, $hostname, $root, $backdoor, $ports, $level, $ramGb, $notes ?: null, $id, $sessionId]);
        }
        $db->commit();
        return $id;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($exception instanceof PDOException && $exception->getCode() === '23000') {
            throw new InvalidArgumentException('That hostname already exists in this session.');
        }
        throw $exception;
    }
}

/**
 * Generate a depth-first list of servers in creation order within each parent.
 * Iterative traversal avoids a fixed recursion depth and guards against corrupt cycles.
 * Each entry contains the original row and its nesting depth.
 *
 * @return array<int, array{server: array, depth: int}>
 */
function orderedServerHierarchy(array $servers): array
{
    $children = [];
    foreach ($servers as $server) {
        $parent = $server['parent_server_id'] === null ? 0 : (int) $server['parent_server_id'];
        $children[$parent][] = $server;
    }
    foreach ($children as &$siblings) {
        usort($siblings, static fn(array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);
    }
    unset($siblings);

    $stack = [];
    foreach (array_reverse($children[0] ?? []) as $root) {
        $stack[] = [$root, 0];
    }
    $visited = [];
    $result = [];
    while ($stack) {
        [$server, $depth] = array_pop($stack);
        $id = (int) $server['id'];
        if (isset($visited[$id])) {
            continue;
        }
        $visited[$id] = true;
        $result[] = ['server' => $server, 'depth' => $depth];
        foreach (array_reverse($children[$id] ?? []) as $child) {
            $stack[] = [$child, $depth + 1];
        }
    }
    return $result;
}

/** Exclude an edited server and its descendants from valid parent candidates. */
function excludedParentIds(array $servers, int $serverId): array
{
    $children = [];
    foreach ($servers as $server) {
        if ($server['parent_server_id'] !== null) {
            $children[(int) $server['parent_server_id']][] = (int) $server['id'];
        }
    }
    $excluded = [];
    $pending = [$serverId];
    while ($pending) {
        $id = array_pop($pending);
        if (isset($excluded[$id])) {
            continue;
        }
        $excluded[$id] = true;
        foreach ($children[$id] ?? [] as $childId) {
            $pending[] = $childId;
        }
    }
    return $excluded;
}
