<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/product.php';

/**
 * Class Name:  ProductService
 * Purpose:     Create, read, update, delete and search products in the database.
 * Description: All SQL for the products table is kept in this class. Every
 *              query uses prepared statements with bound parameters so user
 *              input can never change the SQL (prevents SQL injection).
 */
class ProductService
{
    private const SELECT_PRODUCTS_SQL = 'SELECT p.product_id, p.category_id, c.category_name, p.product_name,
                p.description, p.price, p.stock_quantity, p.created_at, p.updated_at
            FROM products p
            INNER JOIN categories c ON c.category_id = p.category_id';

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
        $statement = $this->connection->query(self::SELECT_PRODUCTS_SQL . ' ORDER BY p.product_id');

        return $this->mapRowsToProducts($statement->fetchAll());
    }

    /**
     * Function/Method Name: findProductById
     * Purpose:      Fetch one product by its ID.
     * Parameters:   int $productId - the product to look for
     * Return Value: ?Product - the product, or null when it does not exist
     * Description:  Used by view, edit and delete to load the target product.
     */
    public function findProductById(int $productId): ?Product
    {
        $statement = $this->connection->prepare(self::SELECT_PRODUCTS_SQL . ' WHERE p.product_id = :productId');
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
        $conditions = [];
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

        $searchSql = self::SELECT_PRODUCTS_SQL;
        if ($conditions !== []) {
            $searchSql .= ' WHERE ' . implode(' AND ', $conditions);
        }

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
             WHERE product_id = :productId'
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
     * Purpose:      Permanently remove a product.
     * Parameters:   int $productId - the product to delete
     * Return Value: bool - true when a row was deleted, false when the ID did not exist
     * Description:  Called only after the user has confirmed the deletion.
     */
    public function deleteProduct(int $productId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM products WHERE product_id = :productId');
        $statement->execute(['productId' => $productId]);

        return $statement->rowCount() > 0;
    }

    /**
     * Function/Method Name: countProducts
     * Purpose:      Count all products.
     * Parameters:   none
     * Return Value: int - number of rows in the products table
     * Description:  Shown on the delete result so the user can see the total drop by one.
     */
    public function countProducts(): int
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM products')->fetchColumn();
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
