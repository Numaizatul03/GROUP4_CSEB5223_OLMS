# OLMS JSON API (Lab 7 & 8)

The API lets other programs, and the shop page itself, read OLMS data as JSON.
It uses the same services and rules as the website, so both always agree.

- Entry point: `api.php`
- Address format: `api.php/<path>`, for example `api.php/products/FRP-001`
- If your server does not support this format, use `api.php?path=/products/FRP-001`
- Every answer is JSON with `Content-Type: application/json`

## 1. Answer format

Every successful answer looks like this:

```json
{ "data": { ... }, "meta": { ... } }
```

Every error looks like this:

```json
{ "error": { "code": "validation_failed", "message": "Some values are not valid.", "fields": { "quantity": "..." } } }
```

`code` is for programs to check. `message` is a plain sentence for people.
`fields` only appears for input mistakes. Errors never show SQL, file paths or other technical details.

## 2. Endpoints

| Method | Path | Who can use it | What it does |
|--------|------|----------------|--------------|
| GET | `/health` | Anyone | Checks that the API and database work |
| GET | `/products` | Anyone | Lists or searches products, one page at a time |
| GET | `/products/{id or code}` | Anyone | One product, by ID (`3`) or code (`FRP-003`) |
| GET | `/products/{id or code}/stock` | Anyone | Live stock check (used by the shop page) |
| GET | `/categories` | Anyone | All categories with their codes |
| GET | `/cart` | Logged-in customer | The customer's own cart |
| POST | `/cart/items` | Logged-in customer | Add a product to the customer's own cart |
| GET | `/orders` | Administrator | All orders, with optional filters |

### GET /products

Query values (all optional):

| Name | Rule |
|------|------|
| `keyword` | Matches name, code or description. Up to 100 characters. `%` and `_` are matched as normal characters. |
| `categoryId` | Whole number |
| `minPrice`, `maxPrice` | Numbers such as `2.50`. Max must not be below min. |
| `page` | Whole number from 1 to 1000 (default 1) |
| `perPage` | Whole number from 1 to 50 (default 20) |

`meta` contains `total`, `page`, `perPage` and `totalPages`. A search with no match is not an error: `data` is `[]`.

### GET /products/{id or code}/stock

Optional `quantity` (whole number from 1 to 20, the cart limit). The answer says
`isAvailable` (can this quantity be bought?), `stockQuantity`, `stockStatus` and `maxQuantityPerOrder`.

### POST /cart/items

Send JSON: `{ "productId": 3, "quantity": 2 }` (`quantity` defaults to 1).

- Needs the header `X-CSRF-Token` with the token from the page's `<meta name="csrf-token">` tag.
- The cart owner always comes from the login session. A `userId` in the body is ignored.
- Answers `201 Created` with the updated cart and a message.

### GET /orders

Same filters as the Manage Orders page: `orderStatus`, `dateFrom`, `dateTo` (YYYY-MM-DD), `keyword`, `orderId`.

## 3. Status codes

| Code | When |
|------|------|
| 200 | OK |
| 201 | Item added to the cart |
| 400 | Broken JSON, a list instead of a single value, or a product key that is neither an ID nor a code |
| 401 | You must log in first |
| 403 | Your account is not allowed (for example a customer asking for `/orders`) |
| 404 | No such product or endpoint |
| 405 | Wrong method (the `Allow` header lists the right one) |
| 409 | The action breaks a shop rule, for example more than the stock |
| 419 | Missing or wrong CSRF token on a POST |
| 422 | Some values are not valid; see `fields` |
| 429 | More than 60 requests in one minute from the same computer; see `Retry-After` |
| 500 | Unexpected error; the message has a reference code such as `ERR-3F9A1C` |
| 503 | The database cannot be reached |

## 4. Middleware

Every API call (and every web page) goes through the same steps, in this order,
before the controller runs. They are in `src/middleware/`.

| Step | Class | What it does |
|------|-------|--------------|
| 1 | `ErrorHandlingMiddleware` | Catches any error and answers 500 or 503 safely; logs details to `storage/logs/app.log` |
| 2 | `SecurityHeadersMiddleware` | Adds browser protection headers (Content-Security-Policy, X-Frame-Options and more) |
| 3 | `InputGuardMiddleware` | Refuses lists, values over 5000 characters and broken JSON (400) |
| 4 | `SessionMiddleware` | Safe session cookie; logs out users idle for 30 minutes |
| 5 | `AuthenticationMiddleware` | Connects to the database and finds the logged-in user |
| 6 | `RoutingMiddleware` | Finds the endpoint (404) and checks the method (405) |
| 7 | `RateLimitMiddleware` | 60 API calls per minute; login lock after 5 wrong passwords (429) |
| 8 | `AuthorizationMiddleware` | Checks the user's role (401, 403) |
| 9 | `CsrfMiddleware` | Checks the CSRF token on every change (419) |

## 5. Examples

```
GET api.php/products?keyword=eggs
GET api.php/products/FRP-002
GET api.php/products/3/stock?quantity=4
GET api.php/categories
```
