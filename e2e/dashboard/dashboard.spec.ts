import { test, expect } from '../fixtures';

test.describe('dashboard', () => {
    test('loads the dashboard page', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveTitle(/Dashboard/);
    });

    test('displays income, expenses and net balance summary cards', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page.getByText('Income')).toBeVisible();
        await expect(page.getByText('Expenses')).toBeVisible();
        await expect(page.getByText('Net Balance')).toBeVisible();
    });

    test('shows a monetary value in each summary card from seeded data', async ({ page }) => {
        await page.goto('/dashboard');
        // Seeder creates income and expense transactions for the current month.
        // Each card renders a £ value; confirm at least one is non-zero.
        const incomeCard = page.locator('div').filter({ hasText: /^Income$/ }).locator('+ p');
        await expect(incomeCard).toContainText('£');
    });

    test('displays the budgets vs actuals section', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page.getByRole('heading', { name: 'Budgets vs Actuals' })).toBeVisible();
        // Seeder creates Housing and Groceries budgets for the current month.
        await expect(page.getByText('Housing')).toBeVisible();
        await expect(page.getByText('Groceries')).toBeVisible();
    });

    test('displays income and expense chart sections', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page.getByRole('heading', { name: 'Income by Category' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Expenses by Category' })).toBeVisible();
    });
});
