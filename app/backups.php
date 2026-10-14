<?php

declare(strict_types=1);

/** Portable, versioned, single-session backups. This is a local-only application. */
const BACKUP_FORMAT = 'bitburner-server-manager-session';
const BACKUP_VERSION = 1;
const BACKUP_MAX_BYTES = 8388608;
const BACKUP_MAX_SERVERS = 10000;

function exportGameBackup(PDO $db, int $sessionId): void
{
    if (!verifyCsrfToken()) {
        throw new InvalidArgumentException('Form expired. Reload Settings and try again.');
    }
    $stmt = $db->prepare('SELECT id, name, bitnode, notes, status FROM game_sessions WHERE id = ?');
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    if (!$session) {
        throw new InvalidArgumentException('The selected game session does not exist.');
    }
    $stmt = $db->prepare('SELECT id, parent_server_id, hostname, has_root_access, has_backdoor, ports_required, hacking_level, ram_gb, notes FROM servers WHERE game_session_id = ? ORDER BY id ASC');
    $stmt->execute([$sessionId]);
    $servers = [];
    foreach ($stmt->fetchAll() as $s) {
        $servers[] = [
            'id' => (int) $s['id'],
            'parent_id' => $s['parent_server_id'] === null ? null : (int) $s['parent_server_id'],
            'hostname' => $s['hostname'],
            'root' => (bool) $s['has_root_access'],
            'backdoor' => (bool) $s['has_backdoor'],
            'ports' => (int) $s['ports_required'],
            'level' => (int) $s['hacking_level'],
            'ram_gb' => (string) $s['ram_gb'],
            'notes' => $s['notes'] ?? '',
        ];
    }
    $payload = [
        'format' => BACKUP_FORMAT,
        'version' => BACKUP_VERSION,
        'exported_at' => gmdate('c'),
        'session' => ['name' => $session['name'], 'bitnode' => $session['bitnode'] ?? '', 'notes' => $session['notes'] ?? ''],
        'servers' => $servers,
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $filename = 'bitburner-session-' . $sessionId . '-' . gmdate('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    echo $json;
}

function backupText(mixed $value, string $field, int $max, bool $required = false): string
{
    if (!is_string($value) || ($required && trim($value) === '') || (function_exists('mb_strlen') ? mb_strlen($value) : strlen($value)) > $max) {
        throw new InvalidArgumentException("Invalid {$field} in the backup.");
    }
    return trim($value);
}

/** Validate the entire document *before* opening a database transaction. */
function validateGameBackup(array $data): array
{
    if (($data['format'] ?? null) !== BACKUP_FORMAT || ($data['version'] ?? null) !== BACKUP_VERSION) {
        throw new InvalidArgumentException('Unsupported backup format or version.');
    }
    if (!isset($data['session'], $data['servers']) || !is_array($data['session']) || !is_array($data['servers']) || !array_is_list($data['servers'])) {
        throw new InvalidArgumentException('Backup session or server list is missing.');
    }
    if (count($data['servers']) > BACKUP_MAX_SERVERS) {
        throw new InvalidArgumentException('Backup contains too many servers.');
    }
    $meta = [
        'name' => backupText($data['session']['name'] ?? null, 'session name', 150, true),
        'bitnode' => backupText($data['session']['bitnode'] ?? '', 'BitNode', 80),
        'notes' => backupText($data['session']['notes'] ?? '', 'session notes', 6000),
    ];
    $byId = [];
    $names = [];
    foreach ($data['servers'] as $s) {
        if (!is_array($s)) {
            throw new InvalidArgumentException('Invalid server entry.');
        }
        $id = $s['id'] ?? null;
        $parent = $s['parent_id'] ?? null;
        if (!is_int($id) || $id < 1 || isset($byId[$id]) || ($parent !== null && (!is_int($parent) || $parent < 1))) {
            throw new InvalidArgumentException('Duplicate or invalid server ID/parent in backup.');
        }
        $hostname = backupText($s['hostname'] ?? null, 'hostname', 255, true);
        $key = function_exists('mb_strtolower') ? mb_strtolower($hostname) : strtolower($hostname);
        if (isset($names[$key])) {
            throw new InvalidArgumentException('Duplicate hostname in backup: ' . $hostname);
        }
        $names[$key] = true;
        if (strcasecmp($hostname, 'home') === 0 && $parent !== null) {
            throw new InvalidArgumentException('Home cannot have a parent.');
        }
        if (!is_bool($s['root'] ?? null) || !is_bool($s['backdoor'] ?? null)) {
            throw new InvalidArgumentException('Root and backdoor values must be true or false.');
        }
        $ports = $s['ports'] ?? null;
        $level = $s['level'] ?? null;
        if (!is_int($ports) || $ports < 0 || $ports > 5 || !is_int($level) || $level < 1 || $level > 2147483647) {
            throw new InvalidArgumentException('Invalid ports or hacking level in backup.');
        }
        $ram = $s['ram_gb'] ?? null;
        if ((!is_string($ram) && !is_int($ram) && !is_float($ram)) || !preg_match('/^\d+(?:\.\d{1,3})?$/D', (string) $ram) || (float) $ram > 99999999999.999) {
            throw new InvalidArgumentException('Invalid RAM value in backup.');
        }
        $byId[$id] = [
            'id' => $id, 'parent_id' => $parent, 'hostname' => $hostname,
            'root' => $s['root'], 'backdoor' => $s['backdoor'], 'ports' => $ports,
            'level' => $level, 'ram_gb' => (string) $ram,
            'notes' => backupText($s['notes'] ?? '', 'server notes', 6000),
        ];
    }
    foreach ($byId as $id => $server) {
        $visited = [];
        $cursor = $id;
        while ($cursor !== null) {
            if (isset($visited[$cursor])) {
                throw new InvalidArgumentException('Circular server hierarchy in backup.');
            }
            $visited[$cursor] = true;
            if (!isset($byId[$cursor])) {
                throw new InvalidArgumentException('A server has a missing parent in the backup.');
            }
            $cursor = $byId[$cursor]['parent_id'];
        }
    }
    return ['session' => $meta, 'servers' => $byId];
}

/** Insert a *new archived* copy. Existing records and active status never change. */
function importGameBackup(PDO $db): int
{
    if (!verifyCsrfToken()) {
        throw new InvalidArgumentException('Form expired. Reload Settings and try again.');
    }
    $file = $_FILES['backup_file'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !is_int($file['size'] ?? null) || $file['size'] < 1 || $file['size'] > BACKUP_MAX_BYTES
        || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new InvalidArgumentException('Upload a valid JSON backup (maximum 8 MB).');
    }
    $contents = file_get_contents($file['tmp_name']);
    if ($contents === false) {
        throw new InvalidArgumentException('Unable to read uploaded backup.');
    }
    try {
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new InvalidArgumentException('Backup is not valid JSON.');
    }
    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON backup document.');
    }
    $validated = validateGameBackup($data);
    $meta = $validated['session'];
    $remaining = $validated['servers'];
    $db->beginTransaction();
    try {
        $insertSession = $db->prepare("INSERT INTO game_sessions (name, bitnode, notes, status) VALUES (?, ?, ?, 'archived')");
        $insertSession->execute([(function_exists('mb_substr') ? mb_substr($meta['name'], 0, 139) : substr($meta['name'], 0, 139)) . ' (imported)', $meta['bitnode'] ?: null, $meta['notes'] ?: null]);
        $sessionId = (int) $db->lastInsertId();
        $insertServer = $db->prepare('INSERT INTO servers (game_session_id, parent_server_id, hostname, has_root_access, has_backdoor, ports_required, hacking_level, ram_gb, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $mapped = [];
        // Insert parents before children, retaining original sibling creation order.
        while ($remaining) {
            $progress = false;
            foreach ($remaining as $oldId => $server) {
                $parent = $server['parent_id'];
                if ($parent !== null && !isset($mapped[$parent])) {
                    continue;
                }
                $insertServer->execute([
                    $sessionId, $parent === null ? null : $mapped[$parent], $server['hostname'],
                    (int) $server['root'], (int) $server['backdoor'], $server['ports'],
                    $server['level'], $server['ram_gb'], $server['notes'] ?: null,
                ]);
                $mapped[$oldId] = (int) $db->lastInsertId();
                unset($remaining[$oldId]);
                $progress = true;
            }
            if (!$progress) {
                throw new InvalidArgumentException('Unable to restore server hierarchy.');
            }
        }
        $db->commit();
        return $sessionId;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }
}
