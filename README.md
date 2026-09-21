# GROUP4_CSEB5223_OLMS
Online Shopping Management System - CSEB5223 Software Construction &amp; Methods

# 4. Documentation Format

The Online Shopping Management System shall follow a consistent documentation format to ensure clarity, readability, and consistency throughout the project.

## 4.1 Documentation Standards

- Use clear and descriptive headings and subheadings.
- Use numbered sections for the project documentation.
- Use consistent formatting throughout the documentation.
- Tables and diagrams should include appropriate titles or captions.
- Important classes, methods, and functions should be documented clearly.
- Screenshots should include a short description when necessary.
- The GitHub README shall use Markdown format.

## 4.2 Function and Method Documentation

Important functions and methods shall follow the documentation format below:

| Item | Description |
|---|---|
| Function/Method Name | Name of the function or method |
| Purpose | Main purpose of the function or method |
| Parameters | Input values required by the function |
| Return Value | Value returned by the function |
| Description | Brief explanation of the function or method |

## 4.3 Diagram Documentation

System diagrams shall include:

- Figure number
- Figure title
- Diagram
- Brief description of the diagram

# 5. Coding and File Format

The Online Shopping Management System shall follow consistent coding and file organization standards to improve readability, maintainability, and organization.

## 5.1 Coding Standards

- Use consistent indentation throughout the source code.
- Use meaningful names for classes, variables, functions, and files.
- Keep functions focused on one main task.
- Avoid unnecessary duplicate code.
- Use comments to explain important or complex logic.
- Remove unused code before committing.
- Keep related files organized in appropriate folders.
- Test the code before pushing changes to GitHub.

## 5.2 File and Folder Structure

The project files shall be organized according to their purpose.

```text
Online-Shopping-Management-System/
├── src/
│   ├── models/
│   ├── controllers/
│   ├── views/
│   └── services/
├── tests/
├── docs/
├── README.md
└── .gitignore


---

## Then Part 6 — Naming Convention Dictionary

```markdown
# 6. Naming Convention Dictionary

The following naming conventions shall be used consistently throughout the Online Shopping Management System.

| Element | Naming Convention | Example |
|---|---|---|
| Class | PascalCase | `Product` |
| Variable | camelCase | `productName` |
| Function/Method | camelCase | `searchProduct()` |
| Constant | UPPER_SNAKE_CASE | `MAX_QUANTITY` |
| File | snake_case | `product_controller.php` |
| Folder | lowercase | `controllers` |
| Database Table | snake_case | `order_items` |
| Database Column | snake_case | `product_name` |
| Boolean Variable | is/has/can + camelCase | `isAvailable` |
| ID Variable | camelCase + Id | `productId` |

## 6.1 Naming Examples

### Classes

```text
User
Customer
Administrator
Product
Category
ShoppingCart
CartItem
Order
OrderItem
Payment

### Functions / Methods
registerCustomer()
loginCustomer()
searchProduct()
viewProductDetails()
addToCart()
updateCart()
checkout()
makePayment()
viewOrderHistory()

### Variables
customerName
productName
productPrice
quantity
orderDate
paymentStatus

### Database Tables
users
customers
products
categories
shopping_carts
cart_items
orders
order_items
payments
