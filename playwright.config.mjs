import { defineConfig, devices } from '@playwright/test';
import process from 'node:process';

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8010';
const phpBinary = process.env.PHP_BINARY || 'php';

export default defineConfig({
    testDir: './tests/E2E',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never' }]],
    outputDir: 'test-results',
    use: {
        baseURL,
        locale: 'vi-VN',
        timezoneId: 'Asia/Ho_Chi_Minh',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
        ...devices['Desktop Chrome'],
    },
    webServer: {
        command: `"${phpBinary}" artisan serve --env=e2e --host=127.0.0.1 --port=8010`,
        url: baseURL,
        reuseExistingServer: false,
        timeout: 120000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
