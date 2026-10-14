<?php
$serverCount = count($servers);
$rooted = count(array_filter($servers, static fn(array $s): bool => (bool)$s['has_root_access']));
$backdoored = count(array_filter($servers, static fn(array $s): bool => (bool)$s['has_backdoor']));
$archived = count(array_filter($sessions, static fn(array $s): bool => $s['status'] === 'archived'));
?>
<section class="page-intro"><p class="eyebrow">OVERVIEW</p><h1>Dashboard</h1><p>Your current gameplay progress at a glance.</p></section>
<?php if ($selected === null): ?>
<div class="panel pane-body"><p>No saved gameplay yet.</p><a class="nav-cta" href="sessions.php">Create your first session</a></div>
<?php else: ?>
<div class="context-line"><strong><?= escape($selected['name']) ?></strong><span class="state <?= $selected['status'] === 'active' ? 'state-active' : '' ?>"><?= escape($selected['status']) ?></span></div>
<div class="metric-grid">
<div class="metric"><span>Servers</span><strong><?= $serverCount ?></strong></div>
<div class="metric"><span>Root access</span><strong><?= $rooted ?> / <?= $serverCount ?></strong></div>
<div class="metric"><span>Backdoors</span><strong><?= $backdoored ?> / <?= $serverCount ?></strong></div>
<div class="metric"><span>Archived runs</span><strong><?= $archived ?></strong></div>
</div>
<section class="panel pane-body"><h2>Still needs attention</h2>
<?php $pending = array_values(array_filter($servers, static fn(array $s): bool => !(bool)$s['has_root_access'] || !(bool)$s['has_backdoor'])); ?>
<?php if (!$pending): ?><p class="hint">No outstanding root or backdoor tasks for saved servers.</p><?php endif; ?>
<div class="pending-grid">
<?php foreach ($pending as $sv): ?><a class="pending-item" href="explorer.php?session=<?= (int)$selected['id'] ?>&amp;server=<?= (int)$sv['id'] ?>"><strong><?= escape($sv['hostname']) ?></strong><span><?= !$sv['has_root_access'] ? 'Root needed · ' : '' ?><?= !$sv['has_backdoor'] ? 'Backdoor needed' : '' ?> · RAM: <?= escape(formatServerRam($sv['ram_gb'] ?? 0)) ?></span></a><?php endforeach; ?>
</div></section>
<?php endif; ?>
