<section class="page-intro"><p class="eyebrow">NETWORK MAPPING</p><h1>Server Explorer</h1><p>Explore a saved server hierarchy and update each server's access status.</p></section>
<?php if ($selected === null): ?>
<div class="panel pane-body"><p class="hint">No game sessions yet. <a href="sessions.php">Create a session</a> to start mapping.</p></div>
<?php else: ?>
<div class="context-line"><strong><?= escape($selected['name']) ?></strong><span class="state <?= $selected['status'] === 'active' ? 'state-active' : '' ?>"><?= escape($selected['status']) ?></span><a href="sessions.php?session=<?= (int)$selected['id'] ?>">Manage session</a></div>
<section class="workspace explorer-workspace" aria-label="Server explorer"><aside class="panel"><div class="panel-heading"><h2>Server network</h2><span><?= count($servers) ?> SERVERS</span></div><div class="pane-body">
                    <section class="server-explorer" aria-label="Server network">
                        <h3>Server network <small>(<?= count($servers) ?>)</small></h3>
                        <p class="hint">Servers are listed in the order they were added under each parent. Ports, level, and notes are shown below each name.</p>
                        <?php if ($selected['status'] === 'active'): ?>
                            <a class="server-new" href="explorer.php?session=<?= (int)$selected['id'] ?>&new_server=1">+ Add server</a>
                        <?php endif; ?>
                        <form class="server-filter-panel" id="server-filters" role="search" aria-label="Filter recorded servers">
                            <div class="server-filter-heading"><strong>Find servers</strong><button type="reset" class="server-filter-clear">Clear filters</button></div>
                            <label for="filter-search">Hostname or notes</label>
                            <input id="filter-search" type="search" placeholder="Search servers or notes..." autocomplete="off">
                            <div class="server-filter-grid">
                                <div><label for="filter-root">Root access</label><select id="filter-root"><option value="any">Any</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                <div><label for="filter-backdoor">Backdoor installed</label><select id="filter-backdoor"><option value="any">Any</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                <div><label for="filter-ports">Ports required</label><select id="filter-ports"><option value="any">Any</option><?php for ($port = 0; $port <= 5; $port++): ?><option value="<?= $port ?>"><?= $port ?></option><?php endfor; ?></select></div>
                                <div><label for="filter-level-max">Maximum hacking level</label><input id="filter-level-max" type="number" min="1" step="1" placeholder="Any"></div>
                                <div><label for="filter-ram-min">Minimum RAM (GB)</label><input id="filter-ram-min" type="number" min="0" step="any" placeholder="Any"></div>
                            </div>
                            <p id="server-filter-count" class="server-filter-count" role="status" aria-live="polite"><?= count($servers) ?> matching servers</p>
                            <p class="hint">Matching servers keep their connection ancestors visible. Filters do not modify saved data.</p>
                        </form>
                        <?php
                            $children = [];
                            foreach ($servers as $sv) $children[$sv['parent_server_id'] === null ? 0 : (int)$sv['parent_server_id']][] = $sv;
                            $renderTree = static function (int $parent, array $ancestors = []) use (&$renderTree, $children, $selected, $selectedServer): void {
                                if (empty($children[$parent])) return;
                                echo '<ul class="server-tree">';
                                foreach ($children[$parent] as $sv) {
                                    $id = (int)$sv['id'];
                                    if (isset($ancestors[$id])) continue;
                                    $more = $ancestors; $more[$id] = true;
                                    $label = escape($sv['hostname']);
                                    $target = 'explorer.php?session='.(int)$selected['id'].'&server='.$id;
                                    $style = ($selectedServer !== null && (int)$selectedServer['id'] === $id) ? ' is-selected' : '';
                                    echo '<li class="server-filter-item" data-server-host="'.escape(mb_strtolower((string)$sv['hostname'])).'" data-server-notes="'.escape(mb_strtolower((string)($sv['notes'] ?? ''))).'" data-root="'.((int)$sv['has_root_access'] ? 'yes' : 'no').'" data-backdoor="'.((int)$sv['has_backdoor'] ? 'yes' : 'no').'" data-ports="'.(int)$sv['ports_required'].'" data-level="'.(int)$sv['hacking_level'].'" data-ram="'.escape((string)($sv['ram_gb'] ?? '0')).'">';
                                    if (!empty($children[$id])) echo '<details open><summary><a class="server-node'.$style.'" href="'.$target.'">'.$label.'</a></summary>';
                                    else echo '<a class="server-node'.$style.'" href="'.$target.'">'.$label.'</a>';
                                    echo '<span class="server-flags">'.((int)$sv['has_root_access'] ? '✓ root' : '○ root').' · '.((int)$sv['has_backdoor'] ? '✓ backdoor' : '○ backdoor').' · Ports: '.(int)$sv['ports_required'].' · Level: '.(int)$sv['hacking_level'].' · RAM: '.escape(formatServerRam($sv['ram_gb'] ?? 0)).'</span>';
                                     if (trim((string)($sv['notes'] ?? '')) !== '') {
                                         echo '<span class="server-inline-notes">'.nl2br(escape((string)$sv['notes']), false).'</span>';
                                     }
                                    if (!empty($children[$id])) { $renderTree($id, $more); echo '</details>'; }
                                    echo '</li>';
                                }
                                echo '</ul>';
                            };
                            $renderTree(0);
                        ?>
                        <?php if (!$servers): ?><p class="hint">No servers recorded for this game yet.</p><?php endif; ?>
                    </section>
