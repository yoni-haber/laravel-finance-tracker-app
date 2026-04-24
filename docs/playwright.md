# Playwright E2E Testing

Playwright tests live in `e2e/` at the project root — separate from `tests/` (PHPUnit) because they are a JavaScript tool that exercises the app through a real browser, not the PHP layer.

## Prerequisites

The database must be migrated and seeded before running E2E tests:

```bash
php artisan migrate
php artisan db:seed
```

The seeder creates `alex@example.com` / `password` — the default test user.

## Running tests

```bash
# Run the full suite (starts the Laravel server automatically)
npx playwright test

# Interactive UI — best for development; shows browser, timeline, and trace
npx playwright test --ui

# Run a single file
npx playwright test e2e/login.spec.ts

# Run only the chromium project (fastest for local spot-checks)
npx playwright test --project=chromium

# Run tests matching a title keyword
npx playwright test --grep "dashboard"

# Debug mode — pauses on each step, opens DevTools
npx playwright test --debug

# Generate a test by clicking through the app
npx playwright codegen http://127.0.0.1:8000
```

> **Tip:** The `webServer` in `playwright.config.ts` starts `php artisan serve` automatically. If you already have the server running (e.g. via `composer dev`), Playwright will reuse it.

## File and directory map

```
e2e/
├── fixtures/
│   ├── auth.setup.ts   # Logs in once and saves session to playwright/.auth/user.json
│   └── index.ts        # Re-exports test + expect — import from here in every spec
├── login.spec.ts        # Auth flows (runs as unauthenticated guest)
│
playwright/.auth/        # Saved browser sessions — git-ignored, created at runtime
playwright-report/       # HTML report from the last run — git-ignored
test-results/            # Trace files and failure screenshots — git-ignored
playwright.config.ts     # Project config: browsers, baseURL, webServer, reporters
tsconfig.json            # TypeScript config scoped to playwright.config.ts + e2e/**
```

## Authentication

Authentication is handled by a **setup project** in `playwright.config.ts`. It runs `e2e/fixtures/auth.setup.ts` once before all browser projects:

1. Opens `/login`, fills in the test-user credentials, submits the form.
2. Saves the resulting cookies/localStorage to `playwright/.auth/user.json`.
3. Every browser project (`chromium`, `firefox`, `webkit`) loads that file as its `storageState`, so every test starts already logged in.

**Writing a test that needs authentication** — just use the normal import; auth is injected automatically:

```typescript
import { test, expect } from './fixtures';

test('shows the dashboard', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
});
```

**Writing a test that must run as a guest** (e.g. login, register pages) — override `storageState` at the top of the file:

```typescript
import { test, expect } from './fixtures';

test.use({ storageState: { cookies: [], origins: [] } });

test('redirects to login when unauthenticated', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL('/login');
});
```

## Grouping tests — follow the app structure

Mirror the `app/Livewire/` directory structure in `e2e/` so tests are easy to find:

```
e2e/
├── fixtures/
│   ├── auth.setup.ts
│   └── index.ts
├── auth/
│   ├── login.spec.ts
│   └── register.spec.ts
├── dashboard/
│   └── dashboard.spec.ts
├── transactions/
│   ├── create.spec.ts
│   ├── edit.spec.ts
│   └── recurring.spec.ts
├── budgets/
│   └── budgets.spec.ts
├── categories/
│   └── categories.spec.ts
├── reports/
│   └── reports.spec.ts
└── net-worth/
    └── net-worth.spec.ts
```

## Selectors — what to use

Prefer selectors in this order:

| Method | When to use |
|---|---|
| `page.getByRole()` | Buttons, links, headings, inputs by ARIA role |
| `page.getByLabel()` | Form fields — matches the `<label>` text |
| `page.getByText()` | Non-interactive content |
| `page.getByTestId()` | Anything that lacks a reliable role or label |

Add `data-test="..."` attributes to Blade/Flux components when no semantic selector exists — the login button already uses this pattern (`data-test="login-button"`).

Avoid raw CSS selectors like `input[name="email"]` — they break when markup changes.

## Expanding the fixture

`e2e/fixtures/index.ts` re-exports `test` and is the place to add **page-object fixtures** as the suite grows:

```typescript
import { test as base, expect } from '@playwright/test';
import { TransactionsPage } from './pages/TransactionsPage';

export const test = base.extend<{ transactionsPage: TransactionsPage }>({
    transactionsPage: async ({ page }, use) => use(new TransactionsPage(page)),
});

export { expect };
```

Page objects live in `e2e/fixtures/pages/` and encapsulate selectors and actions for a single screen, keeping individual specs readable.

## Multiple test users

The seeder also creates `jamie@example.com` / `password`. To test with a second role, add a second setup file and project:

```typescript
// e2e/fixtures/auth-jamie.setup.ts
const authFile = path.join('playwright', '.auth', 'jamie.json');

setup('authenticate as jamie', async ({ page }) => {
    // ... same login steps with jamie's credentials
    await page.context().storageState({ path: authFile });
});
```

Then add a `jamie` project in `playwright.config.ts` that depends on `setup-jamie` and uses `storageState: 'playwright/.auth/jamie.json'`.

## CI

The Playwright workflow (`.github/workflows/playwright.yml`) runs automatically on push and pull requests to `main`. It:

1. Installs PHP/Node dependencies (including Flux credentials from repository secrets).
2. Seeds the database.
3. Runs only the `setup` and `chromium` projects — fast, focused CI feedback.
4. Uploads the HTML report as a build artifact (retained 30 days).

To run all three browsers in CI, change the workflow step to:

```bash
npx playwright test
```

## TypeScript

`tsconfig.json` at the project root covers `playwright.config.ts` and all `e2e/**/*.ts` files. Key settings:

| Option | Purpose |
|---|---|
| `"module": "ESNext"` | Enables `import.meta.url` (required for ESM-aware `__dirname` equivalent) |
| `"esModuleInterop": true` | Allows default imports of CJS modules (`path`, `fs`, `dotenv`) |
| `"moduleResolution": "bundler"` | Correct resolution for an ESM project where Playwright handles transpilation |
| `"noEmit": true` | Type-check only — Playwright does its own TS transform at runtime |

Type-check without running tests:

```bash
./node_modules/.bin/tsc --noEmit
```

## Viewing results

```bash
# Open the HTML report after a local run
npx playwright show-report

# Open the trace viewer for a specific trace file
npx playwright show-trace test-results/<test-name>/trace.zip
```
