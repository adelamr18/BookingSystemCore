import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 180000,
    expect: {
        timeout: 10000,
    },
    fullyParallel: false,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ],
    use: {
        baseURL: 'http://127.0.0.1:8002',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: {
        command: 'bash tests/e2e/serve.sh',
        url: 'http://127.0.0.1:8002/login',
        timeout: 180000,
        reuseExistingServer: false,
    },
});
