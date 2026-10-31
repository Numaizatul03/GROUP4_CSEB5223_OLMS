<?php

declare(strict_types=1);

/**
 * Class Name:  Category
 * Purpose:     Represent a product category (Assignment Part 1, Section 3.5).
 * Description: Groups products. Administrators create, search, edit and
 *              delete categories through CategoryService. addProduct() and
 *              removeProduct() from the class diagram are done by choosing the
 *              category on the product form; productCount shows how many
 *              products a category currently holds.
 */
class Category
{
    private int $categoryId = 0;
    private string $categoryCode = '';
    private string $categoryName = '';
    private string $description = '';
    private int $productCount = 0;

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
        $category->categoryCode = (string) ($categoryRow['category_code'] ?? '');
        $category->setCategoryName((string) $categoryRow['category_name']);
        $category->setDescription((string) $categoryRow['description']);
        $category->productCount = (int) ($categoryRow['product_count'] ?? 0);

        return $category;
    }

    /**
     * Function/Method Name: getCategoryDetails
     * Purpose:      Return all category attributes in one array.
     * Parameters:   none
     * Return Value: array - attribute name => value
     * Description:  Used by the details, delete confirmation and before/after pages.
     */
    public function getCategoryDetails(): array
    {
        return [
            'categoryId' => $this->categoryId,
            'categoryCode' => $this->categoryCode,
            'categoryName' => $this->categoryName,
            'description' => $this->description,
            'productCount' => $this->productCount,
        ];
    }

    /**
     * Function/Method Name: hasProducts
     * Purpose:      Tell whether any product still uses this category.
     * Parameters:   none
     * Return Value: bool - true when productCount is above 0
     * Description:  A category with products cannot be deleted (the database refuses it too).
     */
    public function hasProducts(): bool
    {
        return $this->productCount > 0;
    }

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    /**
     * Function/Method Name: getCategoryCode
     * Purpose:      Return the readable category code (e.g. "FRP").
     * Parameters:   none
     * Return Value: string
     * Description:  Made once by CodeGenerator when the category is created and never
     *               changed afterwards, even if the category is renamed.
     */
    public function getCategoryCode(): string
    {
        return $this->categoryCode;
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
