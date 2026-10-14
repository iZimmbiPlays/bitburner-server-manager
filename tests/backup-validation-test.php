<?php

declare(strict_types=1);
require __DIR__ . '/../app/backups.php';

$good = [
    'format' => BACKUP_FORMAT, 'version' => BACKUP_VERSION,
    'session' => ['name' => 'Test run', 'bitnode' => 'BN1', 'notes' => 'Saved'],
    'servers' => [
        ['id' => 1, 'parent_id' => null, 'hostname' => 'home', 'root' => true, 'backdoor' => true, 'ports' => 0, 'level' => 1, 'ram_gb' => '32.000'],
        ['id' => 2, 'parent_id' => 1, 'hostname' => 'n00dles', 'root' => false, 'backdoor' => false, 'ports' => 0, 'level' => 1, 'ram_gb' => '16.000'],
    ],
];
assert(count(validateGameBackup($good)['servers']) === 2);
function rejection(array $data): void {
    try { validateGameBackup($data); } catch (InvalidArgumentException) { return; }
    throw new RuntimeException('Invalid backup was accepted');
}
$broken = $good;
$broken['servers'][1]['parent_id'] = 999;
rejection($broken);
$broken = $good;
$broken['servers'][0]['parent_id'] = 2;
rejection($broken);
$broken = $good;
$broken['servers'][1]['hostname'] = 'HOME';
rejection($broken);
$broken = $good;
$broken['servers'][1]['ram_gb'] = '-4';
rejection($broken);
$broken = $good;
$broken['version'] = 999;
rejection($broken);
echo "Backup validation tests passed.\n";
