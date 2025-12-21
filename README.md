# Laravel Finance Tracker

## Project Overview
Laravel Finance Tracker is a personal budgeting and finance dashboard built with the Laravel 12 Livewire starter kit. It lets a single authenticated user record transactions, group them into categories, set monthly budgets, track net worth entries, and review reports that summarise income vs. expenses over time.

- **Framework:** Laravel 12 with Fortify authentication, Livewire 3, and Volt/Flux for server-driven UI components.
- **Language:** PHP 8.2+ (tested in CI on PHP 8.4).
- **Frontend:** Blade-based Livewire views enhanced by Volt components; assets compiled with Vite, Tailwind CSS, and the Laravel Vite plugin.
- **Database:** SQLite by default for easy local setup (switchable to MySQL/PostgreSQL via `.env`).
- **Tooling:** Composer for PHP dependencies, npm for frontend tooling, and bundled scripts for setup, development, testing, and asset builds.

## Key Design Principles
- **Framework conventions first:** Routes point directly to Livewire classes and Volt pages, leaning on Laravel defaults instead of custom routing or service layers for clarity.
- **Separation of concerns:** Livewire components own screen-level interactions, Eloquent models handle data, and support classes (e.g., `App\Support\TransactionReport`) encapsulate reporting logic. Views stay thin and presentation-focused.
- **Simplicity over premature optimisation:** SQLite works out of the box, migrations seed the necessary schema, and scripts automate common tasks to allow development rather than environment wrangling.
- **Learning-first architecture:** The code follows Laravel’s directory conventions and uses explicit method naming (`mount`, `render`, `save`, `edit`, `delete`) to make the request/component lifecycle easy to follow.

## Directory Structure Explained
- **`app/Livewire`** – Screen-level components such as `Dashboard`, `TransactionManager`, `CategoryManager`, `BudgetManager`, `NetWorthTracker`, and `ReportsHub`. Each class manages its own state, validation rules, and rendering logic.
- **`app/Models`** – Eloquent models for `Transaction`, `Category`, `Budget`, `NetWorthEntry`, and related domain entities. They map database tables and relationships using Laravel’s Active Record pattern.
- **`app/Support`** – Reusable domain services such as `TransactionReport` and `Money` that perform calculations and reporting, keeping Livewire components lean.
- **`routes/web.php`** – Declares authenticated routes that point directly to Livewire classes and Volt-powered settings pages, demonstrating Laravel’s route-to-component workflow.
- **`resources/views`** – Blade and Livewire templates (including Volt partials) that render layouts, components, and screen views. Styles are compiled via Tailwind and Vite for a clean developer experience. 
- **`database/migrations` & `database/factories`** – Schema definitions and factories for seeding test data, ensuring the app is reproducible across machines.  
- **`config` & `bootstrap`** – Standard Laravel configuration and bootstrap files; largely unchanged to keep the focus on Laravel defaults and readability.  
- **`tests`** – Feature and unit tests (PHPUnit) that exercise critical flows and support CI automation.

## How the Application Works
1. **Request flow:** Authenticated routes defined in `routes/web.php` map to Livewire classes (e.g., `/transactions` → `TransactionManager`). Livewire handles request/response cycles server-side and re-renders Blade fragments as state changes.  
2. **Stateful components:** Each Livewire class exposes public properties for form state (amount, date, category, recurrence) and lifecycle hooks (`mount`, `render`, `updatedIsRecurring`) to set defaults and react to user interactions.
3. **Validation and persistence:** Actions like `save`, `edit`, and `delete` validate input, enforce user scoping with `Auth::id()`, and use Eloquent to insert or update rows. Recurring transactions support occurrence exceptions to omit specific dates.  
4. **Querying and reporting:** The dashboard gathers monthly transactions via `TransactionReport`, calculates income/expense totals, evaluates budgets, and emits chart data for category breakdowns, showcasing separation between reporting logic and UI.
5. **Views and layout:** Components render Blade templates under `resources/views/livewire`, wrapped in a shared layout (`components.layouts.app`) that wires Livewire/Volt assets, Tailwind styles, and Vite-built scripts for a cohesive UI.

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
- **Tests (`tests.yml`):** Installs PHP 8.4 and Node 22, builds assets, and runs PHPUnit to guard core flows like authentication and finance operations.
- **Linter (`lint.yml`):** Runs Laravel Pint and can auto-commit fixes on branches, ensuring consistent styling without manual effort.
- **Static analysis (`static-analysis.yml`):** Executes PHPStan over app, config, routes, database, and tests directories to catch type issues early.
- **Additional checks:** Workflows for coverage, dependency audits, secret scanning, migrations, and PHP security checks further harden the codebase (see `.github/workflows/`). 

## Skills Demonstrated
- Laravel 12 fundamentals: routing to Livewire components, Fortify auth, validation, Eloquent models, and schema migrations.  
- Livewire 3 + Volt patterns: server-driven UI state, reusable layouts, and interactive forms without heavy JavaScript. 
- Domain modelling: Transactions, categories, budgets, net worth entries, and recurring transaction support with occurrence exceptions.
- Frontend tooling: Vite, Tailwind CSS, and Laravel Vite plugin for modern asset pipelines.
- DevOps & quality: Multi-stage CI with tests, linting, static analysis, coverage, and security scans to mirror professional workflows.
