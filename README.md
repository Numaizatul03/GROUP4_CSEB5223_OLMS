# GROUP4_CSEB5223_OLMS
Online Local Mart System - CSEB5223 Software Construction &amp; Methods
## 1. Construction Environment

### 1.1 System Type

The proposed system is a **web-based online shopping system**.

### 1.2 Proposed Technologies

| Component | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Version Control | GitHub |
| Development Environment | Web |

---

## 2. Use Case

```mermaid
flowchart LR

    Customer["Customer"]
    Admin["Administrator"]

    subgraph System["Online Local Mart System"]
        direction TB

        C1(["Register / Login"])
        C2(["Browse Products"])
        C3(["Search Products"])
        C4(["View Product Details"])
        C5(["Manage Shopping Cart"])
        C6(["Checkout"])
        C7(["Make Payment"])
        C8(["View Order History"])

        A1(["Login"])
        A2(["Manage Products"])
        A3(["Manage Categories"])
        A4(["Manage Inventory"])
        A5(["Manage Customers"])
        A6(["Manage Orders"])
    end

    Customer --- C1
    Customer --- C2
    Customer --- C3
    Customer --- C4
    Customer --- C5
    Customer --- C6
    Customer --- C7
    Customer --- C8

    Admin --- A1
    Admin --- A2
    Admin --- A3
    Admin --- A4
    Admin --- A5
    Admin --- A6
```

## 3. Class Diagram

```mermaid
classDiagram

    class User {
        +userId
        +name
        +email
        +password
        +login()
        +logout()
        +updateProfile()
    }

    class Customer {
        +shippingAddress
        +phoneNumber
        +browseProducts()
        +searchProducts()
        +addToCart()
        +checkout()
        +viewOrders()
    }

    class Administrator {
        +manageProducts()
        +manageCategories()
        +manageInventory()
        +manageCustomers()
        +manageOrders()
    }

    class Product {
        +productId
        +productName
        +description
        +price
        +stockQuantity
        +getProductDetails()
        +updateProduct()
        +checkStock()
    }

    class Category {
        +categoryId
        +categoryName
        +description
        +addProduct()
        +removeProduct()
    }

    class ShoppingCart {
        +cartId
        +totalAmount
        +addItem()
        +removeItem()
        +updateQuantity()
        +calculateTotal()
        +clearCart()
    }

    class CartItem {
        +cartItemId
        +quantity
        +subtotal
        +calculateSubtotal()
        +updateQuantity()
    }

    class Order {
        +orderId
        +orderDate
        +totalAmount
        +orderStatus
        +createOrder()
        +calculateTotal()
        +updateStatus()
        +cancelOrder()
    }

    class OrderItem {
        +orderItemId
        +quantity
        +price
        +subtotal
        +calculateSubtotal()
    }

    class Payment {
        +paymentId
        +paymentMethod
        +paymentStatus
        +paymentDate
        +processPayment()
        +verifyPayment()
        +refundPayment()
    }

    User <|-- Customer
    User <|-- Administrator

    Customer "1" *-- "1" ShoppingCart
    ShoppingCart "1" *-- "0..*" CartItem
    CartItem "*" --> "1" Product

    Category "1" --> "0..*" Product

    Customer "1" --> "0..*" Order
    Order "1" *-- "1..*" OrderItem
    OrderItem "*" --> "1" Product

    Order "1" --> "0..1" Payment
```
