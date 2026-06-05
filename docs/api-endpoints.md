# Beruang REST API Endpoints

All endpoints live under the namespace **`beruang/v1`** and are served by the WordPress REST API.

**Base URL:** `https://example.com/wp-json/beruang/v1`

**Authentication:** Every endpoint requires an authenticated WordPress user (`401` is returned otherwise). The standard WordPress cookie / nonce auth is used from the frontend shortcode. External clients should send the `Authorization` header or an application password.

**Response envelope:**

```json
{ "success": true, "data": { ... } }
{ "success": false, "data": { "message": "Error message." } }
```

---

## Transactions

### `POST /transactions`

Create a new transaction for the current user.

**Request body (JSON):**

| Field | Type | Required | Default | Notes |
|---|---|---|---|---|
| `date` | string | ✅ | — | `YYYY-MM-DD` |
| `description` | string | ✅ | — | |
| `amount` | number | ✅ | — | Positive value |
| `type` | string | — | `expense` | `expense` or `income` |
| `time` | string | — | null | `HH:MM` |
| `note` | string | — | `""` | |
| `category_id` | integer | — | `0` | `0` = no category |
| `wallet_id` | string\|null | — | null | `null` / `""` = no wallet |

**Response `200`:**
```json
{ "success": true, "data": { "id": 42 } }
```

**Errors:**
- `400` – Invalid wallet ID (wallet does not belong to user)
- `400` – Invalid category ID
- `400` – Failed to save

---

### `GET /transactions`

List transactions for the current user, with optional filtering and pagination.

**Query parameters:**

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `year` | integer | current year | Filter by year |
| `search` | string | `""` | Searches `description` |
| `category_id` | integer | `""` | Filter by single category |
| `budget_id` | integer | `""` | Filter by all categories in a budget |
| `wallet_id` | integer | `""` | Filter by wallet; `0` = no-wallet transactions |
| `page` | integer | `1` | |
| `per_page` | integer | `100` | Capped at `500` |

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "items": [ { "id": 1, "date": "2024-01-15", "description": "Grocery", "amount": "50.00", "type": "expense", "category_id": "3", "wallet_id": "2", "time": null, "note": "" } ],
    "total": 120,
    "pages": 2,
    "page": 1,
    "per_page": 100
  }
}
```

---

### `GET /transactions/{id}`

Retrieve a single transaction by ID (must belong to current user).

**Response `200`:**
```json
{ "success": true, "data": { "transaction": { "id": 42, ... } } }
```

**Errors:**
- `400` – Invalid ID
- `404` – Transaction not found

---

### `PUT /transactions/{id}`

Update an existing transaction.

**Request body (JSON):** Same optional fields as `POST /transactions`. Only supplied fields are updated; omitted fields keep their current value. Returns `400 No changes were made` if nothing changed.

**Response `200`:**
```json
{ "success": true, "data": { "id": 42 } }
```

**Errors:**
- `400` – Invalid ID / Invalid wallet / Invalid category / No changes / Failed to update
- `404` – Transaction not found

---

### `DELETE /transactions/{id}`

Delete a transaction owned by the current user.

**Response `200`:**
```json
{ "success": true, "data": { "deleted": true } }
```

---

## Categories

### `GET /categories`

List all categories (flat) for the current user, including hierarchy info (`parent_id`).

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "categories": [
      { "id": 1, "name": "Food", "parent_id": 0, "sort_order": 0 },
      { "id": 2, "name": "Lunch", "parent_id": 1, "sort_order": 0 }
    ]
  }
}
```

---

### `POST /categories`

Create or update a category. Pass `id > 0` to update an existing category.

**Request body (JSON):**

| Field | Type | Required | Default | Notes |
|---|---|---|---|---|
| `name` | string | ✅ | — | |
| `id` | integer | — | `0` | `0` = create |
| `parent_id` | integer | — | `0` | `0` = top-level |

**Response `200`:**
```json
{ "success": true, "data": { "id": 5 } }
```

**Errors:**
- `400` – Name required / Invalid parent category
- `404` – Category not found (when updating)
- `400` – Failed to save

---

### `DELETE /categories/{id}`

Delete a category owned by the current user. Child categories are also removed.

**Response `200`:**
```json
{ "success": true, "data": { "deleted": true } }
```

---

## Wallets

### `GET /wallets`

