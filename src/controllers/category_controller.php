<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/category_service.php';
require_once __DIR__ . '/../services/category_validator.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  CategoryController
 * Purpose:     The "Manage Product Categories" use case: create, search, edit, delete, display.
 * Description: Follows the same flow as the Product module: search to find the
 *              target, show "Not found" on no match, show before/after after
 *              editing and the full list after deleting.
 */
class CategoryController extends BaseController
{
    private const FIELD_LABELS = ['categoryName' => 'Category Name', 'description' => 'Description', 'productCount' => 'Products'];

    private CategoryService $categoryService;
    private CategoryValidator $categoryValidator;

    public function __construct(CategoryService $categoryService, CategoryValidator $categoryValidator)
    {
        $this->categoryService = $categoryService;
        $this->categoryValidator = $categoryValidator;
    }

    /**
     * Function/Method Name: listCategories
     * Purpose:      Display all categories, with a search form at the top.
     * Parameters:   none (reads categoryId and keyword from $_GET)
     * Return Value: void
     * Description:  Each row has View/Edit/Delete; a search with no match shows "Not found".
     */
    public function listCategories(): void
    {
        $searchErrors = $this->categoryValidator->validateSearchInput($_GET);
        $searchCriteria = $this->categoryValidator->toSearchCriteria($_GET);
        $hasSearched = $searchErrors === [] && OrderValidator::hasSearchCriteria($searchCriteria);

        ViewService::renderView('categories/list', [
            'pageTitle' => 'Manage Categories',
            'searchInput' => $_GET,
            'searchErrors' => $searchErrors,
            'hasSearched' => $hasSearched,
            'searchResults' => $hasSearched ? $this->categoryService->searchCategories($searchCriteria) : [],
            'categories' => $this->categoryService->getAllCategories(),
            'highlightCategoryId' => SessionService::pullFlashData('highlightCategoryId'),
        ]);
    }

    public function showCreateForm(): void
    {
        $this->renderForm('categories/form', 'Add Category', [], [], null);
    }

    /**
     * Function/Method Name: storeCategory
     * Purpose:      Save a new category.
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  Invalid input re-shows the form; success returns to the list with the new row highlighted.
     */
    public function storeCategory(): void
    {
        $this->requireValidCsrfToken();
        $isNameTaken = $this->categoryService->isCategoryNameTaken((string) ($_POST['categoryName'] ?? ''));
        $validationErrors = $this->categoryValidator->validateCategoryInput($_POST, $isNameTaken);
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderForm('categories/form', 'Add Category', $_POST, $validationErrors, null);
            return;
        }

