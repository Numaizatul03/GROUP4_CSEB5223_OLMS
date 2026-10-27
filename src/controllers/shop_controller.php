<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/product_service.php';
require_once __DIR__ . '/../services/category_service.php';
require_once __DIR__ . '/../services/product_validator.php';

/**
 * Class Name:  ShopController
 * Purpose:     The customer shop: Browse Products, Search Products and View Product Details.
 * Description: Reuses ProductService::searchProduct() from the Product module,
 *              so customers and administrators search with exactly the same rules.
 */
class ShopController extends BaseController
{
    private ProductService $productService;
    private CategoryService $categoryService;
    private ProductValidator $productValidator;

    public function __construct(ProductService $productService, CategoryService $categoryService, ProductValidator $productValidator)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->productValidator = $productValidator;
    }

    /**
     * Function/Method Name: browseProducts
     * Purpose:      Show the product catalogue with category filter and search.
     * Parameters:   none (reads keyword, categoryId, minPrice, maxPrice from $_GET)
     * Return Value: void
     * Description:  With no criteria every product is shown; a search with no match
     *               shows a "Not found" message instead of an empty page.
     */
    public function browseProducts(): void
    {
        $searchErrors = $this->productValidator->validateSearchInput($_GET);
        $searchCriteria = $this->productValidator->toSearchCriteria($_GET);
        $hasSearched = $searchErrors === [] && $this->productValidator->hasSearchCriteria($searchCriteria);

        ViewService::renderView('shop/catalog', [
            'pageTitle' => 'Shop Local',
            'categories' => $this->categoryService->getAllCategories(),
            'searchInput' => $_GET,
            'searchErrors' => $searchErrors,
            'hasSearched' => $hasSearched,
            'products' => $hasSearched ? $this->productService->searchProduct($searchCriteria) : $this->productService->getAllProducts(),
        ]);
    }

    /**
     * Function/Method Name: viewProductDetails
     * Purpose:      Show one product with the Add to Cart form.
     * Parameters:   none (reads productId from $_GET)
     * Return Value: void
     * Description:  Shows "Not found" for an unknown ID.
     */
    public function viewProductDetails(): void
    {
        $productId = $this->readIdFromRequest($_GET, 'productId');
        $product = $productId === null ? null : $this->productService->findProductById($productId);
        if ($product === null) {
            $this->showNotFound('That product does not exist or is no longer sold.', ['Back to the shop' => buildUrl('shop')]);
            return;
        }

        ViewService::renderView('shop/product', [
            'pageTitle' => $product->getProductName(),
            'product' => $product,
            'maxQuantity' => min($product->getStockQuantity(), CartService::MAX_QUANTITY_PER_ITEM),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }
}
