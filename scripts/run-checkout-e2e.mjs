import { existsSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import process from 'node:process';

const windowsPhp = 'C:\\Program Files\\Ampps\\php82\\php.exe';
const phpBinary = process.env.PHP_BINARY
    || (process.platform === 'win32' && existsSync(windowsPhp) ? windowsPhp : 'php');

function run(command, args, extraEnvironment = {}) {
    const result = spawnSync(command, args, {
        cwd: process.cwd(),
        env: { ...process.env, ...extraEnvironment },
        stdio: 'inherit',
        shell: false,
    });

    if (result.error) throw result.error;
    if (result.status !== 0) process.exit(result.status ?? 1);
}

run(phpBinary, ['scripts/prepare-e2e.php']);
run(
    process.execPath,
    ['node_modules/@playwright/test/cli.js', 'test', '--config=playwright.config.mjs'],
    { PHP_BINARY: phpBinary }
);
