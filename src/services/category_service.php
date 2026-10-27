<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/category.php';
require_once __DIR__ . '/business_rule_exception.php';

/**
 * Class Name:  CategoryService
 * Purpose:     Create, read, update, delete and search product categories.
 * Description: All SQL for the categories table is kept here. Each category is
 *              read together with the number of products that use it.
 */
class CategoryService
{
    private const SELECT_CATEGORIES_SQL = 'SELECT c.category_id, c.category_name, c.description,
                COUNT(p.product_id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.category_id';
    private const GROUP_CATEGORIES_SQL = ' GROUP BY c.category_id, c.category_name, c.description';

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: getAllCategories
     * Purpose:      Fetch every category.
     * Parameters:   none
     * Return Value: Category[] - all categories ordered by name
     * Description:  Used for the category dropdowns, the shop filter and Manage Categories.
     */
    public function getAllCategories(): array
    {
        return $this->searchCategories([]);
    }

    /**
     * Function/Method Name: getCategoryIds
     * Purpose:      List the IDs of all existing categories.
     * Parameters:   none
     * Return Value: int[] - category IDs
     * Description:  Passed to ProductValidator so a product cannot use a missing category.
     */
    public function getCategoryIds(): array
    {
        return array_map(static fn (Category $category) => $category->getCategoryId(), $this->getAllCategories());
    }

    /**
     * Function/Method Name: findCategoryById
     * Purpose:      Fetch one category by ID.
     * Parameters:   int $categoryId
     * Return Value: ?Category - the category, or null when it does not exist
     * Description:  Used by edit and delete to load the target category.
     */
    public function findCategoryById(int $categoryId): ?Category
    {
        $categories = $this->searchCategories(['categoryId' => $categoryId]);

        return $categories[0] ?? null;
    }

    /**
     * Function/Method Name: searchCategories
     * Purpose:      Find categories that match the search criteria.
     * Parameters:   array $searchCriteria - categoryId (int) and/or keyword (string); null/'' = not used
     * Return Value: Category[] - matching categories (empty array when nothing matches)
     * Description:  The keyword is matched against the name and description.
     */
    public function searchCategories(array $searchCriteria): array
    {
        $conditions = [];
        $parameters = [];

        if (($searchCriteria['categoryId'] ?? null) !== null) {
            $conditions[] = 'c.category_id = :categoryId';
            $parameters['categoryId'] = $searchCriteria['categoryId'];
        }

        if (($searchCriteria['keyword'] ?? '') !== '') {
            $conditions[] = '(c.category_name LIKE :keywordName OR c.description LIKE :keywordDescription)';
            $parameters['keywordName'] = '%' . $searchCriteria['keyword'] . '%';
            $parameters['keywordDescription'] = '%' . $searchCriteria['keyword'] . '%';
        }

        $searchSql = self::SELECT_CATEGORIES_SQL;
        if ($conditions !== []) {
            $searchSql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $statement = $this->connection->prepare($searchSql . self::GROUP_CATEGORIES_SQL . ' ORDER BY c.category_name');
        $statement->execute($parameters);

        return array_map(static fn (array $categoryRow) => Category::fromDatabaseRow($categoryRow), $statement->fetchAll());
    }

    /**
     * Function/Method Name: isCategoryNameTaken
     * Purpose:      Check whether another category already has this name.
     * Parameters:   string $categoryName, ?int $exceptCategoryId - the category's own ID when editing
     * Return Value: bool - true when a different category uses the name (ignoring case)
     * Description:  Category names must be unique so the shop filter is not confusing.
     */
    public function isCategoryNameTaken(string $categoryName, ?int $exceptCategoryId = null): bool
    {
        $statement = $this->connection->prepare(
            'SELECT category_id FROM categories WHERE LOWER(category_name) = :categoryName'
        );
        $statement->execute(['categoryName' => strtolower(trim($categoryName))]);
        $existingCategoryId = $statement->fetchColumn();

        return $existingCategoryId !== false && (int) $existingCategoryId !== $exceptCategoryId;
    }

    /**
     * Function/Method Name: createCategory
     * Purpose:      Insert a new category.
     * Parameters:   array $categoryData - categoryName, description (validated)
     * Return Value: int - ID of the new category
     * Description:  Data must be validated by CategoryValidator first.
     */
    public function createCategory(array $categoryData): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO categories (category_name, description) VALUES (:categoryName, :description)'
        );
        $statement->execute([
            'categoryName' => $categoryData['categoryName'],
            'description' => $categoryData['description'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Function/Method Name: updateCategory
     * Purpose:      Save changes to an existing category.
     * Parameters:   Category $category - category object holding the new values
     * Return Value: bool - true when the category still exists after saving
     * Description:  Products keep pointing at the same category ID, so they follow the rename.
     */
    public function updateCategory(Category $category): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE categories SET category_name = :categoryName, description = :description WHERE category_id = :categoryId'
        );
        $statement->execute([
            'categoryName' => $category->getCategoryName(),
            'description' => $category->getDescription(),
            'categoryId' => $category->getCategoryId(),
        ]);

        return $this->findCategoryById($category->getCategoryId()) !== null;
    }

    /**
     * Function/Method Name: deleteCategory
     * Purpose:      Permanently remove an empty category.
     * Parameters:   int $categoryId
     * Return Value: bool - true when a row was deleted
     * Description:  Refused while products still use the category; the user is told
     *               to move or delete those products first.
     */
    public function deleteCategory(int $categoryId): bool
    {
        $category = $this->findCategoryById($categoryId);
        if ($category !== null && $category->hasProducts()) {
            throw new BusinessRuleException(
                'Category "' . $category->getCategoryName() . '" still has ' . $category->getProductCount()
                . ' product(s). Move or delete those products first.'
            );
        }

        $statement = $this->connection->prepare('DELETE FROM categories WHERE category_id = :categoryId');
        $statement->execute(['categoryId' => $categoryId]);

        return $statement->rowCount() > 0;
    }

    public function countCategories(): int
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    }
}
