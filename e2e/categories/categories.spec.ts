import { test, expect } from '../fixtures';

test.describe('categories', () => {
    test('loads the categories page', async ({ page }) => {
        await page.goto('/categories');
        await expect(page).toHaveTitle(/Categories/);
        await expect(page.getByRole('heading', { name: 'Add / Edit Category' })).toBeVisible();
    });

    test('creates a new category', async ({ page }) => {
        const name = `E2E Category ${Date.now()}`;

        await page.goto('/categories');

        await page.locator('input[wire\\:model="name"]').fill(name);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Category saved.')).toBeVisible();
        await expect(page.getByText(name)).toBeVisible();
    });

    test('edits a category', async ({ page }) => {
        const original = `E2E Edit Cat ${Date.now()}`;
        const updated = `E2E Updated Cat ${Date.now()}`;

        await page.goto('/categories');

        // Create a category first.
        await page.locator('input[wire\\:model="name"]').fill(original);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Category saved.')).toBeVisible();

        // Open edit mode.
        const card = page.locator('.rounded-lg.border').filter({ hasText: original });
        await card.getByRole('button', { name: 'Edit' }).click();

        // Replace the name and save.
        await page.locator('input[wire\\:model="name"]').fill(updated);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Category saved.')).toBeVisible();
        await expect(page.getByText(updated)).toBeVisible();
        await expect(page.getByText(original)).not.toBeVisible();
    });

    test('deletes a category', async ({ page }) => {
        const name = `E2E Del Cat ${Date.now()}`;

        await page.goto('/categories');

        // Create a category to delete.
        await page.locator('input[wire\\:model="name"]').fill(name);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Category saved.')).toBeVisible();

        // Delete it.
        const card = page.locator('.rounded-lg.border').filter({ hasText: name });
        await card.getByRole('button', { name: 'Delete' }).click();

        await expect(page.getByText('Category removed.')).toBeVisible();
        await expect(page.getByText(name)).not.toBeVisible();
    });

    test('shows a validation error when the name is empty', async ({ page }) => {
        await page.goto('/categories');

        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText(/The name field is required/i)).toBeVisible();
    });
});
