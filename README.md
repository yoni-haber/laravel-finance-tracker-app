# Laravel Finance Tracker

## Project Overview
Laravel Finance Tracker is a personal budgeting and finance dashboard built with the Laravel 12 Livewire starter kit. It lets a single authenticated user record transactions, group them into categories, set monthly budgets, track net worth entries, and review reports that summarise income vs. expenses over time. The project exists as a portfolio piece to demonstrate how to assemble a modern Laravel application with Livewire/Volt, Vite, and Tailwind while keeping the codebase approachable for new Laravel developers.

## Tech Stack
- **Framework:** Laravel 12 with Fortify authentication, Livewire 3, and Volt/Flux for server-driven UI components.【F:composer.json†L11-L70】【F:routes/web.php†L3-L40】  
- **Language:** PHP 8.2+ (tested in CI on PHP 8.4).【F:composer.json†L11-L27】【F:.github/workflows/tests.yml†L22-L54】  
- **Frontend:** Blade-based Livewire views enhanced by Volt components; assets compiled with Vite, Tailwind CSS, and the Laravel Vite plugin.【F:package.json†L4-L20】【F:resources/views】  
- **Database:** SQLite by default for easy local setup (switchable to MySQL/PostgreSQL via `.env`).【F:.env.example†L23-L65】  
- **Tooling:** Composer for PHP dependencies, npm for frontend tooling, and bundled scripts for setup, development, testing, and asset builds.【F:composer.json†L40-L75】【F:package.json†L4-L16】

## Key Design Principles
- **Framework conventions first:** Routes point directly to Livewire classes and Volt pages, leaning on Laravel defaults instead of custom routing or service layers for clarity.【F:routes/web.php†L13-L40】  
- **Separation of concerns:** Livewire components own screen-level interactions, Eloquent models handle data, and support classes (e.g., `App\Support\TransactionReport`) encapsulate reporting logic. Views stay thin and presentation-focused.【F:app/Livewire/Transactions/TransactionManager.php†L17-L103】  
- **Simplicity over premature optimisation:** SQLite works out of the box, migrations seed the necessary schema, and scripts automate common tasks so newcomers can focus on learning rather than environment wrangling.【F:.env.example†L23-L65】【F:composer.json†L40-L75】  
- **Learning-first architecture:** The code follows Laravel’s directory conventions and uses explicit method naming (`mount`, `render`, `save`, `edit`, `delete`) to make the request/component lifecycle easy to follow for readers exploring their first Laravel app.【F:app/Livewire/Transactions/TransactionManager.php†L33-L103】

## Directory Structure Explained
- **`app/Livewire`** – Screen-level components such as `Dashboard`, `TransactionManager`, `CategoryManager`, `BudgetManager`, `NetWorthTracker`, and `ReportsHub`. Each class manages its own state, validation rules, and rendering logic.【F:routes/web.php†L3-L23】【F:app/Livewire/Transactions/TransactionManager.php†L17-L103】  
- **`app/Models`** – Eloquent models for `Transaction`, `Category`, `Budget`, `NetWorthEntry`, and related domain entities. They map database tables and relationships using Laravel’s Active Record pattern.【F:app/Models】  
- **`app/Support`** – Reusable domain services such as `TransactionReport` and `Money` that perform calculations and reporting, keeping Livewire components lean.【F:app/Livewire/Dashboard.php†L5-L87】  
- **`routes/web.php`** – Declares authenticated routes that point directly to Livewire classes and Volt-powered settings pages, demonstrating Laravel’s route-to-component workflow.【F:routes/web.php†L13-L40】  
- **`resources/views`** – Blade and Livewire templates (including Volt partials) that render layouts, components, and screen views. Styles are compiled via Tailwind and Vite for a clean developer experience.【F:resources/views】【F:package.json†L4-L16】  
- **`database/migrations` & `database/factories`** – Schema definitions and factories for seeding test data, ensuring the app is reproducible across machines.  
- **`config` & `bootstrap`** – Standard Laravel configuration and bootstrap files; largely unchanged to keep the focus on Laravel defaults and readability.  
- **`tests`** – Feature and unit tests (PHPUnit) that exercise critical flows and support CI automation.【F:composer.json†L53-L56】【F:.github/workflows/tests.yml†L13-L54】

