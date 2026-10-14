<?php

declare(strict_types=1);

const DISCOVERY_FORMAT = 'bitburner-server-manager-network';
const DISCOVERY_MAX_BYTES = 8388608;
const DISCOVERY_MAX_SERVERS = 10000;

/** Validate the entire uploaded discovery payload; do not trust browser-supplied preview rows. */
function validateDiscovery(array $data): array
{
    if (($data['format'] ?? null) !== DISCOVERY_FORMAT || ($data['version'] ?? null) !== 1 || ($data['root'] ?? null) !== 'home') {
        throw new InvalidArgumentException('Expected a version 1 network-discovery export rooted at home.');
    }
    $records = $data['servers'] ?? null;
    if (!is_array($records) || !array_is_list($records) || !$records || count($records) > DISCOVERY_MAX_SERVERS) {
        throw new InvalidArgumentException('The server list is missing, empty, or too large.');
    }
    $normalized = [];
    foreach ($records as $entry) {
        if (!is_array($entry)) throw new InvalidArgumentException('Invalid server entry.');
        $name = $entry['hostname'] ?? null;
        $parent = $entry['parent'] ?? null;
        if (!is_string($name) || !preg_match('/\A[a-zA-Z0-9._-]{1,255}\z/D', $name) || ($parent !== null && (!is_string($parent) || !preg_match('/\A[a-zA-Z0-9._-]{1,255}\z/D', $parent)))) {
            throw new InvalidArgumentException('Invalid hostname or parent.');
        }
        $key = strtolower($name);
        if (isset($normalized[$key])) throw new InvalidArgumentException('Duplicate server: ' . $name);
        if (($key === 'home') !== ($parent === null)) throw new InvalidArgumentException('Only home may have no parent.');
        if (!is_bool($entry['root'] ?? null) || !is_bool($entry['backdoor'] ?? null)) throw new InvalidArgumentException('Invalid access flags.');
        $ports = $entry['ports'] ?? null;
        $level = $entry['level'] ?? null;
        $ram = $entry['ram_gb'] ?? null;
        if (!is_int($ports) || $ports < 0 || $ports > 5 || !is_int($level) || $level < 0 || $level > 2147483647 || (!is_int($ram) && !is_float($ram)) || (float)$ram < 0 || (float)$ram > 99999999999.999 || !is_finite((float)$ram)) {
            throw new InvalidArgumentException('Invalid ports, skill, or RAM value for ' . $name);
        }
        if ((float)$ram !== round((float)$ram, 3)) throw new InvalidArgumentException('RAM precision exceeds 3 decimals for ' . $name);
        $normalized[$key] = ['hostname'=>$name, 'parent'=>$parent, 'root'=>$entry['root'], 'backdoor'=>$entry['backdoor'], 'ports'=>$ports, 'level'=>$level, 'ram_gb'=>number_format((float)$ram,3,'.','')];
    }
    if (!isset($normalized['home'])) throw new InvalidArgumentException('Discovery must include home.');
    foreach ($normalized as $key=>$entry) {
        $seen = [];
        $at = $key;
        while ($at !== 'home') {
            if (isset($seen[$at])) throw new InvalidArgumentException('Circular parent path detected.');
            $seen[$at] = true;
            if (!isset($normalized[$at])) throw new InvalidArgumentException('Missing parent in discovery.');
            $parent = $normalized[$at]['parent'];
            if ($parent === null) throw new InvalidArgumentException('Disconnected discovery branch.');
            $at = strtolower($parent);
            if (!isset($normalized[$at])) throw new InvalidArgumentException('Unknown parent ' . $parent);
        }
    }
    return $normalized;
}

