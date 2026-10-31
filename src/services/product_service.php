<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/product.php';
require_once __DIR__ . '/product_validator.php';
require_once __DIR__ . '/business_rule_exception.php';

/**
 * Class Name:  ProductService
 * Purpose:     Create, read, update, delete and search products in the database.
 * Description: All SQL for the products table is kept in this class. Every
 *              query uses prepared statements with bound parameters so user
 *              input can never change the SQL (prevents SQL injection).
 *              Deleting is a "soft delete": the row stays with deleted_at set,
 *              so the product keeps its ID and can be restored from Delete History.
 *              Every normal read only sees products that are not deleted.
 */
class ProductService
{
    private const SELECT_PRODUCTS_SQL = 'SELECT p.product_id, p.category_id, c.category_name, p.product_name,
                p.description, p.price, p.stock_quantity, p.created_at, p.updated_at, p.deleted_at
            FROM products p
            INNER JOIN categories c ON c.category_id = p.category_id';
    private const NOT_DELETED_SQL = 'p.deleted_at IS NULL';

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: getAllProducts
     * Purpose:      Fetch every product for the data display page.
     * Parameters:   none
     * Return Value: Product[] - all products ordered by product ID
     * Description:  Joins categories so each product also carries its category name.
     */
    public function getAllProducts(): array
    {
        $statement = $this->connection->query(self::SELECT_PRODUCTS_SQL . ' WHERE ' . self::NOT_DELETED_SQL . ' ORDER BY p.product_id');

        return $this->mapRowsToProducts($statement->fetchAll());
    }

    /**
     * Function/Method Name: findProductById
     * Purpose:      Fetch one product by its ID.
     * Parameters:   int $productId - the product to look for
     * Return Value: ?Product - the product, or null when it does not exist or is deleted
     * Description:  Used by view, edit and delete to load the target product.
     */
    public function findProductById(int $productId): ?Product
    {
        $statement = $this->connection->prepare(
            self::SELECT_PRODUCTS_SQL . ' WHERE p.product_id = :productId AND ' . self::NOT_DELETED_SQL
        );
        $statement->execute(['productId' => $productId]);
        $productRow = $statement->fetch();

        return $productRow === false ? null : Product::fromDatabaseRow($productRow);
    }

    /**
     * Function/Method Name: searchProduct
     * Purpose:      Find products that match the search criteria.
     * Parameters:   array $searchCriteria - any of: productId (int), keyword (string),
     *               categoryId (int), minPrice (float), maxPrice (float); null = not used
     * Return Value: Product[] - matching products (empty array when nothing matches)
     * Description:  Builds the WHERE clause only from the criteria that were given,
     *               so the same method serves the Search, Edit and Delete pages.
     *               The keyword is matched against product name and description.
     */
    public function searchProduct(array $searchCriteria): array
    {
        $conditions = [self::NOT_DELETED_SQL];
        $parameters = [];

        if (($searchCriteria['productId'] ?? null) !== null) {
            $conditions[] = 'p.product_id = :productId';
            $parameters['productId'] = $searchCriteria['productId'];
        }

        if (($searchCriteria['keyword'] ?? '') !== '') {
            $conditions[] = '(p.product_name LIKE :keywordName OR p.description LIKE :keywordDescription)';
            $parameters['keywordName'] = '%' . $searchCriteria['keyword'] . '%';
            $parameters['keywordDescription'] = '%' . $searchCriteria['keyword'] . '%';
        }

        if (($searchCriteria['categoryId'] ?? null) !== null) {
            $conditions[] = 'p.category_id = :categoryId';
            $parameters['categoryId'] = $searchCriteria['categoryId'];
        }

        if (($searchCriteria['minPrice'] ?? null) !== null) {
            $conditions[] = 'p.price >= :minPrice';
            $parameters['minPrice'] = $searchCriteria['minPrice'];
        }

        if (($searchCriteria['maxPrice'] ?? null) !== null) {
            $conditions[] = 'p.price <= :maxPrice';
            $parameters['maxPrice'] = $searchCriteria['maxPrice'];
        }

        $searchSql = self::SELECT_PRODUCTS_SQL . ' WHERE ' . implode(' AND ', $conditions);

        $statement = $this->connection->prepare($searchSql . ' ORDER BY p.product_name');
        $statement->execute($parameters);

        return $this->mapRowsToProducts($statement->fetchAll());
    }

