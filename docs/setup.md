# Beruang — Setup Guide

## Requirements

- WordPress 6.0+
- PHP 8.2+
- A logged-in user for each visitor (Beruang is a per-user app — all data is private per account)

---

## 1. Install & Activate

1. Upload the `beruang` plugin folder to `wp-content/plugins/`.
2. Go to **Plugins → Installed Plugins** and activate **Beruang Budget**.

The plugin creates its database tables automatically on activation.

---

## 2. Configure Currency & Number Format

Go to **Beruang → Settings**.

| Setting | Description | Example |
|---|---|---|
| Currency | Symbol shown on amounts | `IDR`, `$`, `€` |
| Decimal Separator | Character between integer and decimal | `,` or `.` |
| Thousand Separator | Character between digit groups | `.` or `,` |
| Decimal Places | Number of decimal digits (0–4) | `0` for IDR, `2` for USD |

Click **Save Changes**.

---

## 3. Activate the Beruang SaaS Theme

Beruang ships with a bare-bones SaaS theme (`beruang-saas`) designed for single-page money-tracking apps.

1. Go to **Appearance → Themes**.
2. Activate **Beruang SaaS**.

> You can also use any other theme and place shortcodes manually — see [Shortcodes](#6-shortcodes).

---

## 4. Create Pages

### 4a. Main App Page (Front Page)

1. Go to **Pages → Add New**.
2. Set the title — e.g. `Beruang`.
3. Under **Page Attributes → Template**, select **Beruang SaaS**.
4. Publish the page.

### 4b. Account Page

1. Go to **Pages → Add New**.
2. Set the title to `Account` and the slug to `account`.
3. Under **Page Attributes → Template**, select **Account**.
4. Publish the page.

---

## 5. Set the Front Page

1. Go to **Settings → Reading**.
2. Set **Your homepage displays** to **A static page**.
3. Set **Homepage** to the main app page created in step 4a.
4. Click **Save Changes**.

---

## 6. Configure Theme Settings

Go to **Beruang → Theme Settings**.

| Setting | Description |
|---|---|
| Content in Site Drawer | HTML/shortcodes shown in the slide-out sidebar menu |
| Account Page | Select the Account page created in step 4b |

Click **Save Changes**.

The header will now show a profile icon that links to the account page when a user is logged in.

---

## 7. Shortcodes

If you are not using the `beruang-saas` theme, place these shortcodes on any page or post:

| Shortcode | Output |
|---|---|
| `[beruang-form]` | Add / edit transaction form |
| `[beruang-list]` | Transaction list with filters and search |
| `[beruang-graph]` | Monthly / yearly charts |
| `[beruang-budget]` | Budget cards with progress |
| `[beruang-wallet]` | Wallet management (add, transfer, set default) |
| `[beruang_install_button]` | PWA install button (enable PWA in Settings) |

All shortcodes require the visitor to be logged in. Logged-out users see nothing.

---

## 8. Optional — PWA (Install to Home Screen)

1. Go to **Beruang → Settings** and scroll to the **PWA** section.
2. Enable PWA and fill in App Name, Short Name, and Theme Color.
3. Save Changes.

To render an install button anywhere on the site:

```
[beruang_install_button]
[beruang_install_button label="Install App" tag="a" class="my-class"]
```

---

## 9. Optional — Per-User Display Settings

Users can override currency, separators, and decimal places from the **Account → Budget** tab on the account page. These override the site-wide defaults for that user only.

---

## 10. Import / Export

Go to **Beruang → Settings** and scroll to the **Export / Import** section.

### Export
- **Export data (JSON)** — full export of all categories, wallets, transactions, and budgets.
- **Export transactions (CSV)** — transactions only, Excel-compatible.

### Import
- Upload a previously exported JSON file and click **Import from JSON**.
- The importer accepts full exports or partial exports (e.g. wallets-only files).

---

## 11. WP-CLI

Beruang includes WP-CLI commands for development and testing.

```bash
# Seed dummy data for a user
wp beruang seed --user_id=1 --transactions=100

# Reset all data for a user
wp beruang reset --user_id=1

# Add a transaction
wp beruang transaction add --user_id=1 --amount=50000 --type=expense --description="Lunch"

# List categories
wp beruang category list --user_id=1
```

---

## Quick-Start Checklist

- [ ] Plugin activated
- [ ] Currency & number format configured
- [ ] Beruang SaaS theme activated (or shortcodes placed manually)
- [ ] Main app page created with **Beruang SaaS** template
- [ ] Account page created with **Account** template, slug `/account`
- [ ] Front page set to the main app page
- [ ] Account page selected in **Beruang → Theme Settings**
- [ ] At least one registered user to log in and test
