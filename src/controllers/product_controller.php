<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/product_service.php';
require_once __DIR__ . '/../services/category_service.php';
require_once __DIR__ . '/../services/product_validator.php';
require_once __DIR__ . '/../services/session_service.php';
require_once __DIR__ . '/../services/view_service.php';

/**
 * Class Name:  ProductController
 * Purpose:     Handle every page of the Product module (Administrator: Manage Products).
 * Description: Each public method handles one page or one form submission:
 *              read the request -> call a service -> pass the result to a view.
 *              Business rules live in the services, HTML lives in the views.
 */
class ProductController
{
    private const SEARCH_MODE_SEARCH = 'search';
    private const SEARCH_MODE_EDIT = 'edit';
    private const SEARCH_MODE_DELETE = 'delete';

    private ProductService $productService;
    private CategoryService $categoryService;
    private ProductValidator $productValidator;

    public function __construct(
        ProductService $productService,
        CategoryService $categoryService,
        ProductValidator $productValidator
    ) {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->productValidator = $productValidator;
    }

    /**
     * Function/Method Name: listProducts
     * Purpose:      Data display function - show all products in a table.
     * Parameters:   none (reads the flash message and highlighted row from the session)
     * Return Value: void
     * Description:  Also the landing page after a product is created.
     */
    public function listProducts(): void
    {
        ViewService::renderView('products/list', [
            'pageTitle' => 'All Products',
            'products' => $this->productService->getAllProducts(),
            'successMessage' => SessionService::pullFlashData('successMessage'),
            'highlightProductId' => SessionService::pullFlashData('highlightProductId'),
        ]);
    }

    /**
     * Function/Method Name: showProduct
     * Purpose:      Show every attribute of one product.
     * Parameters:   none (reads productId from the query string)
     * Return Value: void
     * Description:  Shows the "Not found" page when the ID does not exist.
     */
    public function showProduct(): void
    {
        $product = $this->findRequestedProduct($_GET);
        if ($product === null) {
            $this->showNotFound($_GET['productId'] ?? '', self::SEARCH_MODE_SEARCH);
            return;
        }

        ViewService::renderView('products/view', [
            'pageTitle' => 'Product Details',
            'product' => $product,
        ]);
    }

    /**
     * Function/Method Name: showCreateForm
     * Purpose:      Show the empty "Add Product" form.
     * Parameters:   none
     * Return Value: void
     * Description:  The category dropdown is filled from the categories table.
     */
    public function showCreateForm(): void
    {
        $this->renderProductForm('products/create', 'Add Product', [], []);
    }

    /**
     * Function/Method Name: storeProduct
     * Purpose:      Save a new product from the submitted form.
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  Invalid input re-shows the form with error messages and the
     *               values the user typed. Valid input is saved, then the user is
     *               redirected to the product list with the new row highlighted.
     */
    public function storeProduct(): void
    {
        $this->requireValidCsrfToken();

        $validationErrors = $this->productValidator->validateProductInput($_POST, $this->categoryService->getCategoryIds());
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderProductForm('products/create', 'Add Product', $_POST, $validationErrors);
            return;
        }

        $productData = $this->productValidator->toProductData($_POST);
        $newProductId = $this->productService->createProduct($productData);

