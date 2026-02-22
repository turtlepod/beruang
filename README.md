# Beruang

WordPress plugin for per-user money, transaction, and budget tracking.

## Requirements

- WordPress 5.9+
- Logged-in users (data is scoped by user)

## Installation

1. Upload the `beruang` folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins**.
3. Tables are created on activation: `beruang_category`, `beruang_transaction`, `beruang_budget`, `beruang_budget_category`.

## Admin

- **Beruang** (top-level menu)
  - **Settings** – Currency (default IDR), decimal/thousands separators, export/import JSON
  - **Transactions** – List all transactions (filter by user)
  - **Categories** – List/manage categories (add, delete; filter by user)
  - **Budgets** – List budgets
  - **Budget Categories** – List budget–category links

## Shortcodes

Use on pages where users are logged in.

- **`[beruang-form]`** – Add transaction (date, time, description, category, amount, expense/income). Submit via AJAX. Optional calculator icon.
- **`[beruang-list]`** – Monthly transaction list in an accordion. Filter icon opens search and category filter.
- **`[beruang-graph]`** – Chart (by month or by category). Year selector. Uses Chart.js.
- **`[beruang-budget]`** – List budgets with progress; add/edit budget (name, target, type, categories).

## WP-CLI

- `wp beruang transaction list --user_id=1 [--year=] [--month=] [--type=expense|income] [--format=table|json|csv] [--fields=] [--per_page=] [--page=]`
- `wp beruang category list --user_id=1 [--format=] [--fields=]`
- `wp beruang budget list --user_id=1 [--format=] [--fields=]`
- `wp beruang budget-category list [--budget_id=] [--category_id=] [--format=] [--fields=]`

## Data

- All tables use singular names: `beruang_transaction`, `beruang_category`, `beruang_budget`, `beruang_budget_category`.
- Categories are hierarchical (`parent_id`). Default "Uncategorized" when no category is selected.
- Export (Settings) downloads JSON for the current user. Import accepts the same JSON format.

## Text domain

`beruang`. Place translations in `languages/`.

## Development

- **Lint**: `composer lint` or `npm run lint` – run PHPCS
- **Fix**: `composer lint-fix` – auto-fix with PHPCBF
- **Pre-commit hook**: Run `composer install && npm install` after clone. Husky + lint-staged runs PHPCS on staged `.php` files and blocks commits on lint errors.
