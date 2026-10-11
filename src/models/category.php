<?php

declare(strict_types=1);

/**
 * Class Name:  Category
 * Purpose:     Represent a product category (Assignment Part 1, Section 3.5).
 * Description: Used by the Product module to group products. Full Category
 *              CRUD (addProduct(), removeProduct()) belongs to the Category
 *              module and will be added in a later lab.
 */
class Category
{
    private int $categoryId = 0;
    private string $categoryName = '';
    private string $description = '';

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build a Category object from one row of the categories table.
     * Parameters:   array $categoryRow - associative row (snake_case column names)
     * Return Value: Category - object with every attribute filled in
     * Description:  Casts the database values to the attribute data types.
     */
    public static function fromDatabaseRow(array $categoryRow): Category
    {
        $category = new Category();
        $category->setCategoryId((int) $categoryRow['category_id']);
        $category->setCategoryName((string) $categoryRow['category_name']);
        $category->setDescription((string) $categoryRow['description']);

        return $category;
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
        $this->categoryName = trim($categoryName);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = trim($description);
    }
}
