import { defineConfig, devices } from '@playwright/test';

const sharedEnv = {
    APP_ENV: 'testing',
    APP_DEBUG: 'true',
    APP_URL: 'http://127.0.0.1:8000',
    APP_KEY: process.env.APP_KEY ?? 'base64:rsrBEo0cnHXEPSQEEOLB/2i4cw8bAbrkW1j6J7xAAyc=',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: ':memory:',
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'array',
    QUEUE_CONNECTION: 'sync',
    LOG_CHANNEL: 'stderr',
};

export default defineConfig({
    testDir: './tests/Playwright',
    fullyParallel: true,
    timeout: 60 * 1000,
    expect: {
        timeout: 10 * 1000,
    },
    reporter: 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000 --env=testing',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: !process.env.CI,
        timeout: 120 * 1000,
        env: sharedEnv,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
    ],
});
