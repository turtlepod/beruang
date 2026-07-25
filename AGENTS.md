# AGENTS.md — Beruang Budget Plugin

## Version bump checklist

When bumping the plugin version, update ALL of these:

| # | File | Line / Key | Example |
|---|------|-----------|---------|
| 1 | `beruang-budget.php` | Plugin header `* Version: X.Y.Z` | `* Version: 0.6.0` |
| 2 | `beruang-budget.php` | `define( 'BERUANG_BUDGET_VERSION', 'X.Y.Z' )` | `define( 'BERUANG_BUDGET_VERSION', '0.6.0' )` |
| 3 | `readme.txt` | `Stable tag: X.Y.Z` | `Stable tag: 0.6.0` |
| 4 | `package.json` | `"version": "X.Y.Z"` | `"version": "0.6.0"` |
| 5 | `package-lock.json` | `"version": "X.Y.Z"` (2 occurrences: root + packages → "") | `"version": "0.6.0"` |
| 6 | `tests/bootstrap.php` | `define( 'BERUANG_BUDGET_VERSION', 'X.Y.Z' )` | `define( 'BERUANG_BUDGET_VERSION', '0.6.0' )` |

**Note:** `composer.json` has no version field — not needed.
