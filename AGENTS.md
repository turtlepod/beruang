# Beruang Plugin — AI Agent Quick Reference

> Minimal reference for AI agents. Read this first to reduce token usage; dive into specific files only when needed.

## Identity

| | |
|---|---|
| **Purpose** | Per-user money tracking: transactions, wallets, budgets |
| **Namespace** | `Beruang` |
| **PHP** | 8.2+ |
| **Text domain** | `beruang` |

## Constants

| Constant | File | Meaning |
|----------|------|---------|
| `BERUANG_VERSION` | beruang.php | Version string |
| `BERUANG_PLUGIN_FILE` | beruang.php | Main plugin file path |
| `BERUANG_PLUGIN_DIR` | beruang.php | Plugin dir (trailing slash) |
| `BERUANG_PLUGIN_URL` | beruang.php | Plugin URL |
| `ADMIN_SLUG` | includes/admin.php | `'beruang'` |
| `ADMIN_CAPABILITY` | includes/admin.php | `'manage_options'` |
| `DB::DB_VERSION` | class-beruang-db.php | Schema version (4) |

## File Map

```
beruang.php                    Entry; constants; loads core + WP-CLI
includes/
  core.php                     Bootstrap; activation; enqueue; print templates
  class-beruang-db.php         DB schema + CRUD (Category, Wallet, Transaction, Budget)
  icon-helpers.php             beruang_get_icons(), beruang_icon()
  seed.php                     seed_dummy_data($user_id, $tx_count)
  admin.php                    Admin UI, settings, list tables, admin_post_* handlers
  rest.php                     REST routes; permission: rest_permission_logged_in
  manifest.php                 PWA manifest + service worker
  shortcodes.php               Shortcode registration + renderers
  templates-js.php             Inline JS templates for frontend
  class-beruang-cli.php        WP-CLI: beruang transaction|category|budget|seed|reset
  class-beruang-*-list-table.php  Transactions, Categories, Budgets, Wallets
templates/                     [beruang-form], [beruang-list], [beruang-graph], [beruang-budget], [beruang-wallet]
assets/js/frontend/            form, list, graph, budget, wallet modules
dist/                          Built JS/CSS (10up-toolkit); required for front scripts
```

## Data Model

| Table | Key Fields |
|-------|------------|
| `{prefix}_beruang_category` | id, user_id, name, parent_id, sort_order |
| `{prefix}_beruang_wallet` | id, user_id, name, initial_amount, initial_date |
| `{prefix}_beruang_transaction` | id, user_id, date, time, description, note, wallet_id, category_id, amount, type |
| `{prefix}_beruang_budget` | id, user_id, name, target_amount, type |
| `{prefix}_beruang_budget_category` | budget_id, category_id |

All data is **per-user** (`user_id`). Default wallet stored in user meta: `beruang_default_wallet_id`.

## REST API (`/wp-json/beruang/v1`)

| Route | Methods | Handler |
|-------|---------|---------|
| `/transactions` | GET, POST | rest_get_transactions, rest_save_transaction |
| `/transactions/(?P<id>\d+)` | GET, PUT, DELETE | rest_get_transaction, rest_update_transaction, rest_delete_transaction |
| `/descriptions` | GET | rest_get_descriptions |
| `/categories` | GET, POST | rest_get_categories, rest_save_category |
| `/categories/(?P<id>\d+)` | DELETE | rest_delete_category |
| `/wallets` | GET, POST | rest_get_wallets, rest_save_wallet |
| `/wallets/default` | POST | rest_set_default_wallet |
| `/wallets/(?P<id>\d+)` | DELETE | rest_delete_wallet |
| `/budgets` | GET, POST | rest_get_budgets, rest_save_budget |
| `/budgets/(?P<id>\d+)` | GET, DELETE | rest_get_budget, rest_delete_budget |
| `/graph` | GET | rest_get_graph_data |

## Shortcodes

| Shortcode | Template | Notes |
|-----------|----------|-------|
| `[beruang-form]` | templates/form.php | Add transaction |
| `[beruang-list]` | templates/list.php | Transaction list + filters |
| `[beruang-graph]` | templates/graph.php | Chart.js charts |
| `[beruang-budget]` | templates/budget.php | Budget cards |
| `[beruang-wallet]` | templates/wallet.php | Wallet CRUD |

All require logged-in user. Frontend JS uses `window.beruangData` (restUrl, restNonce, i18n).

## Key Hooks

| Hook | Callback | Location |
|------|----------|----------|
| `plugins_loaded` | on_plugins_loaded, admin_setup | core.php, admin.php |
| `init` | shortcodes_setup, manifest_register_rewrite | shortcodes.php, manifest.php |
| `wp_enqueue_scripts` | enqueue_front_scripts | core.php |
| `wp_footer` | print_front_templates | core.php |
| `rest_api_init` | rest_register_routes | rest.php |
| `admin_post_beruang_*` | admin_handle_* | admin.php |

## JS Modules (assets/js/frontend/)

| File | Exports | Purpose |
|------|---------|---------|
| config.js | restUrl, restNonce, i18n, getDecimalPlaces | From window.beruangData |
| utils.js | request(), beruangTemplate(), formatNum() | Fetch + templating |
| form.js | initForm() | Transaction form, calculator, categories modal |
| list.js | initList() | List, filters, edit modal |
| graph.js | initGraph() | Chart.js charts |
| budget.js | initBudget() | Budget CRUD |
| wallet.js | initWallet() | Wallet CRUD |

## Options (WP)

- `beruang_currency`, `beruang_decimal_sep`, `beruang_thousands_sep`, `beruang_decimal_places`
- `beruang_pwa_enabled`, `beruang_pwa_app_name`, `beruang_pwa_short_name`, `beruang_pwa_theme_color`
- `beruang_db_version` (internal)

## Docs

Extended documentation lives in `docs/`:

| File | Contents |
|------|----------|
| `docs/e2e-tests.md` | Playwright E2E test setup, configuration, coverage tables, CLI commands, bugs found |

## Build

- `npm run build` → `dist/` (10up-toolkit)
- Entry: `beruang-front.js` → `dist/js/front.js`
- Front scripts only load when `dist/` exists.

## Quick Edit Targets

| Task | Primary file(s) |
|------|-----------------|
| Add REST endpoint | includes/rest.php |
| Change DB schema | includes/class-beruang-db.php |
| Modify transaction form | templates/form.php, assets/js/frontend/form.js |
| Admin list behavior | includes/class-beruang-*-list-table.php |
| Shortcode output | includes/shortcodes.php + templates/*.php |
| Frontend styles | assets/css/components/*.css |
