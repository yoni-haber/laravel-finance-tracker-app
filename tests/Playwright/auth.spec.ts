import { expect, test, type Page } from '@playwright/test';

const loginHeader = 'Log in to your account';

async function navigateToLogin(page: Page) {
    await page.goto('/');
    await expect(page).toHaveURL(/\/login/);
}

test.describe('Authentication', () => {
    test('renders the login page with primary actions', async ({ page }) => {
        await navigateToLogin(page);

        await expect(page.getByRole('heading', { name: loginHeader })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Log in' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Sign up' })).toBeVisible();
    });

    test('allows navigating to the forgot password screen', async ({ page }) => {
        await navigateToLogin(page);

        await page.getByRole('link', { name: 'Forgot your password?' }).click();
        await expect(page).toHaveURL(/password\/request/);
    });
});
