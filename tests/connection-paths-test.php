<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/servers.php';

$servers = [
    ['id' => 1, 'hostname' => 'home', 'parent_server_id' => null],
    ['id' => 2, 'hostname' => 'n00dles', 'parent_server_id' => 1],
    ['id' => 3, 'hostname' => 'max-hardware', 'parent_server_id' => 2],
    ['id' => 4, 'hostname' => 'unconnected', 'parent_server_id' => null],
    ['id' => 5, 'hostname' => 'orphan', 'parent_server_id' => 999],
    ['id' => 6, 'hostname' => 'loop-a', 'parent_server_id' => 7],
    ['id' => 7, 'hostname' => 'loop-b', 'parent_server_id' => 6],
];
$checks = [
    [1, true, 'home'],
    [2, true, 'home; connect n00dles'],
    [3, true, 'home; connect n00dles; connect max-hardware'],
    [4, false, ''],
    [5, false, ''],
    [6, false, ''],
    [100, false, ''],
];
foreach ($checks as [$id, $available, $commands]) {
    $actual = serverConnectionPath($servers, $id);
    if ($actual['available'] !== $available || $actual['commands'] !== $commands) {
        fwrite(STDERR, "Failed: {$id}\n");
        exit(1);
    }
}
echo "Connection path tests passed.\n";
