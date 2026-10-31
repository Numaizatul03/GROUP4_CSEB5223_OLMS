<?php

declare(strict_types=1);

/**
 * Class Name:  Product
 * Purpose:     Represent one product sold in the Online Local Mart System.
 * Description: Attributes and methods follow the Product class in the class
 *              diagram (Assignment Part 1, Section 3.4). The class only holds
 *              data and product rules; saving to the database is done by
 *              ProductService so each class keeps one responsibility.
 */
class Product
{
    public const LOW_STOCK_THRESHOLD = 5;

    private ?int $productId = null;
    private int $categoryId = 0;
    private string $categoryName = '';
    private string $productName = '';
    private string $description = '';
    private float $price = 0.0;
    private int $stockQuantity = 0;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private ?string $deletedAt = null;

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build a Product object from one row of the products table.
     * Parameters:   array $productRow - associative row (snake_case column names)
     * Return Value: Product - object with every attribute filled in
     * Description:  Converts database column names into object attributes and casts
     *               each value to its proper data type (int, float, string).
     */
    public static function fromDatabaseRow(array $productRow): Product
    {
        $product = new Product();
        $product->setProductId((int) $productRow['product_id']);
        $product->setCategoryId((int) $productRow['category_id']);
        $product->setCategoryName((string) ($productRow['category_name'] ?? ''));
        $product->setProductName((string) $productRow['product_name']);
        $product->setDescription((string) $productRow['description']);
        $product->setPrice((float) $productRow['price']);
        $product->setStockQuantity((int) $productRow['stock_quantity']);
        $product->createdAt = $productRow['created_at'] ?? null;
        $product->updatedAt = $productRow['updated_at'] ?? null;
        $product->deletedAt = $productRow['deleted_at'] ?? null;

        return $product;
    }

    /**
     * Function/Method Name: getProductDetails
     * Purpose:      Return all product attributes in one array.
     * Parameters:   none
     * Return Value: array - attribute name => value, in display order
     * Description:  Used by the views to print a full record (view, delete
     *               confirmation, before/after comparison after editing).
     */
    public function getProductDetails(): array
    {
        return [
            'productId' => $this->productId,
            'productName' => $this->productName,
            'categoryName' => $this->categoryName,
            'description' => $this->description,
            'price' => $this->price,
            'stockQuantity' => $this->stockQuantity,
            'stockStatus' => $this->getStockStatus(),
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /**
     * Function/Method Name: updateProduct
     * Purpose:      Change the editable attributes of this product.
     * Parameters:   array $productData - validated data from ProductValidator
     *               (categoryId, productName, description, price, stockQuantity)
     * Return Value: void
     * Description:  Applies new values through the setters so every change goes
     *               through the same type rules.
     */
    public function updateProduct(array $productData): void
    {
        $this->setCategoryId($productData['categoryId']);
        $this->setProductName($productData['productName']);
        $this->setDescription($productData['description']);
        $this->setPrice($productData['price']);
        $this->setStockQuantity($productData['stockQuantity']);
    }

    /**
     * Function/Method Name: checkStock
     * Purpose:      Check whether enough stock is available.
     * Parameters:   int $requestedQuantity - quantity wanted (default 1)
     * Return Value: bool - true when the stock covers the requested quantity
     * Description:  Will be reused by the shopping cart and checkout modules to
     *               stop customers from ordering more than what is in stock.
     */
    public function checkStock(int $requestedQuantity = 1): bool
    {
        return $this->stockQuantity >= $requestedQuantity;
    }

    /**
     * Function/Method Name: getStockStatus
     * Purpose:      Describe the stock level in words.
     * Parameters:   none
     * Return Value: string - "Out of stock", "Low stock" or "In stock"
     * Description:  Uses LOW_STOCK_THRESHOLD so the limit is defined in one place.
     */
    public function getStockStatus(): string
    {
        if (!$this->checkStock()) {
            return 'Out of stock';
        }

        return $this->stockQuantity <= self::LOW_STOCK_THRESHOLD ? 'Low stock' : 'In stock';
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): void
    {
        $this->categoryName = $categoryName;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = trim($productName);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = trim($description);
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = round($price, 2);
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    /**
     * Function/Method Name: isDeleted
     * Purpose:      Tell whether the product is in Delete History.
     * Parameters:   none
     * Return Value: bool - true when the product was deleted and can still be restored
     * Description:  Deleted products are hidden from the shop, the product list and checkout.
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
