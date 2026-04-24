import { test, expect } from '../fixtures';

test.describe('net worth', () => {
    test('loads the net worth page', async ({ page }) => {
        await page.goto('/net-worth');
        await expect(page).toHaveTitle(/Net Worth/);
        await expect(page.getByRole('heading', { name: 'Net Worth' })).toBeVisible();
    });

    test('creates a net worth entry with assets and liabilities', async ({ page }) => {
        await page.goto('/net-worth');

        // Add an asset line.
        await page.getByPlaceholder('e.g. Cash, Investments').fill('Checking Account');
        await page.locator('input[wire\\:model="newAssetAmount"]').fill('10000.00');
        await page.locator('button[wire\\:click="addAssetLine"]').click();

        // Add a liability line.
        await page.getByPlaceholder('e.g. Mortgage, Credit Card').fill('Student Loan');
        await page.locator('input[wire\\:model="newLiabilityAmount"]').fill('3000.00');
        await page.locator('button[wire\\:click="addLiabilityLine"]').click();

        // Save the entry.
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Net worth entry saved.')).toBeVisible();
        await expect(page.getByRole('cell', { name: 'Checking Account' })).toBeVisible();
        await expect(page.getByRole('cell', { name: 'Student Loan' })).toBeVisible();
    });

    test('edits a net worth entry', async ({ page }) => {
        await page.goto('/net-worth');

        // Create an entry with a specific date so we can verify the edit.
        await page.locator('input[wire\\:model="date"]').fill('2099-05-15');
        await page.getByPlaceholder('e.g. Cash, Investments').fill('E2E Asset To Edit');
        await page.locator('input[wire\\:model="newAssetAmount"]').fill('5000.00');
        await page.locator('button[wire\\:click="addAssetLine"]').click();
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Net worth entry saved.')).toBeVisible();

        // Open the edit form for that entry.
        const row = page.getByRole('row').filter({ hasText: 'E2E Asset To Edit' });
        await row.getByRole('button', { name: 'Edit' }).click();

        // Change the date and save.
        await page.locator('input[wire\\:model="date"]').fill('2099-06-20');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Net worth entry saved.')).toBeVisible();
        await expect(page.getByText('Jun 20, 2099')).toBeVisible();
    });

    test('deletes a net worth entry', async ({ page }) => {
        await page.goto('/net-worth');

        // Create an entry to delete.
        await page.getByPlaceholder('e.g. Cash, Investments').fill('E2E Asset To Delete');
        await page.locator('input[wire\\:model="newAssetAmount"]').fill('1.00');
        await page.locator('button[wire\\:click="addAssetLine"]').click();
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Net worth entry saved.')).toBeVisible();

        const row = page.getByRole('row').filter({ hasText: 'E2E Asset To Delete' });
        await row.getByRole('button', { name: 'Delete' }).click();

        await expect(page.getByText('Net worth entry removed.')).toBeVisible();
        await expect(page.locator('td').filter({ hasText: 'E2E Asset To Delete' })).not.toBeVisible();
    });

    test('shows an error when an asset line has no category', async ({ page }) => {
        await page.goto('/net-worth');

        // Try to add an asset line without filling in the category.
        await page.locator('button[wire\\:click="addAssetLine"]').click();

        await expect(page.getByText('Asset category is required.')).toBeVisible();
    });
});