</div></aside><section class="panel"><div class="panel-heading"><h2>Server details</h2><span>ADD / EDIT</span></div><div class="pane-body">
                    <?php if ($selectedServer !== null): ?>
                        <?php $connection = serverConnectionPath($servers, (int) $selectedServer['id']); ?>
                        <section class="connection-panel" aria-label="Connection path">
                            <h3>Connection path</h3>
                            <?php if ($connection['available']): ?>
                                <p class="hint">Route from home to <strong><?= escape($selectedServer['hostname']) ?></strong></p>
                                <p class="connection-breadcrumb"><?= escape(implode(' → ', $connection['hosts'])) ?></p>
                                <label class="connection-label" for="connection-command">Bitburner terminal commands</label>
                                <textarea id="connection-command" class="connection-command" readonly rows="2" spellcheck="false"><?= escape($connection['commands']) ?></textarea>
                                <button type="button" class="connection-copy" data-copy-connection="connection-command">Copy terminal commands</button>
                                <p class="connection-feedback" role="status" aria-live="polite"></p>
                            <?php else: ?>
                                <p class="connection-warning" role="status"><?= escape($connection['reason']) ?></p>
                                <p class="hint">Review the saved parent connections before generating a route.</p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                    <section class="server-editor" aria-label="Server editor">
                        <h3><?= $selectedServer ? 'Edit server' : 'Add a server' ?></h3>
                        <?php if ($selected['status'] !== 'active'): ?>
                            <p class="hint">This game is archived. Reactivate it to edit or add servers. You can still browse its network.</p>
                        <?php else: ?>
                            <form class="session-form" method="post">
                                <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                                <input type="hidden" name="server_action" value="<?= $selectedServer ? 'update' : 'create' ?>">
                                <input type="hidden" name="game_session_id" value="<?= (int)$selected['id'] ?>">
                                <?php if ($selectedServer): ?><input type="hidden" name="server_id" value="<?= (int)$selectedServer['id'] ?>"><?php endif; ?>
                                <label for="hostname">Server hostname *</label>
                                <input id="hostname" name="hostname" required maxlength="255" value="<?= escape($selectedServer['hostname'] ?? '') ?>" placeholder="n00dles">
                                <label for="parent-server">Connected through / parent server</label>
                                <select id="parent-server" name="parent_server_id">
                                    <option value="">Top-level (no parent)</option>
                                    <?php
                                        $excludedParents = $selectedServer
                                            ? excludedParentIds($servers, (int) $selectedServer['id']) : [];
                                        foreach ($hierarchy as $entry):
                                            $sv = $entry['server'];
                                            $serverOptionId = (int) $sv['id'];
                                            if (isset($excludedParents[$serverOptionId])) continue;
                                            // Nonbreaking spaces survive rendering inside native select options.
                                            $indent = str_repeat("\u{00A0}\u{00A0}\u{00A0}", $entry['depth']);
                                            $prefix = $entry['depth'] > 0 ? '└─ ' : '';
                                    ?>
                                        <option value="<?= $serverOptionId ?>" <?= $selectedServer && (int)($selectedServer['parent_server_id'] ?? 0) === $serverOptionId ? 'selected' : '' ?>><?= escape($indent . $prefix . $sv['hostname']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="server-checkboxes">
                                    <label><input type="checkbox" name="has_root_access" value="1" <?= !empty($selectedServer['has_root_access']) ? 'checked' : '' ?>> Root access</label>
                                    <label><input type="checkbox" name="has_backdoor" value="1" <?= !empty($selectedServer['has_backdoor']) ? 'checked' : '' ?>> Backdoor installed</label>
                                </div>
                                <label for="ports-required">Ports required</label>
                                <input id="ports-required" name="ports_required" type="number" min="0" max="5" required value="<?= (int)($selectedServer['ports_required'] ?? 0) ?>">
                                <label for="hacking-level">Required hacking level</label>
                                <input id="hacking-level" name="hacking_level" type="number" min="1" max="2147483647" required value="<?= (int)($selectedServer['hacking_level'] ?? 1) ?>">
                                <label for="ram-amount">Server RAM</label>
                                <?php
                                    $savedRamGb = (float) ($selectedServer['ram_gb'] ?? 0);
                                    $ramUnit = $savedRamGb >= 1024 ? 'TB' : 'GB';
                                    $ramAmount = $ramUnit === 'TB' ? $savedRamGb / 1024 : $savedRamGb;
                                    $ramInput = rtrim(rtrim(number_format($ramAmount, 3, '.', ''), '0'), '.');
                                ?>
                                <div class="ram-field">
                                    <input id="ram-amount" name="ram_amount" type="number" min="0" step="0.001" required value="<?= escape($ramInput) ?>" aria-label="RAM amount">
                                    <select id="ram-unit" name="ram_unit" aria-label="RAM unit">
                                        <option value="GB" <?= $ramUnit === 'GB' ? 'selected' : '' ?>>GB</option>
                                        <option value="TB" <?= $ramUnit === 'TB' ? 'selected' : '' ?>>TB</option>
                                    </select>
                                </div>
                                <p class="hint">Use 0 when RAM has not been recorded yet. 1 TB = 1024 GB.</p>
                                <label for="server-notes">Server notes</label>
                                <textarea id="server-notes" name="notes" maxlength="6000" rows="3"><?= escape($selectedServer['notes'] ?? '') ?></textarea>
                                <button type="submit"><?= $selectedServer ? 'Save server changes' : 'Add server' ?></button>
                            </form>
                            <?php if ($selectedServer): ?><p><a class="server-new" href="explorer.php?session=<?= (int)$selected['id'] ?>&new_server=1">+ Add another server</a></p><?php endif; ?>
                        <?php endif; ?>
                    </section>
</div></section></section>
<?php endif; ?>
