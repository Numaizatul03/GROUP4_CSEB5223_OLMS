# Product Module (Lab 3 & 4)

Use case: **Administrator - Manage Products** (Assignment Part 1, Section 2.2).
Class: **Product** (Section 3.4) with **Category** (Section 3.5).

## 1. Classes and responsibilities

| Class | File | Responsibility |
|-------|------|----------------|
| Product | `src/models/product.php` | Holds product attributes, getters/setters, stock rules |
| Category | `src/models/category.php` | Holds category attributes |
| ProductService | `src/services/product_service.php` | All SQL for the products table (CRUD + search) |
| CategoryService | `src/services/category_service.php` | Reads categories for dropdowns and validation |
| ProductValidator | `src/services/product_validator.php` | Checks form input and converts it to typed values |
| SessionService | `src/services/session_service.php` | Flash messages and CSRF tokens |
| ViewService | `src/services/view_service.php` | Renders views in the shared layout |
| ProductController | `src/controllers/product_controller.php` | One method per page or form submission |

Table 1: Product module classes

## 2. Product attributes and data types

| Attribute | PHP type | Database column | Rule |
|-----------|----------|-----------------|------|
| productId | `?int` | `product_id INT UNSIGNED AUTO_INCREMENT` | Primary key |
| categoryId | `int` | `category_id INT UNSIGNED` | Must be an existing category |
| productName | `string` | `product_name VARCHAR(100)` | Required, max 100 characters |
| description | `string` | `description TEXT` | Required, max 1000 characters |
| price | `float` | `price DECIMAL(10,2)` | More than 0, max 99,999.99, 2 decimals |
| stockQuantity | `int` | `stock_quantity INT UNSIGNED` | Whole number 0 - 100,000 |
| createdAt / updatedAt | `?string` | `created_at` / `updated_at DATETIME` | Set by the system |

Table 2: Product attributes

## 3. Page flows

| Function | Steps |
|----------|-------|
| Create | `create` form -> `store` (validate) -> errors shown on the form, or redirect to `list` with the new row highlighted |
| Search | `search` with criteria -> results table, or "Not found" -> full product list underneath |
| Edit | `edit_search` -> `edit` form (prefilled) -> `update` -> `edit_result` (before/after table + full list) |
| Delete | `delete_search` -> `delete_confirm` (all attributes + "Are you sure?") -> `destroy` -> `delete_result` (deleted record, count before/after, database check, full list) |
| Display all | `list` - every product in one table, also shown after search, edit and delete |

Table 3: Page flows

## 4. Method documentation

Format from Assignment Part 1, Section 4.2.

| Function/Method Name | Purpose | Parameters | Return Value | Description |
|---|---|---|---|---|
| `ProductService::getAllProducts()` | Fetch every product | none | `Product[]` | Joins categories; ordered by ID |
| `ProductService::findProductById()` | Fetch one product | `int $productId` | `?Product` | `null` means not found |
| `ProductService::searchProduct()` | Find matching products | `array $searchCriteria` (productId, keyword, categoryId, minPrice, maxPrice) | `Product[]` | Builds the WHERE clause only from the criteria given |
| `ProductService::createProduct()` | Insert a product | `array $productData` | `int` new ID | Input must be validated first |
| `ProductService::updateProduct()` | Save changes | `Product $product` | `bool` | Refreshes `updated_at` |
| `ProductService::deleteProduct()` | Remove a product | `int $productId` | `bool` | Called only after confirmation |
| `ProductValidator::validateProductInput()` | Check the product form | `array $formInput`, `int[] $validCategoryIds` | `array` field => message | Empty array means valid |
| `ProductValidator::validateSearchInput()` | Check search criteria | `array $queryInput` | `array` field => message | Rejects bad numbers and reversed price ranges |
| `Product::getProductDetails()` | All attributes in one array | none | `array` | Used for details, confirmation and before/after |
| `Product::checkStock()` | Is enough stock available? | `int $requestedQuantity = 1` | `bool` | Will be reused by cart and checkout |

Table 4: Main method documentation

## 5. Security and robustness

- **SQL injection:** every query uses PDO prepared statements with bound parameters.
- **XSS:** every value printed in a view goes through `escapeHtml()`.
- **CSRF:** create, update and delete forms carry a session token checked with `hash_equals()`.
- **Double submit:** Post/Redirect/Get, so refreshing after saving does not save twice.
- **Wrong HTTP method:** the router only accepts `POST` for store/update/destroy.
- **Passwords:** kept in `config_local.php`, which is ignored by Git.
