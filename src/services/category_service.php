<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/category.php';

/**
 * Class Name:  CategoryService
 * Purpose:     Read product categories from the database.
 * Description: The Product module needs the category list for its dropdowns
 *              and to check that a chosen category really exists.
 */
class CategoryService
{
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
     * Description:  Used to fill the category dropdown on product forms.
     */
    public function getAllCategories(): array
    {
        $statement = $this->connection->query(
            'SELECT category_id, category_name, description FROM categories ORDER BY category_name'
        );

        return array_map(static fn (array $categoryRow) => Category::fromDatabaseRow($categoryRow), $statement->fetchAll());
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
}
