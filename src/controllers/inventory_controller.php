<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/product_service.php';

/**
 * Class Name:  InventoryController
 * Purpose:     The "Manage Inventory" use case: see stock levels and restock or write off.
 * Description: Separate from ProductController so the Lab 3 & 4 product code
 *              (owned by other members) did not need to change.
 */
class InventoryController extends BaseController
{
    private const STOCK_CHANGE_PATTERN = '/^-?\d{1,6}$/';

    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Function/Method Name: showInventory
     * Purpose:      List products with the lowest stock first, each with a stock form.
     * Parameters:   none (reads the highlighted productId from the session)
     * Return Value: void
     * Description:  Out-of-stock and low-stock products appear at the top.
     */
    public function showInventory(): void
    {
        ViewService::renderView('inventory/index', [
            'pageTitle' => 'Manage Inventory',
            'products' => $this->productService->getProductsByStockLevel(),
            'highlightProductId' => SessionService::pullFlashData('highlightProductId'),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: adjustStock
     * Purpose:      Add (restock) or subtract (write off) stock for one product.
     * Parameters:   none (reads productId and quantityChange from $_POST)
     * Return Value: void
     * Description:  quantityChange is a whole number such as 10 or -2 and must not be 0.
     */
    public function adjustStock(): void
    {
        $this->requireValidCsrfToken();
        $productId = $this->readIdFromRequest($_POST, 'productId');
        $quantityChangeText = trim((string) ($_POST['quantityChange'] ?? ''));

        if ($productId === null || !preg_match(self::STOCK_CHANGE_PATTERN, $quantityChangeText) || (int) $quantityChangeText === 0) {
            $this->redirectWithError(buildUrl('inventory'), 'Enter a whole number to add (e.g. 10) or remove (e.g. -2). Zero changes nothing.');
        }

        try {
            $newStockQuantity = $this->productService->adjustStock($productId, (int) $quantityChangeText);
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('inventory'), $exception->getMessage());
        }

        SessionService::setFlashData('highlightProductId', $productId);
        $this->redirectWithSuccess(
            buildUrl('inventory') . '#product-' . $productId,
            'Stock for product #' . $productId . ' is now ' . $newStockQuantity . '.'
        );
    }
}
