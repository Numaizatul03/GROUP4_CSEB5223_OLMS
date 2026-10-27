# Online Local Mart System (OLMS) — System Guide (Lab 5 & 6)

CSEB5223 Software Construction & Methods, Semester 1 2026/27, Section 01A.

This guide describes the complete, integrated system: every class from the class
diagram (Assignment Part 1, Section 3), the pages that use it, and how to run and
test it. The Lab 3 & 4 Product module is described in [product_module.md](product_module.md).

## 1. How to run

1. Start MySQL (an administrator terminal: `net start MySQL80`).
2. Copy `src/config/config_local.example.php` to `src/config/config_local.php` and enter your MySQL password
   (this file is in `.gitignore`, so it is never pushed).
3. Create the tables and sample data — **this replaces any existing OLMS data**:
   ```
   php src/database/setup_database.php
   ```
4. Start the site and open http://localhost:8000:
   ```
   php -S localhost:8000
   ```
5. Run the automated tests (they use a temporary in-memory database):
   ```
   php tests/run_tests.php
   ```

### Test logins (sample data, development only)

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@olms.test | Admin@123 |
| Customer | siti@olms.test | Customer@123 |
| Customer | daniel@olms.test | Customer@123 |
| Customer | kavitha@olms.test | Customer@123 |

Visitors can browse the shop without logging in, and can register a new customer account.

## 2. Modules and classes

| Module | Classes (class diagram) | Model files | Service files | Controller |
|--------|-------------------------|-------------|---------------|------------|
| Accounts | User, Customer, Administrator | `user.php`, `customer.php`, `administrator.php` | `user_service.php`, `user_validator.php`, `auth_service.php` | `AuthController` |
| Products | Product | `product.php` | `product_service.php`, `product_validator.php` | `ProductController`, `ShopController` |
| Inventory | Product (stock) | `product.php` | `product_service.php` | `InventoryController` |
| Categories | Category | `category.php` | `category_service.php`, `category_validator.php` | `CategoryController` |
| Customers | Customer | `customer.php` | `user_service.php`, `user_validator.php` | `CustomerController` |
| Shopping cart | ShoppingCart, CartItem | `shopping_cart.php`, `cart_item.php` | `cart_service.php` | `CartController` |
| Orders | Order, OrderItem | `order.php`, `order_item.php` | `order_service.php`, `order_validator.php` | `OrderController`, `AdminOrderController` |
| Payments | Payment | `payment.php` | `payment_service.php` | `PaymentController` |
| Dashboard | (all) | — | all services | `DashboardController` |

Table 1: Modules, classes and files

## 3. CRUD coverage

| Class / module | Create | Read / search | Update | Delete |
|----------------|--------|---------------|--------|--------|
| Customer (User) | Register; administrator "Add Customer" | Profile; Manage Customers search by ID / name / email / phone | Profile update (before/after); administrator edit (before/after); change password | Administrator delete (confirm; refused when the customer has orders) |
| Administrator (User) | Sample data only (no self-registration) | Log in; profile | Profile update | — (protected) |
| Product | Add Product (Lab 3 & 4) | Shop browse / search / category filter; admin search | Edit (before/after); inventory restock / write-off | Delete (confirm, result) |
| Category | Add Category | Manage Categories search by ID / keyword | Edit (before/after) | Delete (confirm; refused while products use it) |
| ShoppingCart / CartItem | Add to cart | View cart | Change quantity | Remove item; clear cart |
| Order / OrderItem | Checkout (one transaction) | My Orders; administrator search by ID / customer / status / date range | Status update (before/after); customer cancel | Administrator delete (Delivered / Cancelled only) |
| Payment | Make Payment | Order details; Payments page search by order / method / status | Verify (cash received); refund | Never deleted — financial records are kept |

Table 2: Create, read, update and delete for every class

## 4. Business rules

- **Stock:** the cart never holds more than the stock; checkout takes stock with
  `UPDATE ... WHERE stock_quantity >= :quantity`, so two customers cannot buy the last unit.
  Cancelling returns the stock.
- **Checkout transaction:** order, order items, stock and cart are changed together or not at all.
- **Order status flow:** Pending Payment → Paid / Processing → Shipped → Delivered, or Cancelled
  before shipping. Other changes (e.g. Delivered → Paid) are refused.
- **Payments (simulated):** Online Banking and E-Wallet complete at once (order becomes Paid);
  Cash on Delivery stays Pending until verified or delivered. No card or bank details are ever asked for.
  The amount always comes from the saved order, never from the form.
- **Refunds:** cancelling a paid order refunds the payment; a payment cannot be refunded twice.
- **Deletes are protected:** categories with products, customers with orders and active orders cannot be deleted.

## 5. Access control

Every route in `src/config/routes.php` has an access level, checked in `index.php` before the controller runs:

| Level | Who | Examples |
|-------|-----|----------|
| public | everyone | shop, product details |
| guest | not logged in | login, register |
| user | any logged-in user | profile, logout |
| customer | customers only | cart, checkout, my orders, payment |
| administrator | administrators only | dashboard, products, inventory, categories, customers, orders, payments |

A visitor opening a protected page is sent to the login page and returned afterwards;
a logged-in user opening the other role's page gets HTTP 403. Customers can only open their own orders.

## 6. Security

| Risk | Protection |
|------|------------|
| SQL injection | PDO prepared statements everywhere |
| Cross-site scripting | `escapeHtml()` on every printed value |
| Cross-site request forgery | CSRF token on every POST form, checked with `hash_equals()` |
| Password theft | bcrypt hashes (`password_hash` / `password_verify`); plain passwords never stored |
| Session fixation | `session_regenerate_id()` on login and logout |
| Account probing | one message for unknown email and wrong password |
| Open redirect after login | only relative `index.php?...` return links are accepted |

## 7. Tests

`php tests/run_tests.php` runs 31 tests in 5 suites: ProductService, ProductValidator,
UserService (registration, login, profile, customer management), CategoryService, and
Cart, Order and Payment (cart rules, checkout rollback, payment methods, status flow,
cancellation restock, refunds, inventory).