List all wallets for the current user, each including the live `current_amount` (initial balance + all linked transactions).

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "wallets": [
      {
        "id": 1,
        "name": "Cash",
        "initial_amount": 500.0,
        "initial_date": "2024-01-01",
        "current_amount": 850.0
      }
    ],
    "default_wallet_id": 1
  }
}
```

---

### `POST /wallets`

Create or update a wallet. Pass `id > 0` to update.

**Request body (JSON):**

| Field | Type | Required | Default | Notes |
|---|---|---|---|---|
| `name` | string | ✅ | — | |
| `id` | integer | — | `0` | `0` = create |
| `initial_amount` | number | — | `0` | Starting balance |
| `initial_date` | string | — | today | `YYYY-MM-DD` |
| `set_as_default` | boolean | — | `false` | Set this wallet as the default |

**Response `200`:**
```json
{ "success": true, "data": { "id": 3, "default_wallet_id": 3 } }
```

**Errors:**
- `400` – Name required
- `404` – Wallet not found (when updating)
- `400` – Failed to save

---

### `DELETE /wallets/{id}`

Delete a wallet. All transactions linked to this wallet will have their `wallet_id` set to `NULL`.

**Response `200`:**
```json
{ "success": true, "data": { "deleted": true } }
```

---

### `POST /wallets/transfer`

Transfer funds between two wallets. Creates two linked transactions: an expense on the source wallet and an income on the destination wallet.

**Request body (JSON):**

| Field | Type | Required | Default | Notes |
|---|---|---|---|---|
| `from_wallet_id` | integer | ✅ | — | Source wallet |
| `to_wallet_id` | integer | ✅ | — | Destination wallet |
| `amount` | number | ✅ | — | Positive amount |
| `date` | string | ✅ | — | `YYYY-MM-DD` |
| `category_id` | integer | — | `0` | Optional transfer category |
| `note` | string | — | `""` | |
| `time` | string | — | null | `HH:MM` |

**Response `200`:**
```json
{ "success": true, "data": { "from_id": 55, "to_id": 56 } }
```

**Errors:**
- `400` – Invalid from/to wallet, same wallet, invalid amount/date

---

### `POST /wallets/default`

Set the default wallet for the current user.

**Request body (JSON):**

| Field | Type | Notes |
|---|---|---|
| `wallet_id` | string | Wallet ID, or `""` / `null` to clear |

**Response `200`:**
```json
{ "success": true, "data": { "default_wallet_id": 2 } }
```

---

## Budgets

### `GET /budgets`

List all budgets for the current user, each with their linked `category_ids` and the current-period `spent` amount.

**Query parameters:**

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `year` | integer | current year | |
| `month` | integer | current month | For `monthly` budget type |

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "budgets": [
      {
        "id": 1,
        "name": "Food Budget",
        "target_amount": "500.00",
        "type": "monthly",
        "category_ids": [3, 4],
        "spent": 320.50
      }
    ]
  }
}
```

---

### `GET /budgets/{id}`

Get a single budget by ID (must belong to current user), with `spent` amount for the current period.

**Response `200`:**
```json
{ "success": true, "data": { "budget": { "id": 1, "name": "Food Budget", ... } } }
```

**Errors:**
- `404` – Budget not found

---

### `POST /budgets`

Create or update a budget. Pass `id > 0` to update.

**Request body (JSON):**

| Field | Type | Required | Default | Notes |
|---|---|---|---|---|
| `name` | string | ✅ | — | |
| `id` | integer | — | `0` | `0` = create |
| `target_amount` | number | — | `0` | |
| `type` | string | — | `monthly` | `monthly` or `yearly` |
| `category_ids` | integer[] | — | `[]` | Category IDs linked to this budget |

**Response `200`:**
```json
{ "success": true, "data": { "id": 2 } }
```

**Errors:**
- `400` – Name required
- `404` – Budget not found (when updating)
- `400` – Failed to save

---

### `DELETE /budgets/{id}`

Delete a budget owned by the current user. Linked transactions are not affected.

**Response `200`:**
```json
{ "success": true, "data": { "deleted": true } }
```

---

## Graph

### `GET /graph`

Get aggregated transaction data for chart rendering.

**Query parameters:**

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `year` | integer | current year | |
| `group_by` | string | `month` | `month` or `category` |
| `month` | integer | `0` | Used when `group_by=category`; `0` = whole year |

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "labels": ["Jan", "Feb", "Mar"],
    "expense": [300.0, 420.5, 280.0],
    "income": [1000.0, 1000.0, 1000.0]
  }
}
```

---

## Descriptions (Autocomplete)

### `GET /descriptions`

Return recent unique descriptions matching the search query for transaction autocomplete.

**Query parameters:**

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `search` | string | `""` | Returns empty list when blank |

**Response `200`:**
```json
{ "success": true, "data": { "descriptions": ["Grocery", "Lunch at cafe"] } }
```

---

## Export / Import

Export and import are **admin-only** features handled via WordPress admin form submissions (not REST endpoints). They live in the `Beruang Admin` settings page.

### Export

`POST /wp-admin/admin-post.php` with `action=beruang_export`

Downloads a JSON file containing:

```json
{
  "categories":   [ { "id": 1, "name": "Food", "parent_id": 0, "sort_order": 0 } ],
  "wallets":      [ { "id": 1, "name": "Cash", "initial_amount": 500.0, "initial_date": "2024-01-01" } ],
  "transactions": [ { "id": 1, "date": "2024-01-15", "description": "Grocery", "amount": "50.00", "type": "expense", "category_id": "3", "wallet_id": "1" } ],
  "budgets":      [ { "id": 1, "name": "Monthly Food", "target_amount": "500.00", "type": "monthly", "category_ids": [3] } ]
}
```

All four sections are always present in the export file (may be empty arrays). IDs are the database IDs of the exporting user.

### Import

`POST /wp-admin/admin-post.php` with `action=beruang_import` and a `beruang_import_file` file upload.

Handled by `ImportHandler::run()`. Processing order:

1. **Categories** — created first; parent IDs are remapped to new IDs as children are processed.
2. **Wallets** — created second; `initial_amount` and `initial_date` are preserved.
3. **Transactions** — `category_id` and `wallet_id` are remapped to the newly-created IDs.
4. **Budgets** — `category_ids` are remapped; categories not present in the import file are silently dropped.

**Validation rules:**
- File must be valid JSON.
- At least one of `categories`, `wallets`, `transactions`, or `budgets` must be a non-empty array.
- An import file containing only `wallets` is valid.
