# Beruang – Playwright E2E Tests

End-to-end tests for every Beruang shortcode and its UI, running against a live local WordPress installation.

---

## Requirements

| Tool | Min version |
|------|-------------|
| Node.js | 18 |
| WP-CLI | any recent |
| Local by Flywheel (or equivalent) | site running at `https://beruang.test` |

---

## Quick start

```bash
# 1. Install dependencies (first time only)
npm install

# 2. Install Playwright's Chromium browser (first time only)
npx playwright install chromium

# 3. Run all tests
npm run test:e2e

# 4. Open the interactive UI runner
npm run test:e2e:ui
```

---

## Configuration

### Site URL

Tests default to `https://beruang.test`. Override with an environment variable:

```bash
WP_BASE_URL=https://mysite.local npm run test:e2e
```

### WordPress credentials

The global setup logs in as a **subscriber** user to create the authenticated session. Defaults:

| Variable | Default |
|---|---|
| `WP_TEST_USER` | `test` |
| `WP_TEST_PASS` | `test123` |

Override when needed:

```bash
WP_TEST_USER=alice WP_TEST_PASS=secret npm run test:e2e
```

### WordPress path

The global setup uses WP-CLI to create/delete test pages. The WordPress root defaults to:

```
/Users/davidchandra/Sites/beruang/app/public
```

Override with `WP_PATH`:

```bash
WP_PATH=/srv/www/beruang/public npm run test:e2e
```

---

## How it works

### Global setup (`global-setup.ts`)

Runs once **before** the test suite:

1. Creates one WordPress page per shortcode (title prefix `E2E Beruang …`) using WP-CLI.
   If a page with the same title already exists it is deleted first, so every run starts clean.
2. Saves the page IDs and URLs to `tests/e2e/.test-pages.json` for use by the fixture.
3. Launches a headless Chromium, logs in via `wp-login.php`, and saves the authenticated browser state to `tests/e2e/.auth/user.json`.

### Global teardown (`global-teardown.ts`)

Runs once **after** the test suite and permanently deletes the five test pages from WordPress.

### Page URL fixture (`fixtures/pages.ts`)

Extends Playwright's `test` object with a `urls` fixture.
Every spec file imports `test` and `expect` from this fixture instead of from `@playwright/test` directly, so each test receives the live page URLs without any boilerplate.

```ts
import { test, expect } from '../fixtures/pages';

test('example', async ({ page, urls }) => {
  await page.goto(urls.form);
});
```

---

## Directory layout

```
tests/e2e/
├── README.md              ← pointer to docs/e2e-tests.md
├── global-setup.ts        ← creates test pages + saves auth state
├── global-teardown.ts     ← deletes test pages
├── .auth/                 ← generated – gitignored
│   └── user.json          ← saved browser storage state
├── .test-pages.json       ← generated – gitignored
├── fixtures/
│   └── pages.ts           ← urls fixture
└── specs/
    ├── auth.spec.ts        ← unauthenticated access (all 5 shortcodes)
    ├── form.spec.ts        ← [beruang-form]
    ├── list.spec.ts        ← [beruang-list]
    ├── graph.spec.ts       ← [beruang-graph]
    ├── budget.spec.ts      ← [beruang-budget]
    └── wallet.spec.ts      ← [beruang-wallet]
```

---

## Test coverage

### `auth.spec.ts` — Unauthenticated access (5 tests)

Overrides the storage state to simulate a logged-out visitor and asserts that every shortcode page shows its "Please log in …" message.

### `form.spec.ts` — `[beruang-form]` (22 tests)

| Area | What is tested |
|---|---|
| Rendering | Form wrapper, form element, date pre-fill, placeholder, empty amount, Uncategorized category |
| Type toggle | Expense active by default, switching to Income, switching back |
| Calculator modal | Hidden on load, opens via button, closes via ×, display starts at `0` |
| Note modal | Hidden on load, opens via button, closes via Cancel |
| Categories modal | Hidden on load, opens via button, has name + parent inputs, closes via Close button |
| Submission | POST `/beruang/v1/transactions` returns HTTP 200 |