        SessionService::setFlashData('successMessage', 'Product #' . $newProductId . ' "' . $productData['productName'] . '" was added.');
        SessionService::setFlashData('highlightProductId', $newProductId);
        ViewService::redirectTo(buildUrl('list'));
    }

    /**
     * Function/Method Name: searchProducts
     * Purpose:      Search page - find products by ID, keyword, category or price range.
     * Parameters:   none (reads the criteria from $_GET)
     * Return Value: void
     * Description:  Shows matching products, or a "Not found" message when nothing
     *               matches. The full product list is always shown underneath.
     */
    public function searchProducts(): void
    {
        $this->renderSearchPage(self::SEARCH_MODE_SEARCH, 'Search Products');
    }

    /**
     * Function/Method Name: showEditSearch
     * Purpose:      Step 1 of editing - search for the product to edit.
     * Parameters:   none (reads the criteria from $_GET)
     * Return Value: void
     * Description:  Same search as searchProducts(), but each result has an Edit button.
     */
    public function showEditSearch(): void
    {
        $this->renderSearchPage(self::SEARCH_MODE_EDIT, 'Edit Product - Find Product');
    }

    /**
     * Function/Method Name: showEditForm
     * Purpose:      Step 2 of editing - show the form filled with the current values.
     * Parameters:   none (reads productId from $_GET)
     * Return Value: void
     * Description:  Shows the "Not found" page when the ID does not exist.
     */
    public function showEditForm(): void
    {
        $product = $this->findRequestedProduct($_GET);
        if ($product === null) {
            $this->showNotFound($_GET['productId'] ?? '', self::SEARCH_MODE_EDIT);
            return;
        }

        $this->renderProductForm('products/edit', 'Edit Product', $this->toFormInput($product), [], $product);
    }

    /**
     * Function/Method Name: updateProduct
     * Purpose:      Step 3 of editing - save the changes.
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  Keeps a copy of the old values so the result page can show
     *               "before" and "after" side by side for the user to validate.
     */
    public function updateProduct(): void
    {
        $this->requireValidCsrfToken();

        $product = $this->findRequestedProduct($_POST);
        if ($product === null) {
            $this->showNotFound($_POST['productId'] ?? '', self::SEARCH_MODE_EDIT);
            return;
        }

        $validationErrors = $this->productValidator->validateProductInput($_POST, $this->categoryService->getCategoryIds());
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderProductForm('products/edit', 'Edit Product', $_POST, $validationErrors, $product);
            return;
        }

        $previousProductDetails = $product->getProductDetails();
        $product->updateProduct($this->productValidator->toProductData($_POST));
        $this->productService->updateProduct($product);

        SessionService::setFlashData('previousProductDetails', $previousProductDetails);
        ViewService::redirectTo(buildUrl('edit_result', ['productId' => $product->getProductId()]));
    }

    /**
     * Function/Method Name: showEditResult
     * Purpose:      Step 4 of editing - display the edited data to validate the changes.
     * Parameters:   none (reads productId from $_GET and the old values from the session)
     * Return Value: void
     * Description:  The product is read again from the database, so the "after"
     *               column proves what was really saved. The full list follows.
     */
    public function showEditResult(): void
    {
        $product = $this->findRequestedProduct($_GET);
        if ($product === null) {
            $this->showNotFound($_GET['productId'] ?? '', self::SEARCH_MODE_EDIT);
            return;
        }

        ViewService::renderView('products/edit_result', [
            'pageTitle' => 'Product Updated',
            'previousProductDetails' => SessionService::pullFlashData('previousProductDetails'),
            'currentProductDetails' => $product->getProductDetails(),
            'products' => $this->productService->getAllProducts(),
            'highlightProductId' => $product->getProductId(),
        ]);
    }

    /**
     * Function/Method Name: showDeleteSearch
     * Purpose:      Step 1 of deleting - search for the product to delete.
     * Parameters:   none (reads the criteria from $_GET)
     * Return Value: void
     * Description:  Same search as searchProducts(), but each result has a Delete button.
     */
    public function showDeleteSearch(): void
    {
        $this->renderSearchPage(self::SEARCH_MODE_DELETE, 'Delete Product - Find Product');
    }

    /**
     * Function/Method Name: confirmDelete
     * Purpose:      Step 2 of deleting - show all attributes and ask for confirmation.
     * Parameters:   none (reads productId from $_GET)
     * Return Value: void
     * Description:  Nothing is deleted until the user presses "Yes, delete".
     */
    public function confirmDelete(): void
    {
        $product = $this->findRequestedProduct($_GET);
        if ($product === null) {
            $this->showNotFound($_GET['productId'] ?? '', self::SEARCH_MODE_DELETE);
            return;
        }

        ViewService::renderView('products/delete_confirm', [
            'pageTitle' => 'Delete Product - Confirm',
            'product' => $product,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: deleteProduct
     * Purpose:      Step 3 of deleting - remove the confirmed product.
     * Parameters:   none (reads productId from $_POST)
     * Return Value: void
     * Description:  Stores the deleted record and the product count before deletion
     *               so the result page can prove the deletion happened.
     */
    public function deleteProduct(): void
    {
        $this->requireValidCsrfToken();

        $product = $this->findRequestedProduct($_POST);
        if ($product === null) {
            $this->showNotFound($_POST['productId'] ?? '', self::SEARCH_MODE_DELETE);
            return;
        }

        $productCountBefore = $this->productService->countProducts();
        $this->productService->deleteProduct((int) $product->getProductId());

        SessionService::setFlashData('deletedProductDetails', $product->getProductDetails());
        SessionService::setFlashData('productCountBefore', $productCountBefore);
        ViewService::redirectTo(buildUrl('delete_result'));
    }

    /**
     * Function/Method Name: showDeleteResult
     * Purpose:      Step 4 of deleting - display all data to validate the deletion.
     * Parameters:   none (reads the deleted record from the session)
     * Return Value: void
     * Description:  Re-checks the database to confirm the product no longer exists,
     *               then shows the full product list without it.
     */
    public function showDeleteResult(): void
    {
        $deletedProductDetails = SessionService::pullFlashData('deletedProductDetails');
        if ($deletedProductDetails === null) {
            ViewService::redirectTo(buildUrl('list'));
        }

        $deletedProductId = (int) $deletedProductDetails['productId'];

        ViewService::renderView('products/delete_result', [
            'pageTitle' => 'Product Deleted',
            'deletedProductDetails' => $deletedProductDetails,
            'isDeletionVerified' => $this->productService->findProductById($deletedProductId) === null,
            'productCountBefore' => (int) SessionService::pullFlashData('productCountBefore'),
            'productCountAfter' => $this->productService->countProducts(),
            'products' => $this->productService->getAllProducts(),
        ]);
    }

    /**
     * Function/Method Name: renderSearchPage
     * Purpose:      Run a search and show the shared search page.
     * Parameters:   string $searchMode - "search", "edit" or "delete" (decides the result button)
     *               string $pageTitle - heading shown on the page
     * Return Value: void
     * Description:  One method serves three pages, so the search logic is written once.
     */
    private function renderSearchPage(string $searchMode, string $pageTitle): void
    {
        $searchErrors = $this->productValidator->validateSearchInput($_GET);
        $searchCriteria = $this->productValidator->toSearchCriteria($_GET);
        $hasSearched = $searchErrors === [] && $this->productValidator->hasSearchCriteria($searchCriteria);

        ViewService::renderView('products/search', [
            'pageTitle' => $pageTitle,
            'searchMode' => $searchMode,
            'searchInput' => $_GET,
            'searchErrors' => $searchErrors,
            'categories' => $this->categoryService->getAllCategories(),
            'hasSearched' => $hasSearched,
            'searchResults' => $hasSearched ? $this->productService->searchProduct($searchCriteria) : [],
            'products' => $this->productService->getAllProducts(),
        ]);
    }

    private function renderProductForm(
        string $viewName,
        string $pageTitle,
        array $formInput,
        array $validationErrors,
        ?Product $product = null
    ): void {
        ViewService::renderView($viewName, [
            'pageTitle' => $pageTitle,
            'formInput' => $formInput,
            'validationErrors' => $validationErrors,
            'categories' => $this->categoryService->getAllCategories(),
            'product' => $product,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    private function showNotFound(mixed $requestedProductId, string $searchMode): void
    {
        http_response_code(404);
        ViewService::renderView('products/not_found', [
            'pageTitle' => 'Product Not Found',
            'requestedProductId' => (string) $requestedProductId,
            'searchMode' => $searchMode,
        ]);
    }

    /**
     * Function/Method Name: findRequestedProduct
     * Purpose:      Load the product whose ID was sent in the request.
     * Parameters:   array $requestInput - $_GET or $_POST
     * Return Value: ?Product - the product, or null when the ID is missing, invalid or unknown
     * Description:  Shared by view, edit and delete so the ID check is written once.
     */
    private function findRequestedProduct(array $requestInput): ?Product
    {
        $productIdText = (string) ($requestInput['productId'] ?? '');
        if (!ctype_digit($productIdText)) {
            return null;
        }

        return $this->productService->findProductById((int) $productIdText);
    }

    private function toFormInput(Product $product): array
    {
        return [
            'productId' => $product->getProductId(),
            'productName' => $product->getProductName(),
            'categoryId' => $product->getCategoryId(),
            'description' => $product->getDescription(),
            'price' => number_format($product->getPrice(), 2, '.', ''),
            'stockQuantity' => $product->getStockQuantity(),
        ];
    }

    private function requireValidCsrfToken(): void
    {
        if (!SessionService::isValidCsrfToken($_POST['csrfToken'] ?? null)) {
            http_response_code(419);
            exit('Your form has expired. Please go back, refresh the page and try again.');
        }
    }
}
