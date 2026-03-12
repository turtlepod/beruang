=== Beruang Budget ===
Contributors: turtlepod
Tags: budget, finance, transactions, expense, income, personal finance
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 0.4.0-beta
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track money, transactions, and budgets.

== Description ==

Beruang Budget is a WordPress plugin for per-user money, transaction, and budget tracking. All data is scoped by logged-in user. Use shortcodes on front-end pages to add transactions, view lists, display charts, and manage budgets.

= Features =

* **Transactions** – Add and list transactions with date, time, description, category, amount, and type (expense/income)
* **Categories** – Hierarchical categories with optional parent; default "Uncategorized"
* **Budgets** – Create budgets with target amounts, link to categories, track progress (monthly or yearly)
* **Charts** – Graph data by month or by category using Chart.js
* **Admin** – Settings (currency, decimal/thousands separators), transaction/category/budget management, export/import JSON
* **WP-CLI** – List transactions, categories, budgets, and budget-category links via command line

= Requirements =

* WordPress 6.0+
* Logged-in users (data is scoped by user)

== Installation ==

1. Upload the `beruang` folder to `wp-content/plugins/`.
2. Activate the plugin in **Plugins**.
3. Tables are created on activation: `beruang_category`, `beruang_transaction`, `beruang_budget`, `beruang_budget_category`.

Run `npm install && npm run build` in the plugin directory before use. The plugin loads built assets from `dist/`.

= Shortcodes =

Use on pages where users are logged in:

* `[beruang-form]` – Add transaction form with optional calculator
* `[beruang-list]` – Monthly transaction list in an accordion with filter
* `[beruang-graph]` – Chart (by month or by category) with year selector
* `[beruang-budget]` – List budgets with progress; add/edit budget modal

== Resources ==

Font Awesome Free Icons
* Icons: https://fontawesome.com/icons
* License: CC BY 4.0 - https://creativecommons.org/licenses/by/4.0/
* The plugin uses inline SVG paths derived from Font Awesome Free icons: xmark (close), calculator (calc), list-ul (list), filter (filter), file-circle-plus (add), pen-to-square (edit), trash-can (trash).

== Changelog ==

= 0.4.0-beta =
* Several fixes
* Wallet
* Notes

= 0.3.0-beta =
* Refactored front-end CSS into component-based stylesheets and improved responsive rules.
* Refactored front-end JavaScript into modular files for form, list, graph, budget, config, and shared utilities.
* Updated admin management screens to use dedicated WP List Table classes for transactions, categories, and budgets.
* Reworked templates into partials (including shared modals and transaction form partial).
* Improved form UX with loading states, clearer error messages, calculator behavior, and focus/style fixes.
* Improved asset/manifest loading behavior and refreshed plugin icons.

= 0.2.0-beta =
* Use REST API instead of admin-ajax
* Minify JS and CSS via 10up-toolkit
* Add ESLint, stylelint, PHPCS
* Reset form after submit
* Convert to vanilla JavaScript (remove jQuery)

= 0.1.0-beta =
* Initial beta release
* Transaction, category, and budget management
* Shortcodes: form, list, graph, budget
* Admin pages: Settings, Transactions, Categories, Budgets
* WP-CLI support
* Export/import JSON
