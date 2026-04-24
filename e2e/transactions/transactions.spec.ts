import { test, expect } from '../fixtures';

test.describe('transactions', () => {
    test('loads the transactions page', async ({ page }) => {
        await page.goto('/transactions');
        await expect(page).toHaveTitle(/Transactions/);
        await expect(page.getByRole('heading', { name: 'Add / Edit Transaction' })).toBeVisible();
    });

    test('creates a new expense transaction', async ({ page }) => {
        const description = `E2E Expense ${Date.now()}`;
        const today = new Date().toISOString().split('T')[0];

        await page.goto('/transactions');

        await page.locator('select[wire\\:model="type"]').selectOption('expense');
        await page.locator('input[wire\\:model="amount"]').fill('42.50');
        await page.locator('input[wire\\:model="date"]').fill(today);
        await page.locator('textarea[wire\\:model="description"]').fill(description);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Transaction saved successfully.')).toBeVisible();
        await expect(page.getByText(description)).toBeVisible();
    });

    test('creates a new income transaction', async ({ page }) => {
        const description = `E2E Income ${Date.now()}`;
        const today = new Date().toISOString().split('T')[0];

        await page.goto('/transactions');

        await page.locator('select[wire\\:model="type"]').selectOption('income');
        await page.locator('input[wire\\:model="amount"]').fill('1500.00');
        await page.locator('input[wire\\:model="date"]').fill(today);
        await page.locator('textarea[wire\\:model="description"]').fill(description);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Transaction saved successfully.')).toBeVisible();
        await expect(page.getByText(description)).toBeVisible();
    });

    test('edits an existing transaction', async ({ page }) => {
        const original = `E2E Edit Original ${Date.now()}`;
        const updated = `E2E Edit Updated ${Date.now()}`;
        const today = new Date().toISOString().split('T')[0];

        await page.goto('/transactions');

        // Create a transaction to edit.
        await page.locator('input[wire\\:model="amount"]').fill('10.00');
        await page.locator('input[wire\\:model="date"]').fill(today);
        await page.locator('textarea[wire\\:model="description"]').fill(original);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Transaction saved successfully.')).toBeVisible();

        // Open the edit form.
        const row = page.getByRole('row').filter({ hasText: original });
        await row.getByRole('button', { name: 'Edit' }).click();

        // Update description and save.
        await page.locator('textarea[wire\\:model="description"]').fill(updated);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Transaction saved successfully.')).toBeVisible();
        await expect(page.getByText(updated)).toBeVisible();
        await expect(page.getByText(original)).not.toBeVisible();
    });

    test('deletes a transaction', async ({ page }) => {
        const description = `E2E Delete ${Date.now()}`;
        const today = new Date().toISOString().split('T')[0];

        await page.goto('/transactions');

        // Create a transaction to delete.
        await page.locator('input[wire\\:model="amount"]').fill('5.00');
        await page.locator('input[wire\\:model="date"]').fill(today);
        await page.locator('textarea[wire\\:model="description"]').fill(description);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Transaction saved successfully.')).toBeVisible();

        // Delete the row.
        const row = page.getByRole('row').filter({ hasText: description });
        await row.getByRole('button', { name: 'Delete' }).click();

        await expect(page.getByText('Transaction removed.')).toBeVisible();
        await expect(page.getByText(description)).not.toBeVisible();
    });

    test('shows a validation error when the amount is below the minimum', async ({ page }) => {
        await page.goto('/transactions');

        await page.locator('input[wire\\:model="amount"]').fill('0');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText(/The amount/i)).toBeVisible();
    });
});
