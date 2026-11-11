<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/request.php';
require_once __DIR__ . '/../services/api_response.php';
require_once __DIR__ . '/../services/input_guard.php';
require_once __DIR__ . '/../services/product_service.php';
require_once __DIR__ . '/../services/category_service.php';
require_once __DIR__ . '/../services/product_validator.php';
require_once __DIR__ . '/../services/cart_service.php';

/**
 * Class Name:  ApiCatalogController
 * Purpose:     Public read-only API for products, stock and categories.
 * Description: Uses the same services and validators as the website, so the API
 *              and the web pages always follow the same rules. Every method
 *              returns an ApiResponse; nothing is printed here.
 */
final class ApiCatalogController
{
    public const DEFAULT_PAGE_SIZE = 20;
    public const MAX_PAGE_SIZE = 50;
    private const MAX_PAGE = 1000;
    private const PRODUCT_CODE_PATTERN = '/^[A-Za-z0-9]{2,10}-\d{3,6}$/';

    private ProductService $productService;
    private CategoryService $categoryService;
    private ProductValidator $productValidator;
    private PDO $connection;

    public function __construct(ProductService $productService, CategoryService $categoryService, ProductValidator $productValidator, PDO $connection)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->productValidator = $productValidator;
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: showHealth
     * Purpose:      Tell whether the API and its database are working.
     * Parameters:   Request $request
     * Return Value: ApiResponse - 200 {"status": "ok", "database": "ok", "serverTime": ...}
     * Description:  A quick check other systems can call; it never shows secrets.
     */
    public function showHealth(Request $request): ApiResponse
    {
        $this->connection->query('SELECT 1');

        return ApiResponse::success(['status' => 'ok', 'database' => 'ok', 'serverTime' => date('c')]);
    }

    /**
     * Function/Method Name: listProducts
     * Purpose:      List or search products, one page at a time.
     * Parameters:   Request $request - query: keyword, categoryId, minPrice, maxPrice, page, perPage
     * Return Value: ApiResponse - 200 with products and paging meta, or 422 with field errors
     * Description:  An empty result is not an error: data is [] and meta.total is 0.
     */
    public function listProducts(Request $request): ApiResponse
    {
        $query = $request->getQuery();
        $fieldErrors = $this->productValidator->validateSearchInput($query) + $this->validateListInput($query);
        if ($fieldErrors !== []) {
            return ApiResponse::error(422, 'validation_failed', 'Some search values are not valid.', $fieldErrors);
        }

        $searchCriteria = $this->productValidator->toSearchCriteria($query);
        $products = $this->productValidator->hasSearchCriteria($searchCriteria)
            ? $this->productService->searchProduct($searchCriteria)
            : $this->productService->getAllProducts();

        $page = (int) ($query['page'] ?? 1);
        $perPage = (int) ($query['perPage'] ?? self::DEFAULT_PAGE_SIZE);
        $pageOfProducts = array_slice($products, ($page - 1) * $perPage, $perPage);

        return ApiResponse::success(
            array_map([self::class, 'toProductData'], $pageOfProducts),
            ['total' => count($products), 'page' => $page, 'perPage' => $perPage, 'totalPages' => (int) ceil(count($products) / $perPage)]
        );
    }

    /**
     * Function/Method Name: showProduct
     * Purpose:      Return one product by ID (e.g. 3) or code (e.g. FRP-003).
     * Parameters:   Request $request - route value productKey
     * Return Value: ApiResponse - 200 product, 400 bad key, or 404 not found
     * Description:  Deleted products (in Delete History) are "not found".
     */
    public function showProduct(Request $request): ApiResponse
    {
        $productKey = (string) ($request->getAttribute('route')['params']['productKey'] ?? '');
        $product = $this->findProductByKey($productKey);

        return $product instanceof ApiResponse ? $product : ApiResponse::success(self::toProductData($product));
    }

