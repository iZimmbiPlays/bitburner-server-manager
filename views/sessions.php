<section class="page-intro"><p class="eyebrow">GAMEPLAY HISTORY</p><h1>Gameplay Sessions</h1><p>Create new runs and revisit archived playthroughs.</p></section>
<section class="workspace" aria-label="Gameplay sessions"><aside class="panel"><div class="panel-heading"><h2>Saved sessions</h2><span><?= count($sessions) ?> TOTAL</span></div><div class="pane-body">
                <h3>Create new session</h3>
                <form method="post" class="session-form">
                    <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                    <input type="hidden" name="action" value="create">
                    <label for="new-name">Session name *</label>
                    <input id="new-name" name="name" maxlength="150" required placeholder="BitNode 1 — Run #001" <?= $db === null ? 'disabled' : '' ?>>
                    <label for="new-bitnode">BitNode (optional)</label>
                    <input id="new-bitnode" name="bitnode" maxlength="80" placeholder="BitNode 1" <?= $db === null ? 'disabled' : '' ?>>
                    <label for="new-notes">Notes (optional)</label>
                    <textarea id="new-notes" name="notes" maxlength="6000" rows="2" placeholder="Goals for this run..." <?= $db === null ? 'disabled' : '' ?>></textarea>
                    <button type="submit" <?= $db === null ? 'disabled' : '' ?>>+ Create session</button>
                </form>
                <h3 class="session-list-title">Active game</h3>
                <nav class="session-list" aria-label="Active gameplay session">
                    <?php $activeCount = 0; foreach ($sessions as $session): if ($session['status'] !== 'active') continue; $activeCount++; ?>
                        <a class="session-link <?= $selected !== null && $selected['id'] === $session['id'] ? 'is-selected' : '' ?>" href="sessions.php?session=<?= (int) $session['id'] ?>">
                            <strong><?= escape($session['name']) ?></strong><span class="session-meta"><?= escape($session['bitnode'] ?: 'Unspecified BitNode') ?> · Active</span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$activeCount): ?><p class="hint">No active session. Create or reactivate one.</p><?php endif; ?>
                </nav>
                <?php $archivedCount = count(array_filter($sessions, static fn(array $s): bool => $s['status'] === 'archived')); ?>
                <details class="archive-drawer" <?= $selected !== null && $selected['status'] === 'archived' ? 'open' : '' ?>>
                    <summary>Archived sessions <span><?= $archivedCount ?></span></summary>
                    <nav class="session-list" aria-label="Archived gameplay sessions">
                        <?php foreach ($sessions as $session): if ($session['status'] !== 'archived') continue; ?>
                            <a class="session-link <?= $selected !== null && $selected['id'] === $session['id'] ? 'is-selected' : '' ?>" href="sessions.php?session=<?= (int) $session['id'] ?>">
                                <strong><?= escape($session['name']) ?></strong><span class="session-meta"><?= escape($session['bitnode'] ?: 'Unspecified BitNode') ?> · Archived</span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$archivedCount): ?><p class="hint">No archived games yet.</p><?php endif; ?>
                    </nav>
                </details>
</div></aside><section class="panel"><div class="panel-heading"><h2>Session details</h2><span>EDIT / ARCHIVE</span></div><div class="pane-body">
                <?php if ($selected === null): ?>
                    <div class="empty"><span class="icon">⚙</span><h3>Select or create a session</h3><p>Session details will appear here. Server tracking comes in the next milestone.</p></div>
                <?php else: ?>
                    <div class="selected-heading"><h3><?= escape($selected['name']) ?></h3><span class="state <?= $selected['status'] === 'active' ? 'state-active' : '' ?>"><?= escape($selected['status']) ?></span></div>
                    <p class="hint">Created <?= escape($selected['created_at']) ?> · Last updated <?= escape($selected['updated_at']) ?></p>
                    <form method="post" class="session-form">
                        <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                        <input type="hidden" name="session_id" value="<?= (int) $selected['id'] ?>">
                        <input type="hidden" name="action" value="update">
                        <label for="edit-name">Session name *</label>
                        <input id="edit-name" name="name" maxlength="150" required value="<?= escape($selected['name']) ?>">
                        <label for="edit-bitnode">BitNode</label>
                        <input id="edit-bitnode" name="bitnode" maxlength="80" value="<?= escape($selected['bitnode'] ?? '') ?>">
                        <label for="edit-notes">Notes</label>
                        <textarea id="edit-notes" name="notes" maxlength="6000" rows="5"><?= escape($selected['notes'] ?? '') ?></textarea>
                        <button type="submit">Save changes</button>
                    </form>
                    <div class="session-actions">
                        <?php if ($selected['status'] === 'archived'): ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="session_id" value="<?= (int) $selected['id'] ?>">
                                <button class="secondary-button" type="submit">Make active</button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="session_id" value="<?= (int) $selected['id'] ?>">
                                <button class="secondary-button" type="submit">Archive session</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <p class="hint">Archiving preserves the session and its future server data. No sessions are deleted here.</p>
                <?php endif; ?>
</div></section></section>
