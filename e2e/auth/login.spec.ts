import { test, expect } from '../fixtures';

test.describe('authentication', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('shows the login page', async ({ page }) => {
        await page.goto('/login');
        await expect(page).toHaveTitle(/Log in to your account/);
    });

    test('logs in with valid credentials and redirects to dashboard', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email address').fill(process.env.TEST_USER_EMAIL ?? 'alex@example.com');
        await page.locator('input[name="password"]').fill(process.env.TEST_USER_PASSWORD ?? 'password');
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page).toHaveURL('/dashboard');
    });

    test('shows an error for invalid credentials', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email address').fill('nobody@example.com');
        await page.locator('input[name="password"]').fill('wrongpassword');
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page.getByText(/These credentials do not match/i)).toBeVisible();
    });

    test('shows validation errors when fields are empty', async ({ page }) => {
        await page.goto('/login');
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page.getByText(/email field is required/i)).toBeVisible();
    });
});
