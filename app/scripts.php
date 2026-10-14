<?php

declare(strict_types=1);

const SCRIPT_MAX_BYTES = 262144;

function validScriptFilename(string $name): bool
{
    return (bool) preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,174}\.js\z/D', $name);
}

function scriptText(mixed $value, string $label, int $max, bool $required = false): string
{
    if (!is_string($value) || strlen($value) > $max || ($required && trim($value) === '')) {
        throw new InvalidArgumentException('Invalid ' . $label . '.');
    }
    return trim($value);
}

function seedBuiltinScripts(PDO $db): int
{
    $path = dirname(__DIR__) . '/resources/builtin-scripts/catalog.json';
    $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($rows)) {
        throw new RuntimeException('Bundled scripts catalog is invalid.');
    }
    $existing = $db->prepare('SELECT id FROM script_entries WHERE builtin_key = ? LIMIT 1');
    $insert = $db->prepare('INSERT INTO script_entries (builtin_key, filename, category, difficulty, description, usage_command, argument_help, dependencies, caution, code, version_label, is_builtin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $count = 0;
    $db->beginTransaction();
    try {
        foreach ($rows as $row) {
            $key = (string) $row['location'];
            $existing->execute([$key]);
            if ($existing->fetchColumn()) {
                continue; // Idempotent, never silently overwrite built-in source.
            }
            $codePath = dirname(__DIR__) . '/resources/builtin-scripts/' . $key;
            $code = file_get_contents($codePath);
            if ($code === false || strlen($code) > SCRIPT_MAX_BYTES) {
                throw new RuntimeException('Missing or invalid bundled source: ' . $key);
            }
            $insert->execute([$key, $row['filename'], $row['category'], $row['level'], $row['description'], $row['usage'], $row['arguments'], $row['dependencies'], $row['caution'], $code, $row['version']]);
            $count++;
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return $count;
}

function loadScript(PDO $db, int $id): ?array
{
    $q = $db->prepare('SELECT * FROM script_entries WHERE id = ?');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

function processScriptPost(PDO $db): string
{
    if (!verifyCsrfToken()) {
        throw new InvalidArgumentException('Form expired. Reload Scripts and try again.');
    }
    $action = $_POST['script_action'] ?? '';
    if ($action === 'seed') {
        $count = seedBuiltinScripts($db);
        setFlash('success', $count . ' bundled scripts installed. Already installed scripts were left unchanged.');
        return 'scripts.php';
    }
    $id = filter_var($_POST['script_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $source = $id ? loadScript($db, (int) $id) : null;
    if ($action === 'restore_revision') {
        if (!$source || (int) $source['is_builtin'] !== 0 || $source['builtin_key'] !== null) {
            throw new InvalidArgumentException('Only personal scripts can restore revisions.');
        }
        $revisionId = filter_var($_POST['revision_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$revisionId) {
            throw new InvalidArgumentException('Select a valid script revision.');
        }
        if (($_POST['confirm_restore'] ?? '') !== 'yes') {
            throw new InvalidArgumentException('Confirm revision restoration before continuing.');
        }
        $db->beginTransaction();
        try {
            // Lock the script first to avoid overwriting a concurrent save.
            $locked = $db->prepare('SELECT id, code, version_label, is_builtin, builtin_key FROM script_entries WHERE id = ? FOR UPDATE');
            $locked->execute([(int) $source['id']]);
            $current = $locked->fetch();
            if (!$current || (int) $current['is_builtin'] !== 0 || $current['builtin_key'] !== null) {
                throw new InvalidArgumentException('This script cannot be restored.');
            }
            // The revision must belong to this exact script; another script's ID is rejected.
            $selectRevision = $db->prepare('SELECT code FROM script_revisions WHERE id = ? AND script_id = ?');
            $selectRevision->execute([(int) $revisionId, (int) $current['id']]);
            $oldCode = $selectRevision->fetchColumn();
            if (!is_string($oldCode)) {
                throw new InvalidArgumentException('That revision was not found for this script.');
            }
            if ($oldCode === $current['code']) {
                throw new InvalidArgumentException('This revision is already the current code.');
            }
            $snapshot = $db->prepare('INSERT INTO script_revisions (script_id, version_label, code) VALUES (?, ?, ?)');
            $snapshot->execute([(int) $current['id'], $current['version_label'], $current['code']]);
            $update = $db->prepare('UPDATE script_entries SET code = ? WHERE id = ? AND is_builtin = 0 AND builtin_key IS NULL');
            $update->execute([$oldCode, (int) $current['id']]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Unable to restore script revision.');
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
        setFlash('success', 'Previous script code restored. Your former current code was saved in revision history.');
        return 'scripts.php?script=' . (int) $source['id'];
    }
    if ($action === 'delete') {
        if (!$source) {
            throw new InvalidArgumentException('The selected personal script no longer exists.');
        }
        if ((int) $source['is_builtin'] === 1 || $source['builtin_key'] !== null) {
            throw new InvalidArgumentException('Protected built-in helper scripts cannot be deleted.');
        }
        // Both the sidebar pill and editor button submit this explicit confirmation.
        if (($_POST['confirm_delete'] ?? '') !== 'yes') {
            throw new InvalidArgumentException('Confirm deletion before continuing.');
        }
        $db->beginTransaction();
        try {
            // ON DELETE CASCADE removes this personal script's revision history.
            $delete = $db->prepare('DELETE FROM script_entries WHERE id = ? AND is_builtin = 0 AND builtin_key IS NULL');
            $delete->execute([(int) $source['id']]);
            if ($delete->rowCount() !== 1) {
                throw new InvalidArgumentException('The script was not deleted; it may be protected or already removed.');
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        setFlash('success', 'Personal script deleted along with its saved revision history.');
        return 'scripts.php';
    }
    if ($action === 'duplicate') {
        if (!$source) {
            throw new InvalidArgumentException('Script not found.');
        }
        $q = $db->prepare('INSERT INTO script_entries (filename, category, difficulty, description, usage_command, argument_help, dependencies, caution, code, version_label, is_builtin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
        $q->execute([$source['filename'], $source['category'], $source['difficulty'], $source['description'], $source['usage_command'], $source['argument_help'], $source['dependencies'], $source['caution'], $source['code'], '1.0.0']);
        $newId = (int) $db->lastInsertId();
        setFlash('success', 'Editable script copy created.');
        return 'scripts.php?script=' . $newId;
    }
    if ($action === 'create' || $action === 'save') {
        if ($action === 'save' && (!$source || (int) $source['is_builtin'] === 1)) {
            throw new InvalidArgumentException('Built-in scripts cannot be edited. Duplicate a script first.');
        }
        $filename = scriptText($_POST['filename'] ?? '', 'filename', 180, true);
        if (!validScriptFilename($filename)) {
            throw new InvalidArgumentException('Use a filename such as my-script.js (letters, digits, dots, dashes, underscores).');
        }
        $category = scriptText($_POST['category'] ?? 'Utilities', 'category', 60, true);
        $level = scriptText($_POST['difficulty'] ?? 'Beginner', 'difficulty', 30, true);
        $description = scriptText($_POST['description'] ?? '', 'description', 3000, true);
        $usage = scriptText($_POST['usage_command'] ?? '', 'usage command', 500);
        $arguments = scriptText($_POST['argument_help'] ?? '', 'arguments', 500);
        $dependencies = scriptText($_POST['dependencies'] ?? '', 'dependencies', 500);
        $caution = scriptText($_POST['caution'] ?? '', 'caution', 3000);
        $code = $_POST['code'] ?? null;
        if (!is_string($code) || trim($code) === '' || strlen($code) > SCRIPT_MAX_BYTES) {
            throw new InvalidArgumentException('Source code is required and must be no larger than 256 KB.');
        }
        if ($action === 'create') {
            $q = $db->prepare('INSERT INTO script_entries (filename, category, difficulty, description, usage_command, argument_help, dependencies, caution, code, version_label, is_builtin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
            $q->execute([$filename, $category, $level, $description, $usage, $arguments, $dependencies, $caution, $code, '1.0.0']);
            $newId = (int) $db->lastInsertId();
        } else {
            $db->beginTransaction();
            try {
                $snapshot = $db->prepare('INSERT INTO script_revisions (script_id, version_label, code) VALUES (?, ?, ?)');
                $snapshot->execute([(int) $source['id'], $source['version_label'], $source['code']]);
                $q = $db->prepare('UPDATE script_entries SET filename = ?, category = ?, difficulty = ?, description = ?, usage_command = ?, argument_help = ?, dependencies = ?, caution = ?, code = ? WHERE id = ? AND is_builtin = 0');
                $q->execute([$filename, $category, $level, $description, $usage, $arguments, $dependencies, $caution, $code, (int) $source['id']]);
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            $newId = (int) $source['id'];
        }
        setFlash('success', $action === 'create' ? 'Script created.' : 'Script saved; previous code added to revision history.');
        return 'scripts.php?script=' . $newId;
    }
    throw new InvalidArgumentException('Unknown script action.');
}

function downloadScript(PDO $db): void
{
    $id = filter_input(INPUT_GET, 'download', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $script = $id ? loadScript($db, (int) $id) : null;
    if (!$script) {
        http_response_code(404);
        exit('Script not found.');
    }
    header('Content-Type: text/javascript; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $script['filename'] . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    echo $script['code'];
}
