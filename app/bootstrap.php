<?php

declare(strict_types=1);

/** Load local configuration without third-party dependencies. */
function projectConfig(string $name, string $fallback = ''): string
{
    static $variables = null;

    if ($variables === null) {
        $variables = [];
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $variables[trim($key)] = trim(trim($value), "\"'");
            }
        }
    }

    return $variables[$name] ?? (getenv($name) ?: $fallback);
}

/** A single PDO connection for the request. */
function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = projectConfig('DB_HOST', '127.0.0.1');
    $port = projectConfig('DB_PORT', '3306');
    $name = projectConfig('DB_NAME', 'bitburner_server_manager');
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, projectConfig('DB_USER', 'root'), projectConfig('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
