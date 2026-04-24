import { test as setup } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const authFile = path.join('playwright', '.auth', 'user.json');

setup('authenticate', async ({ page }) => {
    fs.mkdirSync(path.dirname(authFile), { recursive: true });

    await page.goto('/login');
    await page.getByLabel('Email address').fill(process.env.TEST_USER_EMAIL ?? 'alex@example.com');
    await page.locator('input[name="password"]').fill(process.env.TEST_USER_PASSWORD ?? 'password');
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL('/dashboard');

    await page.context().storageState({ path: authFile });
});