## How the Application Works
1. **Request flow:** Authenticated routes defined in `routes/web.php` map to Livewire classes (e.g., `/transactions` → `TransactionManager`). Livewire handles request/response cycles server-side and re-renders Blade fragments as state changes.【F:routes/web.php†L17-L40】【F:app/Livewire/Transactions/TransactionManager.php†L17-L103】  
2. **Stateful components:** Each Livewire class exposes public properties for form state (amount, date, category, recurrence) and lifecycle hooks (`mount`, `render`, `updatedIsRecurring`) to set defaults and react to user interactions.【F:app/Livewire/Transactions/TransactionManager.php†L33-L94】  
3. **Validation and persistence:** Actions like `save`, `edit`, and `delete` validate input, enforce user scoping with `Auth::id()`, and use Eloquent to insert or update rows. Recurring transactions support occurrence exceptions to omit specific dates.【F:app/Livewire/Transactions/TransactionManager.php†L45-L94】  
4. **Querying and reporting:** The dashboard gathers monthly transactions via `TransactionReport`, calculates income/expense totals, evaluates budgets, and emits chart data for category breakdowns, showcasing separation between reporting logic and UI.【F:app/Livewire/Dashboard.php†L33-L87】  
5. **Views and layout:** Components render Blade templates under `resources/views/livewire`, wrapped in a shared layout (`components.layouts.app`) that wires Livewire/Volt assets, Tailwind styles, and Vite-built scripts for a cohesive UI.【F:app/Livewire/Dashboard.php†L13-L18】【F:package.json†L4-L16】

## Running the Project Locally
1. **Prerequisites:** PHP 8.2+, Composer, Node 20+ (works with Node 22 in CI), npm, and SQLite (default).  
2. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/laravel-finance-tracker-app.git
   cd laravel-finance-tracker-app
   ```
3. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```
4. **Environment configuration:** Copy the example environment and generate an app key. SQLite is preconfigured so no extra DB setup is required.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
5. **Database setup:** Run migrations (and optionally seed) to create the schema.
   ```bash
   php artisan migrate
   # php artisan db:seed
   ```
6. **Run the dev servers:** Start Laravel and Vite in separate terminals, or use the bundled `composer dev` script to run PHP, queue worker, log tailing, and Vite concurrently.
   ```bash
   php artisan serve
   npm run dev
   # or
   composer dev
   ```
7. **Visit the app:** Open `http://localhost:8000` and register a user to start tracking income, expenses, budgets, and net worth entries.

## GitHub Actions / CI
The repository includes multiple workflows to keep quality high and demonstrate CI/CD practices:
- **Tests (`tests.yml`):** Installs PHP 8.4 and Node 22, builds assets, and runs PHPUnit to guard core flows like authentication and finance operations.【F:.github/workflows/tests.yml†L13-L54】  
- **Linter (`lint.yml`):** Runs Laravel Pint and can auto-commit fixes on branches, ensuring consistent styling without manual effort.【F:.github/workflows/lint.yml†L1-L54】  
- **Static analysis (`static-analysis.yml`):** Executes PHPStan over app, config, routes, database, and tests directories to catch type issues early.【F:.github/workflows/static-analysis.yml†L1-L38】  
- **Additional checks:** Workflows for coverage, dependency audits, secret scanning, migrations, and PHP security checks further harden the codebase (see `.github/workflows/`).【F:.github/workflows】  
Together these pipelines model professional-grade automation for a learning project, giving contributors rapid feedback and portfolio-ready signal.

## Skills Demonstrated
- Laravel 12 fundamentals: routing to Livewire components, Fortify auth, validation, Eloquent models, and schema migrations.  
- Livewire 3 + Volt patterns: server-driven UI state, reusable layouts, and interactive forms without heavy JavaScript.【F:routes/web.php†L13-L40】【F:app/Livewire/Transactions/TransactionManager.php†L17-L103】  
- Domain modelling: Transactions, categories, budgets, net worth entries, and recurring transaction support with occurrence exceptions.【F:app/Livewire/Transactions/TransactionManager.php†L45-L94】【F:app/Livewire/Dashboard.php†L33-L87】  
- Frontend tooling: Vite, Tailwind CSS, and Laravel Vite plugin for modern asset pipelines.【F:package.json†L4-L16】  
- Developer experience: Composer scripts for setup/dev/test, SQLite-first configuration, and Volt-powered settings screens to showcase security features like 2FA.【F:composer.json†L40-L75】【F:routes/web.php†L25-L40】  
- DevOps & quality: Multi-stage CI with tests, linting, static analysis, coverage, and security scans to mirror professional workflows.【F:.github/workflows/tests.yml†L13-L54】【F:.github/workflows/lint.yml†L1-L54】【F:.github/workflows/static-analysis.yml†L1-L38】

## Future Improvements
- Add budgeting insights such as variance trends and rolling averages to help users spot spending drift.  
- Introduce import/export for CSV/OFX so data can move between banks or other finance tools.  
- Add API endpoints (protected by tokens) to enable mobile clients or integrations.  
- Expand test coverage around recurring transaction edge cases and net worth calculations for stronger regression protection.  
- Offer multi-currency support with conversion rates for users managing finances across regions.