/** Shows a complete deterministic diff without editing the database. */
function discoveryDiff(array $incoming, array $existing): array
{
    $found = [];
    foreach ($existing as $s) $found[strtolower((string)$s['hostname'])] = $s;
    $rows = [];
    foreach ($incoming as $key=>$s) {
        $old = $found[$key] ?? null;
        $oldParent = $old ? ($old['parent_hostname'] ?? null) : null;
        $changes = [];
        if ($old) {
            $fields = ['root'=>'has_root_access','backdoor'=>'has_backdoor','ports'=>'ports_required','level'=>'hacking_level','ram_gb'=>'ram_gb'];
            foreach ($fields as $new=>$previous) {
                $different = $new === 'ram_gb' ? abs((float)$s[$new] - (float)$old[$previous]) > 0.0005 : (int)$s[$new] !== (int)$old[$previous];
                if ($different) $changes[] = $new;
            }
            if (strtolower((string)$oldParent) !== strtolower((string)$s['parent'])) $changes[] = 'parent';
        }
        $rows[] = ['item'=>$s,'status'=>$old===null?'new':($changes?'changed':'unchanged'), 'changes'=>$changes];
    }
    return $rows;
}

function discoveryExisting(PDO $db, int $sessionId): array
{
    $q = $db->prepare('SELECT s.*, p.hostname AS parent_hostname FROM servers s LEFT JOIN servers p ON p.id = s.parent_server_id WHERE s.game_session_id = ? ORDER BY s.id');
    $q->execute([$sessionId]);
    return $q->fetchAll();
}

/** Apply all-or-nothing; preserve manual notes, old hosts missing from scan, and active game selection. */
function applyDiscovery(PDO $db, int $sessionId, array $incoming): array
{
    $db->beginTransaction();
    try {
        $check = $db->prepare('SELECT status FROM game_sessions WHERE id = ? FOR UPDATE');
        $check->execute([$sessionId]);
        if ($check->fetchColumn() !== 'active') throw new InvalidArgumentException('Select an active gameplay session. Archived sessions cannot be synchronized.');
        $existing = discoveryExisting($db, $sessionId);
        $byName = [];
        foreach ($existing as $s) $byName[strtolower((string)$s['hostname'])] = $s;
        $insert = $db->prepare('INSERT INTO servers (game_session_id, parent_server_id, hostname, has_root_access, has_backdoor, ports_required, hacking_level, ram_gb, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)');
        $update = $db->prepare('UPDATE servers SET parent_server_id=?, has_root_access=?, has_backdoor=?, ports_required=?, hacking_level=?, ram_gb=? WHERE id=? AND game_session_id=?');
        $ids = []; $pending = $incoming; $added = 0; $changed = 0;
        while ($pending) {
            $progress = false;
            foreach ($pending as $key => $s) {
                $parentKey = $s['parent'] === null ? null : strtolower($s['parent']);
                if ($parentKey !== null && !isset($ids[$parentKey])) continue;
                $parentId = $parentKey === null ? null : $ids[$parentKey];
                $old = $byName[$key] ?? null;
                if (!$old) {
                    $insert->execute([$sessionId,$parentId,$s['hostname'],(int)$s['root'],(int)$s['backdoor'],$s['ports'],$s['level'],$s['ram_gb']]);
                    $ids[$key] = (int)$db->lastInsertId(); $added++;
                } else {
                    $ids[$key] = (int)$old['id'];
                    $needsUpdate = ($old['parent_server_id'] === null ? null : (int)$old['parent_server_id']) !== $parentId
                        || (int)$old['has_root_access'] !== (int)$s['root'] || (int)$old['has_backdoor'] !== (int)$s['backdoor']
                        || (int)$old['ports_required'] !== $s['ports'] || (int)$old['hacking_level'] !== $s['level']
                        || abs((float)$old['ram_gb']-(float)$s['ram_gb'])>0.0005;
                    if ($needsUpdate) {
                        $update->execute([$parentId,(int)$s['root'],(int)$s['backdoor'],$s['ports'],$s['level'],$s['ram_gb'],$ids[$key],$sessionId]);
                        $changed++;
                    }
                }
                unset($pending[$key]); $progress = true;
            }
            if (!$progress) throw new InvalidArgumentException('Cannot resolve discovery parents.');
        }
        $db->commit();
        return ['added'=>$added,'updated'=>$changed];
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