### `list.spec.ts` — `[beruang-list]` (14 tests)

| Area | What is tested |
|---|---|
| Rendering | Wrapper, "Transactions" heading, filter button, accordion container, `data-year` attribute |
| Filters panel | Hidden on load, toggles open, year/search/category/budget controls, current-year pre-select, option labels, Apply + Reset buttons |
| Edit modal | Hidden on load |
| Loading | Accordion stops showing "Loading…" once the REST call returns |
| Integration | A transaction submitted on the form page appears in the list |

### `graph.spec.ts` — `[beruang-graph]` (11 tests)

| Area | What is tested |
|---|---|
| Rendering | Wrapper, "Graph" heading, canvas element exists in wrapper, filter button |
| Filters panel | Hidden on load, toggles open, year + grouping selects, current-year pre-select, "By month" default, both option values present, can switch to "By category" |

### `budget.spec.ts` — `[beruang-budget]` (12 tests)

| Area | What is tested |
|---|---|
| Rendering | Wrapper, "Budgets" heading, Add button, filter button, list container with `data-year`/`data-month` attributes |
| Filters panel | Hidden on load, toggles open, year/month/Apply/Reset controls, 12 month options |
| Budget modal | Hidden on load, opens via Add button, has name + target + type fields, Monthly/Yearly options, closes via × and Cancel |
| Integration | Creating a budget closes the modal and shows the budget name in the list |

### `wallet.spec.ts` — `[beruang-wallet]` (13 tests)

| Area | What is tested |
|---|---|
| Rendering | Wrapper, "Wallets" heading, Add button, wallet list container |
| Wallet modal | Hidden on load, opens via Add button, has name/amount/date/default fields, date pre-filled with today, closes via × and Cancel |
| Integration | Creating a wallet closes the modal and shows the name + edit/delete buttons; edit button opens the modal pre-filled with the wallet's name |

**Total: 77 tests**

---

## Running a single spec file

```bash
npx playwright test form.spec.ts
npx playwright test wallet.spec.ts
```

## Running a single test by title

```bash
npx playwright test --grep "calc button opens"
npx playwright test --grep "creates a wallet"
```

## Headed mode (see the browser)

```bash
npx playwright test --headed
```

## Debug mode (step through)

```bash
npx playwright test --debug
```

## View the HTML report

```bash
npx playwright show-report tests/e2e/report
```

---

## Bugs found during test authoring

Running these tests against the live WordPress instance exposed two real bugs that were fixed:

### 1. REST API — `wallet_id` type schema too strict

**File:** `includes/rest.php`
**Problem:** The `wallet_id` parameter for `POST /beruang/v1/transactions` and `PUT /beruang/v1/transactions/<id>` was declared as `type: string`. The frontend JS sends `null` when the user selects "No Wallet", which failed WordPress REST API validation with HTTP 400 `rest_invalid_param`.
**Fix:** Changed the schema type to `array('string', 'null')` for both endpoints.

### 2. Database — `wallet_id` column not nullable

**File:** `includes/class-beruang-db.php`
**Problem:** The PHP schema defined `wallet_id bigint(20) unsigned DEFAULT NULL`, but the live table column was `NOT NULL DEFAULT 0` because `dbDelta()` does not `ALTER` existing column constraints. The existing `migrate_data()` function tried to `SET wallet_id = NULL` but failed silently against the NOT NULL column.
**Fix:** Bumped `DB_VERSION` to `4` and added `ALTER TABLE … MODIFY wallet_id bigint(20) unsigned DEFAULT NULL` at the start of `migrate_data()`, before the existing UPDATE that converts `wallet_id = 0` to `NULL`.

---

## CI / zip integration

E2E tests are included in the `npm run zip` pipeline:

```
npm run build  →  composer run test  →  npx playwright test  →  zip
```

For CI environments where a live WordPress site is not available, skip E2E tests and run only unit tests:

```bash
npm run build && composer run test
```
