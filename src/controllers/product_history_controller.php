<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/product_service.php';

/**
 * Class Name:  ProductHistoryController
 * Purpose:     Delete History for products: list deleted products, restore them, or delete them for good.
 * Description: Fixes the testing problem "deleted products cannot be restored".
 *              A restored product keeps its original product ID. Kept separate
 *              from ProductController so the Lab 3 & 4 product code stays small.
 */
class ProductHistoryController extends BaseController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Function/Method Name: listDeletedProducts
     * Purpose:      Show every product in Delete History.
     * Parameters:   none
     * Return Value: void
     * Description:  Most recently deleted first, each with Restore and Delete permanently.
     */
    public function listDeletedProducts(): void
    {
        ViewService::renderView('products/history', [
            'pageTitle' => 'Delete History',
            'deletedProducts' => $this->productService->getDeletedProducts(),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    /**
     * Function/Method Name: restoreProduct
     * Purpose:      Bring a deleted product back.
     * Parameters:   none (reads productId from $_POST)
     * Return Value: void
     * Description:  Returns to the product list with the restored row highlighted,
     *               showing it kept the same ID.
     */
    public function restoreProduct(): void
    {
        $this->requireValidCsrfToken();
        $deletedProduct = $this->findRequestedDeletedProduct();
        if ($deletedProduct === null) {
            return;
        }

        $this->productService->restoreProduct((int) $deletedProduct->getProductId());
        SessionService::setFlashData('highlightProductId', $deletedProduct->getProductId());
        SessionService::setFlashData(
            'successMessage',
            'Product #' . $deletedProduct->getProductId() . ' "' . $deletedProduct->getProductName() . '" was restored with its original ID.'
        );
        ViewService::redirectTo(buildUrl('list') . '#product-' . $deletedProduct->getProductId());
    }

    /**
     * Function/Method Name: permanentlyDeleteProduct
     * Purpose:      Remove a product from Delete History for good.
     * Parameters:   none (reads productId from $_POST)
     * Return Value: void
     * Description:  The browser asks "Are you sure?" first. Past orders keep the
     *               product name and price they were bought at.
     */
    public function permanentlyDeleteProduct(): void
    {
        $this->requireValidCsrfToken();
        $deletedProduct = $this->findRequestedDeletedProduct();
        if ($deletedProduct === null) {
            return;
        }

        $this->productService->permanentlyDeleteProduct((int) $deletedProduct->getProductId());
        $this->redirectWithSuccess(
            buildUrl('product_history'),
            'Product #' . $deletedProduct->getProductId() . ' "' . $deletedProduct->getProductName() . '" was deleted permanently.'
        );
    }

    private function findRequestedDeletedProduct(): ?Product
    {
        $productId = $this->readIdFromRequest($_POST, 'productId');
        $deletedProduct = $productId === null ? null : $this->productService->findDeletedProductById($productId);
        if ($deletedProduct === null) {
            $this->showNotFound(
                'That product is not in Delete History. It may have been restored or deleted permanently already.',
                ['Back to Delete History' => buildUrl('product_history')]
            );
        }

        return $deletedProduct;
    }
}
