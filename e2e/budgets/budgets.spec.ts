import { test, expect } from '../fixtures';

// Use year 2099 for all test budgets to avoid conflicts with seeded data.
const TEST_YEAR = '2099';

test.describe('budgets', () => {
    test('loads the budgets page', async ({ page }) => {
        await page.goto('/budgets');
        await expect(page).toHaveTitle(/Budgets/);
        await expect(page.getByRole('heading', { name: 'Add / Edit Budget' })).toBeVisible();
    });

    test('creates a new budget', async ({ page }) => {
        await page.goto('/budgets');

        await page.locator('select[wire\\:model="category_id"]').selectOption({ label: 'Entertainment' });
        await page.locator('select[wire\\:model="month"]').selectOption('6');
        await page.locator('input[wire\\:model="year"]').fill(TEST_YEAR);
        await page.locator('input[wire\\:model="amount"]').fill('300.00');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Budget saved.')).toBeVisible();

        // Filter the list to the test year to confirm the row is visible.
        await page.locator('input[wire\\:model\\.live="filterYear"]').fill(TEST_YEAR);
        await expect(page.getByRole('cell', { name: 'Entertainment' })).toBeVisible();
        await expect(page.getByRole('cell', { name: '£300.00' })).toBeVisible();
    });

    test('edits a budget', async ({ page }) => {
        await page.goto('/budgets');

        // Create a budget to edit.
        await page.locator('select[wire\\:model="category_id"]').selectOption({ label: 'Travel' });
        await page.locator('select[wire\\:model="month"]').selectOption('3');
        await page.locator('input[wire\\:model="year"]').fill(TEST_YEAR);
        await page.locator('input[wire\\:model="amount"]').fill('200.00');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Budget saved.')).toBeVisible();

        // Filter to the test year.
        await page.locator('input[wire\\:model\\.live="filterYear"]').fill(TEST_YEAR);

        // Open the edit form.
        const row = page.getByRole('row').filter({ hasText: 'Travel' });
        await row.getByRole('button', { name: 'Edit' }).click();

        // Update the amount and save.
        await page.locator('input[wire\\:model="amount"]').fill('999.00');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Budget saved.')).toBeVisible();
        await expect(page.getByRole('cell', { name: '£999.00' })).toBeVisible();
    });

    test('deletes a budget', async ({ page }) => {
        await page.goto('/budgets');

        // Create a budget to delete.
        await page.locator('select[wire\\:model="category_id"]').selectOption({ label: 'Savings' });
        await page.locator('select[wire\\:model="month"]').selectOption('9');
        await page.locator('input[wire\\:model="year"]').fill(TEST_YEAR);
        await page.locator('input[wire\\:model="amount"]').fill('150.00');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Budget saved.')).toBeVisible();

        // Filter to the test year.
        await page.locator('input[wire\\:model\\.live="filterYear"]').fill(TEST_YEAR);

        // Delete the row.
        const row = page.getByRole('row').filter({ hasText: 'Savings' });
        await row.getByRole('button', { name: 'Delete' }).click();

        await expect(page.getByText('Budget removed.')).toBeVisible();
    });

    test('shows an error when creating a duplicate budget', async ({ page }) => {
        await page.goto('/budgets');

        // Create the first budget.
        await page.locator('select[wire\\:model="category_id"]').selectOption({ label: 'Freelance' });
        await page.locator('select[wire\\:model="month"]').selectOption('11');
        await page.locator('input[wire\\:model="year"]').fill(TEST_YEAR);
        await page.locator('input[wire\\:model="amount"]').fill('500.00');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Budget saved.')).toBeVisible();

        // Attempt to create the same budget again.
        await page.locator('select[wire\\:model="category_id"]').selectOption({ label: 'Freelance' });
        await page.locator('select[wire\\:model="month"]').selectOption('11');
        await page.locator('input[wire\\:model="year"]').fill(TEST_YEAR);
        await page.locator('input[wire\\:model="amount"]').fill('600.00');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText(/already exists/i)).toBeVisible();
    });

    test('copies budgets from the previous month', async ({ page }) => {
        await page.goto('/budgets');

        // Seed: create a budget in July 2099 to act as the copy source.
        await page.locator('select[wire\\:model="category_id"]').selectOption({ label: 'Salary' });
        await page.locator('select[wire\\:model="month"]').selectOption('7');
        await page.locator('input[wire\\:model="year"]').fill(TEST_YEAR);
        await page.locator('input[wire\\:model="amount"]').fill('5000.00');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Budget saved.')).toBeVisible();

        // Filter the list to August 2099 (the copy target).
        await page.locator('select[wire\\:model\\.live="filterMonth"]').selectOption('8');
        await page.locator('input[wire\\:model\\.live="filterYear"]').fill(TEST_YEAR);

        // Copy from July 2099.
        await page.getByRole('button', { name: 'Copy from previous month' }).click();

        await expect(page.getByText(/Copied 1 budget/i)).toBeVisible();
        await expect(page.getByRole('cell', { name: 'Salary' })).toBeVisible();
    });
});
