<?php

declare(strict_types=1);

$environmentFile = dirname(__DIR__) . '/.env';

if (is_file($environmentFile)) {
    $environment = parse_ini_file($environmentFile, false, INI_SCANNER_RAW);

    if ($environment === false) {
        throw new RuntimeException('The .env file could not be parsed.');
    }

    foreach ($environment as $name => $value) {
        if (!is_string($value)) {
            throw new RuntimeException('Environment values must be strings.');
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
        }
    }
}

function appDatabase(): PDO
{
    $local = strtolower((string) (getenv('APP_ENV') ?: '')) === 'local';
    $requiredSettings = ['DB_HOST', 'DB_NAME', 'DB_USER'];

    if (!$local) {
        $requiredSettings[] = 'DB_PASSWORD';
    }

    $settings = [];

    foreach ($requiredSettings as $name) {
        $value = getenv($name);

        if ($value === false || trim($value) === '') {
            throw new RuntimeException(
                'Database configuration is incomplete: ' . $name . ' is required.'
            );
        }

        $settings[$name] = $value;
    }

    $port = getenv('DB_PORT');
    $port = $port === false || $port === '' ? '3306' : $port;

    if (filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
        throw new RuntimeException('DB_PORT must be a valid TCP port.');
    }

    $host = $settings['DB_HOST'] ?? 'localhost';
    $database = $settings['DB_NAME'] ?? 'T@ke9ine';
    $username = $settings['DB_USER'] ?? 'root';
    $password = getenv('DB_PASSWORD');
    $password = $password === false ? '' : $password;

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
