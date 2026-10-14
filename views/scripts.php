<section class="page-intro"><p class="eyebrow">NETSCRIPT WORKSPACE</p><h1>Scripts Library</h1><p>Bundled beginner helpers and your editable Netscript collection. Scripts are shared across gameplay sessions.</p></section>
<?php if ($db): ?>
<section class="scripts-workspace">
  <aside class="panel pane-body scripts-sidebar">
    <div class="scripts-header"><h2>Library (<?= count($scriptList) ?>)</h2><a href="scripts.php?new=1">+ New script</a></div>
    <input class="scripts-search" id="script-search" type="search" placeholder="Search scripts or categories…" aria-label="Search scripts">
    <?php if (!$scriptList): ?>
      <p class="hint">No scripts installed yet. Import the bundled helpers below.</p>
      <form method="post"><input type="hidden" name="csrf_token" value="<?= escape($token) ?>"><input type="hidden" name="script_action" value="seed"><button type="submit">Install 16 beginner helpers</button></form>
    <?php else: ?>
      <div id="script-list">
        <?php
          // Built-in catalog keys identify their original collection without altering SQL.
          // User copies also retain their broad collection via category or dnet- filename.
          $scriptGroups = ['Regular Scripts' => [], 'Dark Net Scripts' => [], 'My Scripts' => []];
          foreach ($scriptList as $item) {
              $isBuiltin = (int) $item['is_builtin'] === 1;
              if (!$isBuiltin) {
                  $group = 'My Scripts';
              } elseif (str_starts_with(strtolower($item['filename']), 'dnet-') || str_starts_with(strtolower((string) ($item['builtin_key'] ?? '')), 'dark_net/')) {
                  $group = 'Dark Net Scripts';
              } else {
                  $group = 'Regular Scripts';
              }
              $scriptGroups[$group][] = $item;
          }
        ?>
        <nav class="script-collection-tabs" aria-label="Script collections">
          <button type="button" class="script-collection-tab is-active" data-script-collection="all" aria-pressed="true">All</button>
          <button type="button" class="script-collection-tab" data-script-collection="regular" aria-pressed="false">Regular</button>
          <button type="button" class="script-collection-tab" data-script-collection="darknet" aria-pressed="false">Dark Net</button>
          <button type="button" class="script-collection-tab" data-script-collection="personal" aria-pressed="false">My Scripts</button>
        </nav>
        <?php foreach ($scriptGroups as $label => $items): ?>
          <?php if (!$items) continue; ?>
          <?php $groupKey = $label === 'Regular Scripts' ? 'regular' : ($label === 'Dark Net Scripts' ? 'darknet' : 'personal'); ?>
          <details class="script-group" data-script-group="<?= $groupKey ?>" open>
            <summary class="script-group-heading"><?= escape($label) ?> <span>(<?= count($items) ?>)</span></summary>
            <div class="script-group-content">
            <?php foreach ($items as $item): ?>
              <div class="script-list-row" data-script-search="<?= escape(strtolower($item['filename'] . ' ' . $item['category'] . ' ' . $item['description'])) ?>">
                <a class="script-link <?= $activeScript && (int) $activeScript['id'] === (int) $item['id'] ? 'is-selected' : '' ?>" href="scripts.php?script=<?= (int) $item['id'] ?>">
                  <strong><?= escape($item['filename']) ?></strong><span><?= escape($item['category']) ?> · <?= (int) $item['is_builtin'] ? 'Built-in' : 'My Script' ?></span>
                </a>
                <?php if (!(int) $item['is_builtin']): ?>
                  <form class="script-delete-form" method="post" data-delete-script data-script-name="<?= escape($item['filename']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
                    <input type="hidden" name="script_action" value="delete">
                    <input type="hidden" name="script_id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="confirm_delete" value="yes">
                    <button class="script-delete-pill" type="submit" title="Delete <?= escape($item['filename']) ?>" aria-label="Delete <?= escape($item['filename']) ?>">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
        <p id="scripts-empty-filter" class="hint" hidden>No scripts match the selected collection and search.</p>
      </div>
      <form method="post" class="scripts-seed"><input type="hidden" name="csrf_token" value="<?= escape($token) ?>"><input type="hidden" name="script_action" value="seed"><button type="submit">Install missing bundled helpers</button></form>
    <?php endif; ?>
  </aside>
  <section class="panel pane-body scripts-main">
    <?php if ($activeScript || $newScript): ?>
      <?php $s = $activeScript ?: ['filename' => 'my-script.js', 'category' => 'Utilities', 'difficulty' => 'Beginner', 'description' => '', 'usage_command' => '', 'argument_help' => '', 'dependencies' => '', 'caution' => '', 'code' => '/** @param {NS} ns */' . "\n" . 'export async function main(ns) {' . "\n" . '  ns.tprint("Hello, Bitburner!");' . "\n" . '}' . "\n", 'is_builtin' => 0, 'version_label' => '1.0.0']; ?>
      <?php $builtin = (int) $s['is_builtin'] === 1; ?>
      <div class="scripts-header"><h2><?= escape($s['filename']) ?></h2><span class="tag"><?= $builtin ? 'Protected built-in' : 'Editable' ?></span></div>
      <p class="hint">Source status: <?= $builtin ? 'Supplied original · not automatically verified in-game' : 'User-maintained script' ?>. Version: <?= escape($s['version_label']) ?></p>
      <?php if ($builtin): ?>
        <div class="script-warning"><strong>What it does:</strong> <?= escape($s['description']) ?><br><strong>Important:</strong> <?= escape($s['caution']) ?></div>
        <dl class="script-docs"><dt>Difficulty</dt><dd><?= escape($s['difficulty']) ?></dd><dt>Run in Bitburner</dt><dd><code><?= escape($s['usage_command']) ?></code></dd><dt>Arguments</dt><dd><?= escape($s['argument_help']) ?></dd><dt>Other scripts needed</dt><dd><?= escape($s['dependencies'] ?: 'None') ?></dd></dl>
        <div class="script-actions"><a class="script-button" href="scripts.php?download=<?= (int) $s['id'] ?>">Download .js</a><button type="button" data-copy-script>Copy code</button><form method="post"><input type="hidden" name="csrf_token" value="<?= escape($token) ?>"><input type="hidden" name="script_action" value="duplicate"><input type="hidden" name="script_id" value="<?= (int) $s['id'] ?>"><button type="submit">Duplicate to My Scripts</button></form></div>
        <label for="script-code">Original source (read-only)</label>
        <textarea class="script-code" id="script-code" readonly spellcheck="false"><?= escape($s['code']) ?></textarea>
      <?php else: ?>
        <form method="post" class="script-edit-form" id="script-edit-form">
          <input type="hidden" name="csrf_token" value="<?= escape($token) ?>"><input type="hidden" name="script_action" value="<?= $newScript ? 'create' : 'save' ?>">
          <?php if (!$newScript): ?><input type="hidden" name="script_id" value="<?= (int) $s['id'] ?>"><?php endif; ?>
          <div class="script-edit-grid"><label>Filename <input name="filename" maxlength="180" required value="<?= escape($s['filename']) ?>"></label><label>Category <input name="category" maxlength="60" required value="<?= escape($s['category']) ?>"></label><label>Difficulty <select name="difficulty"><?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $level): ?><option <?= $s['difficulty'] === $level ? 'selected' : '' ?>><?= escape($level) ?></option><?php endforeach; ?></select></label></div>
          <label>What it does <textarea name="description" rows="2" maxlength="3000" required><?= escape($s['description']) ?></textarea></label>
          <label>How to run it <input name="usage_command" maxlength="500" value="<?= escape($s['usage_command']) ?>"></label>
          <label>Arguments / prerequisites <input name="argument_help" maxlength="500" value="<?= escape($s['argument_help']) ?>"></label>
          <label>Dependencies <input name="dependencies" maxlength="500" value="<?= escape($s['dependencies']) ?>"></label>
          <label>Warnings / troubleshooting <textarea name="caution" rows="2" maxlength="3000"><?= escape($s['caution']) ?></textarea></label>
          <label for="script-code">JavaScript source</label><textarea class="script-code" id="script-code" name="code" spellcheck="false" required><?= escape($s['code']) ?></textarea>
          <div class="script-actions"><button type="submit"><?= $newScript ? 'Create script' : 'Save script' ?></button><?php if (!$newScript): ?><button type="button" data-copy-script>Copy code</button><a class="script-button" href="scripts.php?download=<?= (int) $s['id'] ?>">Download .js</a><?php endif; ?></div>
        </form>
        <?php if (!$newScript): ?>
          <div class="script-danger-zone">
            <strong>Delete this personal script</strong>
            <p class="hint">Permanently deletes this script and all of its saved code revisions. The built-in originals are unaffected.</p>
            <form method="post" data-delete-script data-script-name="<?= escape($s['filename']) ?>">
              <input type="hidden" name="csrf_token" value="<?= escape($token) ?>">
              <input type="hidden" name="script_action" value="delete">
              <input type="hidden" name="script_id" value="<?= (int) $s['id'] ?>">
              <input type="hidden" name="confirm_delete" value="yes">
              <button type="submit" class="script-delete-button">Delete personal script</button>
            </form>
          </div>
        <?php endif; ?>
        <?php if ($scriptRevisions): ?><details class="script-revisions"><summary>Previous saved code (<?= count($scriptRevisions) ?>)</summary><?php foreach ($scriptRevisions as $revision): ?><details><summary><?= escape($revision['version_label']) ?> · <?= escape($revision['created_at']) ?></summary><textarea class="script-code" readonly><?= escape($revision['code']) ?></textarea></details><?php endforeach; ?></details><?php endif; ?>
      <?php endif; ?>
    <?php else: ?><h2>Select a script</h2><p class="hint">Choose a helper on the left or create your own. Built-in files remain unchanged until you explicitly duplicate them.</p><?php endif; ?>
  </section>
</section>
<?php endif; ?>
