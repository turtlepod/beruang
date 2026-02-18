# Mowi

WordPress plugin for per-user money, transaction, and budget tracking.

## Requirements

- WordPress 5.9+
- Logged-in users (data is scoped by user)

## Installation

1. Upload the `mowi` folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins**.
3. Tables are created on activation: `mowi_category`, `mowi_transaction`, `mowi_budget`, `mowi_budget_category`.

## Admin

- **Mowi** (top-level menu)
  - **Settings** – Currency (default IDR), decimal/thousands separators, export/import JSON
  - **Transactions** – List all transactions (filter by user)
  - **Categories** – List/manage categories (add, delete; filter by user)
  - **Budgets** – List budgets
  - **Budget Categories** – List budget–category links

## Shortcodes

Use on pages where users are logged in.

- **`[mowi-form]`** – Add transaction (date, time, description, category, amount, expense/income). Submit via AJAX. Optional calculator icon.
- **`[mowi-list]`** – Monthly transaction list in an accordion. Filter icon opens search and category filter.
- **`[mowi-graph]`** – Chart (by month or by category). Year selector. Uses Chart.js.
- **`[mowi-budget]`** – List budgets with progress; add/edit budget (name, target, type, categories).

## WP-CLI

- `wp mowi transaction list --user_id=1 [--year=] [--month=] [--type=expense|income] [--format=table|json|csv] [--fields=] [--per_page=] [--page=]`
- `wp mowi category list --user_id=1 [--format=] [--fields=]`
- `wp mowi budget list --user_id=1 [--format=] [--fields=]`
- `wp mowi budget-category list [--budget_id=] [--category_id=] [--format=] [--fields=]`

## Data

- All tables use singular names: `mowi_transaction`, `mowi_category`, `mowi_budget`, `mowi_budget_category`.
- Categories are hierarchical (`parent_id`). Default "Uncategorized" when no category is selected.
- Export (Settings) downloads JSON for the current user. Import accepts the same JSON format.

## Text domain

`mowi`. Place translations in `languages/`.
