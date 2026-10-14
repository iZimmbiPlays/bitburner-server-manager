<section class="page-intro"><p class="eyebrow">APPLICATION</p><h1>Settings</h1><p>Local setup, data storage and portable session backups.</p></section>
<section class="panel pane-body">
    <h2>Application information</h2>
    <dl class="settings-grid">
        <dt>Version</dt><dd>v0.2.0-dev</dd>
        <dt>Database</dt><dd><?= $db ? 'Connected' : 'Setup required' ?></dd>
        <dt>Data storage</dt><dd>Local MySQL database</dd>
        <dt>Server nesting</dt><dd>No artificial depth limit</dd>
    </dl>
    <h2>Export a gameplay session</h2>
    <p class="hint">Download a JSON file with the selected session's details, servers, RAM, notes, and parent connections.</p>
    <?php if ($db && $sessions): ?>
        <form method="post" class="session-form backup-form">
            <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
            <input type="hidden" name="backup_action" value="export">
            <label for="backup-session">Gameplay session</label>
            <select name="session_id" id="backup-session" required>
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $selected && (int) $selected['id'] === (int) $s['id'] ? 'selected' : '' ?>><?= escape($s['name']) ?><?= $s['status'] === 'archived' ? ' (archived)' : '' ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Download JSON backup</button>
        </form>
    <?php else: ?><p class="hint">Create a session before exporting.</p><?php endif; ?>
    <h2>Import a gameplay session</h2>
    <p class="hint">Restore a JSON export as a <strong>new archived session</strong>. Existing games and the active session will not be replaced. To edit the imported game, reactivate it from Sessions.</p>
    <?php if ($db): ?>
        <form method="post" enctype="multipart/form-data" class="session-form backup-form">
            <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
            <input type="hidden" name="backup_action" value="import">
            <label for="backup-file">JSON backup file (up to 8 MB)</label>
            <input type="file" id="backup-file" name="backup_file" accept=".json,application/json" required>
            <button type="submit">Import as archived session</button>
        </form>
    <?php endif; ?>
    <h2>Security</h2>
    <p class="hint">This app has no login system and is for local use only. Store downloaded backups safely: they contain all saved session notes and server details. Continue exporting your full MySQL database in HeidiSQL before major upgrades.</p>
</section>
