<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

if (!is_file($root . '/.env')) {
    throw new RuntimeException('Không tìm thấy file .env để tạo cấu hình E2E.');
}

$fileEnvironment = Dotenv::createArrayBacked($root)->safeLoad();

$environment = static function (string $key, ?string $default = null) use ($fileEnvironment): ?string {
    $processValue = getenv($key);
    $value = $processValue !== false && $processValue !== ''
        ? $processValue
        : ($fileEnvironment[$key] ?? $default);

    return $value === false || $value === null || $value === '' ? $default : (string) $value;
};

$sourceDatabase = $environment('DB_DATABASE', 'fruitshop');
$database = $environment('E2E_DB_DATABASE', $sourceDatabase . '_e2e');

if (!preg_match('/^[A-Za-z0-9_]+$/', $sourceDatabase)) {
    throw new RuntimeException('Tên database chính không hợp lệ.');
}

if (!preg_match('/^[A-Za-z0-9_]+_e2e$/', $database) || $database === $sourceDatabase) {
    throw new RuntimeException('Database E2E phải khác database chính và có hậu tố _e2e.');
}

$host = $environment('DB_HOST', '127.0.0.1');
$port = $environment('DB_PORT', '3306');
$username = $environment('DB_USERNAME', 'root');
$password = $environment('DB_PASSWORD', '');
$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$databaseFingerprint = static function (PDO $connection, string $databaseName): array {
    $tables = ['users', 'categories', 'products', 'orders', 'migrations'];
    $fingerprint = [];

    $tableCount = $connection->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :database'
    );
    $tableCount->execute(['database' => $databaseName]);
    $fingerprint['tables'] = (int) $tableCount->fetchColumn();

    foreach ($tables as $table) {
        $exists = $connection->prepare(
            'SELECT COUNT(*) FROM information_schema.tables '
            . 'WHERE table_schema = :database AND table_name = :table'
        );
        $exists->execute(['database' => $databaseName, 'table' => $table]);
        $fingerprint[$table] = (int) $exists->fetchColumn() === 1
            ? (int) $connection->query(sprintf(
                'SELECT COUNT(*) FROM `%s`.`%s`',
                $databaseName,
                $table
            ))->fetchColumn()
            : null;
    }

    return $fingerprint;
};

$sourceFingerprint = $databaseFingerprint($pdo, $sourceDatabase);

$pdo->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $database
));

$e2eEnvironment = file_get_contents($root . '/.env');
if ($e2eEnvironment === false) {
    throw new RuntimeException('Không thể đọc file .env.');
}

$overrides = [
    'APP_ENV' => 'e2e',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'http://127.0.0.1:8010',
    'DB_CONNECTION' => 'mysql',
    'DB_DATABASE' => $database,
    'DB_URL' => '',
    'DATABASE_URL' => '',
    'CACHE_STORE' => 'array',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'file',
    'SESSION_SECURE_COOKIE' => 'false',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
    'MAIL_FROM_ADDRESS' => 'checkout.e2e@example.test',
    'ANALYTICS_ENABLED' => 'false',
    'SHOP_ORDER_PLACED_EMAIL_ENABLED' => 'false',
    'SHOP_ORDER_CONFIRMED_EMAIL_ENABLED' => 'false',
    'SHOP_LOW_STOCK_ALERT_ENABLED' => 'false',
];

foreach ($overrides as $key => $value) {
    $line = $key . '=' . $value;
    $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

    if (preg_match($pattern, $e2eEnvironment) === 1) {
        $e2eEnvironment = preg_replace($pattern, $line, $e2eEnvironment, 1) ?? $e2eEnvironment;
    } else {
        $e2eEnvironment = rtrim($e2eEnvironment) . PHP_EOL . $line . PHP_EOL;
    }
}

if (file_put_contents($root . '/.env.e2e', $e2eEnvironment) === false) {
    throw new RuntimeException('Không thể tạo file .env.e2e.');
}

$artisanEnvironment = [
    'APP_ENV' => 'e2e',
    'APP_DEBUG' => 'false',
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => $host,
    'DB_PORT' => $port,
    'DB_DATABASE' => $database,
    'DB_USERNAME' => $username,
    'DB_PASSWORD' => $password,
    'DB_URL' => false,
    'DATABASE_URL' => false,
];

$runArtisan = static function (array $arguments, bool $stream = true) use ($root, $artisanEnvironment): string {
    $process = new Process(
        array_merge([PHP_BINARY, $root . '/artisan'], $arguments),
        $root,
        $artisanEnvironment
    );
    $process->setTimeout(300);
    $process->run($stream ? static function (string $type, string $buffer): void {
        fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
    } : null);

    if (!$process->isSuccessful()) {
        throw new RuntimeException(sprintf(
            'Lệnh Artisan thất bại: %s',
            $process->getCommandLine()
        ));
    }

    return $process->getOutput() . $process->getErrorOutput();
};

fwrite(STDOUT, "Chuẩn bị database E2E an toàn: {$database}" . PHP_EOL);
$runArtisan(['config:clear', '--env=e2e']);
$databaseInfo = $runArtisan(['db:show', '--database=mysql', '--env=e2e'], false);

if (!str_contains($databaseInfo, $database)) {
    throw new RuntimeException(sprintf(
        'Đã chặn migrate:fresh vì Laravel không kết nối tới database E2E %s.',
        $database
    ));
}

try {
    $runArtisan(['migrate:fresh', '--env=e2e', '--force']);
    $runArtisan(['db:seed', '--class=Database\\Seeders\\E2eCheckoutSeeder', '--env=e2e', '--force']);
} finally {
    $sourceAfter = $databaseFingerprint($pdo, $sourceDatabase);

    if ($sourceAfter !== $sourceFingerprint) {
        throw new RuntimeException(
            'Đã phát hiện database chính thay đổi trong lúc chuẩn bị E2E. Dừng kiểm thử ngay.'
        );
    }
}

$targetFingerprint = $databaseFingerprint($pdo, $database);
$expectedMigrations = count(glob($root . '/database/migrations/*.php') ?: []);

if (
    $targetFingerprint['tables'] < 1
    || $targetFingerprint['users'] !== 1
    || $targetFingerprint['categories'] !== 1
    || $targetFingerprint['products'] !== 1
    || $targetFingerprint['orders'] !== 0
    || $targetFingerprint['migrations'] !== $expectedMigrations
) {
    throw new RuntimeException('Database E2E không có đúng dữ liệu nền mong đợi sau khi seed.');
}

fwrite(STDOUT, "Database E2E đã sẵn sàng." . PHP_EOL);
