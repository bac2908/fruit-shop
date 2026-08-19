<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$database = getenv('TEST_DB_DATABASE') ?: 'fruitshop_test';

if (preg_match('/(^|[_-])test(ing)?($|[_-])/i', $database) !== 1) {
    fwrite(STDERR, "Refusing to reset database [{$database}]. The test database name must contain test/testing.\n");
    exit(2);
}

$environment = [
    'APP_ENV' => 'testing',
    'APP_URL' => 'http://localhost',
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => getenv('TEST_DB_HOST') ?: '127.0.0.1',
    'DB_PORT' => getenv('TEST_DB_PORT') ?: '3308',
    'DB_DATABASE' => $database,
    'DB_USERNAME' => getenv('TEST_DB_USERNAME') ?: 'fruitshop',
    'DB_PASSWORD' => getenv('TEST_DB_PASSWORD') ?: 'fruitshop_test',
    'MAIL_MAILER' => 'array',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
];

foreach ($environment as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $environment['DB_HOST'],
    $environment['DB_PORT'],
    $environment['DB_DATABASE']
);

$connected = false;
for ($attempt = 1; $attempt <= 30; $attempt++) {
    try {
        new PDO($dsn, $environment['DB_USERNAME'], $environment['DB_PASSWORD'], [
            PDO::ATTR_TIMEOUT => 2,
        ]);
        $connected = true;
        break;
    } catch (PDOException) {
        usleep(500_000);
    }
}

if (! $connected) {
    fwrite(
        STDERR,
        "Test MySQL is unavailable at {$environment['DB_HOST']}:{$environment['DB_PORT']}. "
        ."Start it with: docker compose --profile test up -d mysql_test\n"
    );
    exit(3);
}

$run = static function (array $arguments) use ($root): void {
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
};

$run([
    PHP_BINARY,
    $root.'/artisan',
    'migrate:fresh',
    '--force',
    '--no-interaction',
]);

$run(array_merge([
    PHP_BINARY,
    $root.'/vendor/bin/phpunit',
    '--do-not-cache-result',
], array_slice($argv, 1)));