        $categoryData = $this->categoryValidator->toCategoryData($_POST);
        $newCategoryId = $this->categoryService->createCategory($categoryData);
        SessionService::setFlashData('highlightCategoryId', $newCategoryId);
        $this->redirectWithSuccess(buildUrl('categories'), 'Category "' . $categoryData['categoryName'] . '" was added.');
    }

    public function showEditForm(): void
    {
        $category = $this->findRequestedCategory($_GET);
        if ($category !== null) {
            $this->renderForm('categories/form', 'Edit Category', $category->getCategoryDetails(), [], $category);
        }
    }

    /**
     * Function/Method Name: updateCategory
     * Purpose:      Save changes to a category.
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  Keeps the old values so the result page can show before and after.
     */
    public function updateCategory(): void
    {
        $this->requireValidCsrfToken();
        $category = $this->findRequestedCategory($_POST);
        if ($category === null) {
            return;
        }

        $isNameTaken = $this->categoryService->isCategoryNameTaken((string) ($_POST['categoryName'] ?? ''), $category->getCategoryId());
        $validationErrors = $this->categoryValidator->validateCategoryInput($_POST, $isNameTaken);
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderForm('categories/form', 'Edit Category', $_POST, $validationErrors, $category);
            return;
        }

        $previousDetails = $category->getCategoryDetails();
        $categoryData = $this->categoryValidator->toCategoryData($_POST);
        $category->setCategoryName($categoryData['categoryName']);
        $category->setDescription($categoryData['description']);
        $this->categoryService->updateCategory($category);

        SessionService::setFlashData('previousDetails', $previousDetails);
        ViewService::redirectTo(buildUrl('category_edit_result', ['categoryId' => $category->getCategoryId()]));
    }

    /**
     * Function/Method Name: showEditResult
     * Purpose:      Display the edited category next to its old values, then the full list.
     * Parameters:   none (reads categoryId from $_GET)
     * Return Value: void
     * Description:  The "after" values are read again from the database.
     */
    public function showEditResult(): void
    {
        $category = $this->findRequestedCategory($_GET);
        if ($category === null) {
            return;
        }

        ViewService::renderView('shared/update_result', [
            'pageTitle' => 'Category Updated',
            'successMessage' => 'Category #' . $category->getCategoryId() . ' was updated.',
            'fieldLabels' => self::FIELD_LABELS,
            'previousDetails' => SessionService::pullFlashData('previousDetails'),
            'currentDetails' => $category->getCategoryDetails(),
            'backLinks' => ['Edit again' => buildUrl('category_edit', ['categoryId' => $category->getCategoryId()]), 'All categories' => buildUrl('categories')],
            'listTitle' => 'All Categories',
            'listPartial' => 'categories/partials/category_table',
            'listData' => ['categories' => $this->categoryService->getAllCategories(), 'highlightCategoryId' => $category->getCategoryId()],
        ]);
    }

    /**
     * Function/Method Name: confirmDelete
     * Purpose:      Show the whole category and ask "Are you sure?".
     * Parameters:   none (reads categoryId from $_GET)
     * Return Value: void
     * Description:  A category that still has products shows why it cannot be deleted.
     */
    public function confirmDelete(): void
    {
        $category = $this->findRequestedCategory($_GET);
        if ($category === null) {
            return;
        }

        ViewService::renderView('shared/delete_confirm', [
            'pageTitle' => 'Delete Category - Confirm',
            'entityLabel' => 'category',
            'detailRows' => $this->toDetailRows($category),
            'blockedReason' => $category->hasProducts()
                ? 'This category still has ' . $category->getProductCount() . ' product(s). Move or delete those products first.'
                : null,
            'deleteAction' => 'category_delete',
            'hiddenFields' => ['categoryId' => $category->getCategoryId()],
            'cancelUrl' => buildUrl('categories'),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: deleteCategory
     * Purpose:      Delete the confirmed category.
     * Parameters:   none (reads categoryId from $_POST)
     * Return Value: void
     * Description:  Stores the deleted record and the count before deletion for the result page.
     */
    public function deleteCategory(): void
    {
        $this->requireValidCsrfToken();
        $category = $this->findRequestedCategory($_POST);
        if ($category === null) {
            return;
        }

        $countBefore = $this->categoryService->countCategories();
        try {
            $this->categoryService->deleteCategory($category->getCategoryId());
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('category_delete_confirm', ['categoryId' => $category->getCategoryId()]), $exception->getMessage());
        }

        SessionService::setFlashData('deletedRecord', ['id' => $category->getCategoryId(), 'rows' => $this->toDetailRows($category), 'countBefore' => $countBefore]);
        ViewService::redirectTo(buildUrl('category_delete_result'));
    }

    public function showDeleteResult(): void
    {
        $deletedRecord = SessionService::pullFlashData('deletedRecord');
        if ($deletedRecord === null) {
            ViewService::redirectTo(buildUrl('categories'));
        }

        ViewService::renderView('shared/delete_result', [
            'pageTitle' => 'Category Deleted',
            'entityLabel' => 'Category',
            'deletedRecord' => $deletedRecord,
            'isDeletionVerified' => $this->categoryService->findCategoryById((int) $deletedRecord['id']) === null,
            'countAfter' => $this->categoryService->countCategories(),
            'listTitle' => 'All Categories',
            'listPartial' => 'categories/partials/category_table',
            'listData' => ['categories' => $this->categoryService->getAllCategories()],
        ]);
    }

    private function renderForm(string $viewName, string $pageTitle, array $formInput, array $validationErrors, ?Category $category): void
    {
        ViewService::renderView($viewName, [
            'pageTitle' => $pageTitle,
            'formInput' => $formInput,
            'validationErrors' => $validationErrors,
            'category' => $category,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    private function toDetailRows(Category $category): array
    {
        return [
            'Category ID' => (string) $category->getCategoryId(),
            'Category Code' => $category->getCategoryCode(),
            'Category Name' => $category->getCategoryName(),
            'Description' => $category->getDescription() === '' ? '-' : $category->getDescription(),
            'Products in category' => (string) $category->getProductCount(),
        ];
    }

    /**
     * Function/Method Name: findRequestedCategory
     * Purpose:      Load the category whose ID was sent, or show "Not found".
     * Parameters:   array $requestInput - $_GET or $_POST
     * Return Value: ?Category - null after the "Not found" page was shown
     * Description:  Shared by edit and delete so the ID check is written once.
     */
    private function findRequestedCategory(array $requestInput): ?Category
    {
        $categoryId = $this->readIdFromRequest($requestInput, 'categoryId');
        $category = $categoryId === null ? null : $this->categoryService->findCategoryById($categoryId);
        if ($category === null) {
            $this->showNotFound(
                'There is no category with ID "' . ($requestInput['categoryId'] ?? '') . '". It may have been deleted already.',
                ['Search categories' => buildUrl('categories')]
            );
        }

        return $category;
    }
}
