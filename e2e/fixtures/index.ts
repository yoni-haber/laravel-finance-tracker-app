import { test as base, expect } from '@playwright/test';

/**
 * Extend this with custom fixtures and page-object models as the suite grows.
 *
 * Example:
 *   import { DashboardPage } from './pages/DashboardPage';
 *   export const test = base.extend<{ dashboardPage: DashboardPage }>({
 *       dashboardPage: async ({ page }, use) => use(new DashboardPage(page)),
 *   });
 */
export const test = base;
export { expect };