    /**
     * Function/Method Name: showStock
     * Purpose:      Live stock check for one product (used by the shop page).
     * Parameters:   Request $request - route value productKey; optional query "quantity"
     * Return Value: ApiResponse - 200 stock facts; isAvailable answers "can I buy this quantity?"
     * Description:  The quantity must be a whole number from 1 to 20 (the cart limit).
     */
    public function showStock(Request $request): ApiResponse
    {
        $product = $this->findProductByKey((string) ($request->getAttribute('route')['params']['productKey'] ?? ''));
        if ($product instanceof ApiResponse) {
            return $product;
        }

        $quantityText = InputGuard::readText($request->getQuery(), 'quantity', 10);
        $requestedQuantity = $quantityText === '' ? 1 : (ctype_digit($quantityText) ? (int) $quantityText : 0);
        if ($requestedQuantity < 1 || $requestedQuantity > CartService::MAX_QUANTITY_PER_ITEM) {
            return ApiResponse::error(422, 'validation_failed', 'Quantity must be a whole number from 1 to ' . CartService::MAX_QUANTITY_PER_ITEM . '.', [
                'quantity' => 'Use a whole number from 1 to ' . CartService::MAX_QUANTITY_PER_ITEM . '.',
            ]);
        }

        return ApiResponse::success([
            'productId' => $product->getProductId(),
            'productCode' => $product->getProductCode(),
            'stockQuantity' => $product->getStockQuantity(),
            'stockStatus' => $product->getStockStatus(),
            'requestedQuantity' => $requestedQuantity,
            'isAvailable' => $product->checkStock($requestedQuantity),
            'maxQuantityPerOrder' => min($product->getStockQuantity(), CartService::MAX_QUANTITY_PER_ITEM),
        ]);
    }

    public function listCategories(Request $request): ApiResponse
    {
        $categories = array_map(static fn (Category $category) => [
            'id' => $category->getCategoryId(),
            'code' => $category->getCategoryCode(),
            'name' => $category->getCategoryName(),
            'description' => $category->getDescription(),
            'productCount' => $category->getProductCount(),
        ], $this->categoryService->getAllCategories());

        return ApiResponse::success($categories, ['total' => count($categories)]);
    }

    /**
     * Function/Method Name: toProductData
     * Purpose:      Turn a Product into the API's product format.
     * Parameters:   Product $product
     * Return Value: array - id, code, name, description, price, priceText, stock and category
     * Description:  Prices are numbers (7.9) plus a ready-to-show text ("RM 7.90").
     */
    public static function toProductData(Product $product): array
    {
        return [
            'id' => $product->getProductId(),
            'code' => $product->getProductCode(),
            'name' => $product->getProductName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'priceText' => formatPrice($product->getPrice()),
            'stockQuantity' => $product->getStockQuantity(),
            'stockStatus' => $product->getStockStatus(),
            'category' => ['id' => $product->getCategoryId(), 'name' => $product->getCategoryName()],
        ];
    }

    /**
     * Function/Method Name: findProductByKey
     * Purpose:      Look up a product by numeric ID or by product code.
     * Parameters:   string $productKey - e.g. "3" or "FRP-003"
     * Return Value: Product|ApiResponse - the product, or a ready 400/404 error answer
     * Description:  Anything that is neither an ID nor a code shape is refused before
     *               touching the database.
     */
    private function findProductByKey(string $productKey): Product|ApiResponse
    {
        if (InputGuard::readId(['productKey' => $productKey], 'productKey') !== null) {
            $product = $this->productService->findProductById((int) $productKey);
        } elseif (preg_match(self::PRODUCT_CODE_PATTERN, $productKey)) {
            $product = $this->productService->findProductByCode($productKey);
        } else {
            return ApiResponse::error(400, 'invalid_product_key', 'Use a product ID (e.g. 3) or a product code (e.g. FRP-003).');
        }

        return $product ?? ApiResponse::error(404, 'not_found', 'No product found for "' . mb_substr($productKey, 0, 20) . '".');
    }

    private function validateListInput(array $query): array
    {
        $fieldErrors = [];
        $pagingFields = ['page' => ['Page number', self::MAX_PAGE], 'perPage' => ['Products per page', self::MAX_PAGE_SIZE]];
        foreach ($pagingFields as $fieldName => [$fieldLabel, $maxValue]) {
            $fieldText = InputGuard::readText($query, $fieldName, 10);
            if ($fieldText !== '' && (!ctype_digit($fieldText) || (int) $fieldText < 1 || (int) $fieldText > $maxValue)) {
                $fieldErrors[$fieldName] = $fieldLabel . ' must be a whole number from 1 to ' . $maxValue . '.';
            }
        }

        $categoryIdText = InputGuard::readText($query, 'categoryId', 11);
        if ($categoryIdText !== '' && InputGuard::readId($query, 'categoryId') === null) {
            $fieldErrors['categoryId'] = 'Category ID must be a whole number, for example 2.';
        }

        if (mb_strlen(InputGuard::readText($query, 'keyword', 1000)) > InputGuard::MAX_SEARCH_LENGTH) {
            $fieldErrors['keyword'] = 'Keyword must be ' . InputGuard::MAX_SEARCH_LENGTH . ' characters or fewer.';
        }

        return $fieldErrors;
    }
}