    /**
     * Function/Method Name: createProduct
     * Purpose:      Insert a new product.
     * Parameters:   array $productData - validated data (categoryId, productName,
     *               description, price, stockQuantity)
     * Return Value: int - ID of the newly created product
     * Description:  Data must be validated by ProductValidator before calling this.
     *               Timestamps are set in PHP so they use APP_TIMEZONE on any database.
     */
    public function createProduct(array $productData): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO products (category_id, product_name, description, price, stock_quantity, created_at, updated_at)
             VALUES (:categoryId, :productName, :description, :price, :stockQuantity, :createdAt, :updatedAt)'
        );
        $currentTime = date('Y-m-d H:i:s');
        $statement->execute([
            'categoryId' => $productData['categoryId'],
            'productName' => $productData['productName'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'stockQuantity' => $productData['stockQuantity'],
            'createdAt' => $currentTime,
            'updatedAt' => $currentTime,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Function/Method Name: updateProduct
     * Purpose:      Save the changed attributes of an existing product.
     * Parameters:   Product $product - product object that already holds the new values
     * Return Value: bool - true when the product row was found and saved
     * Description:  Also refreshes updated_at so the change time can be shown to the user.
     */
    public function updateProduct(Product $product): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE products
             SET category_id = :categoryId, product_name = :productName, description = :description,
                 price = :price, stock_quantity = :stockQuantity, updated_at = :updatedAt
             WHERE product_id = :productId AND deleted_at IS NULL'
        );
        $statement->execute([
            'categoryId' => $product->getCategoryId(),
            'productName' => $product->getProductName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'stockQuantity' => $product->getStockQuantity(),
            'updatedAt' => date('Y-m-d H:i:s'),
            'productId' => $product->getProductId(),
        ]);

        return $this->findProductById((int) $product->getProductId()) !== null;
    }

    /**
     * Function/Method Name: deleteProduct
     * Purpose:      Delete a product by moving it to Delete History (soft delete).
     * Parameters:   int $productId - the product to delete
     * Return Value: bool - true when the product was deleted, false when it did not
     *               exist or was already deleted
     * Description:  Called only after the user has confirmed the deletion. The product
     *               disappears from the shop, lists and searches, and is taken out of
     *               every shopping cart, but it keeps its ID so it can be restored.
     */
    public function deleteProduct(int $productId): bool
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                'UPDATE products SET deleted_at = :deletedAt WHERE product_id = :productId AND deleted_at IS NULL'
            );
            $statement->execute(['deletedAt' => date('Y-m-d H:i:s'), 'productId' => $productId]);
            $isDeleted = $statement->rowCount() > 0;

            if ($isDeleted) {
                $cartStatement = $this->connection->prepare('DELETE FROM cart_items WHERE product_id = :productId');
                $cartStatement->execute(['productId' => $productId]);
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $isDeleted;
    }

    /**
     * Function/Method Name: getDeletedProducts
     * Purpose:      List the products in Delete History.
     * Parameters:   none
     * Return Value: Product[] - deleted products, most recently deleted first
     * Description:  Shown on the Delete History page with Restore buttons.
     */
    public function getDeletedProducts(): array
    {
        $statement = $this->connection->query(
            self::SELECT_PRODUCTS_SQL . ' WHERE p.deleted_at IS NOT NULL ORDER BY p.deleted_at DESC, p.product_id DESC'
        );

        return $this->mapRowsToProducts($statement->fetchAll());
    }

    /**
     * Function/Method Name: findDeletedProductById
     * Purpose:      Fetch one product from Delete History.
     * Parameters:   int $productId
     * Return Value: ?Product - the deleted product, or null when it is not in Delete History
     * Description:  Used by restore and permanent delete.
     */
    public function findDeletedProductById(int $productId): ?Product
    {
        $statement = $this->connection->prepare(
            self::SELECT_PRODUCTS_SQL . ' WHERE p.product_id = :productId AND p.deleted_at IS NOT NULL'
        );
        $statement->execute(['productId' => $productId]);
        $productRow = $statement->fetch();

        return $productRow === false ? null : Product::fromDatabaseRow($productRow);
    }

    /**
     * Function/Method Name: restoreProduct
     * Purpose:      Bring a product back from Delete History.
     * Parameters:   int $productId
     * Return Value: bool - true when the product was restored
     * Description:  The product returns with the same ID, stock and details it had
     *               before it was deleted.
     */
    public function restoreProduct(int $productId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE products SET deleted_at = NULL, updated_at = :updatedAt WHERE product_id = :productId AND deleted_at IS NOT NULL'
        );
        $statement->execute(['updatedAt' => date('Y-m-d H:i:s'), 'productId' => $productId]);

        return $statement->rowCount() > 0;
    }

    /**
     * Function/Method Name: permanentlyDeleteProduct
     * Purpose:      Remove a product from Delete History for good.
     * Parameters:   int $productId
     * Return Value: bool - true when the row was removed
     * Description:  Only products already in Delete History can be removed, so an
     *               active product can never skip the history. Past orders keep their
     *               copied product name and price.
     */
    public function permanentlyDeleteProduct(int $productId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM products WHERE product_id = :productId AND deleted_at IS NOT NULL');
        $statement->execute(['productId' => $productId]);

        return $statement->rowCount() > 0;
    }

    /**
     * Function/Method Name: adjustStock
     * Purpose:      Add or remove stock for one product (Manage Inventory).
     * Parameters:   int $productId, int $quantityChange - positive to restock, negative to write off
     * Return Value: int - the new stock quantity
     * Description:  Refuses a change that would make stock negative or exceed the
     *               maximum, and refuses unknown products.
     */
    public function adjustStock(int $productId, int $quantityChange): int
    {
        $product = $this->findProductById($productId);
        if ($product === null) {
            throw new BusinessRuleException('Product #' . $productId . ' was not found.');
        }

        $newStockQuantity = $product->getStockQuantity() + $quantityChange;
        if ($newStockQuantity < 0) {
            throw new BusinessRuleException(
                'Cannot remove ' . abs($quantityChange) . ' unit(s): only ' . $product->getStockQuantity() . ' in stock.'
            );
        }

        if ($newStockQuantity > ProductValidator::MAX_STOCK_QUANTITY) {
            throw new BusinessRuleException('Stock cannot be more than ' . number_format(ProductValidator::MAX_STOCK_QUANTITY) . ' units.');
        }

        $statement = $this->connection->prepare(
            'UPDATE products SET stock_quantity = :stockQuantity, updated_at = :updatedAt WHERE product_id = :productId'
        );
        $statement->execute(['stockQuantity' => $newStockQuantity, 'updatedAt' => date('Y-m-d H:i:s'), 'productId' => $productId]);

        return $newStockQuantity;
    }

    /**
     * Function/Method Name: getProductsByStockLevel
     * Purpose:      List products with the lowest stock first (Manage Inventory page).
     * Parameters:   none
     * Return Value: Product[] - ordered by stock quantity, then name
     * Description:  Puts the products that need restocking at the top.
     */
    public function getProductsByStockLevel(): array
    {
        $statement = $this->connection->query(
            self::SELECT_PRODUCTS_SQL . ' WHERE ' . self::NOT_DELETED_SQL . ' ORDER BY p.stock_quantity, p.product_name'
        );

        return $this->mapRowsToProducts($statement->fetchAll());
    }

    public function countLowStockProducts(): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM products WHERE stock_quantity <= :threshold AND deleted_at IS NULL'
        );
        $statement->execute(['threshold' => Product::LOW_STOCK_THRESHOLD]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Function/Method Name: countProducts
     * Purpose:      Count all products.
     * Parameters:   none
     * Return Value: int - number of products that are not deleted
     * Description:  Shown on the delete result so the user can see the total drop by one.
     */
    public function countProducts(): int
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM products WHERE deleted_at IS NULL')->fetchColumn();
    }

    /**
     * Function/Method Name: mapRowsToProducts
     * Purpose:      Turn database rows into Product objects.
     * Parameters:   array $productRows - rows returned by PDO
     * Return Value: Product[] - one object per row
     * Description:  Shared by every read method to avoid duplicated mapping code.
     */
    private function mapRowsToProducts(array $productRows): array
    {
        return array_map(static fn (array $productRow) => Product::fromDatabaseRow($productRow), $productRows);
    }
}
