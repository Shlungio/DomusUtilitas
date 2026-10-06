<?php
// POST /DomusUtilitas_api/admin/toggle_product.php
// Activates or deactivates a product. Admin only.

declare(strict_types=1);

require __DIR__ . '/../auth/_helpers.php';
require __DIR__ . '/../db.php';
require __DIR__ . '/audit.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$body = get_json_body();

$id = (int)($body['id'] ?? 0);
$isActive = $body['is_active'] ?? null;

if ($id <= 0) {
    json_out([
        'ok' => false,
        'error' => 'Invalid product ID.'
    ], 400);
}

if ($isActive !== 0 && $isActive !== 1) {
    json_out([
        'ok' => false,
        'error' => 'Invalid product status.'
    ], 400);
}

try {

    // ========================================
    // CHECK PRODUCT EXISTS
    // ========================================

    $stmt = $pdo->prepare(
        'SELECT
            id,
            name,
            is_active
        FROM products
        WHERE id = ?'
    );

    $stmt->execute([
        $id
    ]);

    $product =
        $stmt->fetch();


    if (!$product) {

        json_out([
            'ok' => false,
            'error' => 'Product not found.'
        ], 404);

    }

    // Update product status
    $stmt = $pdo->prepare(
        'UPDATE products
         SET is_active = ?
         WHERE id = ?'
    );

    $stmt->execute([
        $isActive,
        $id
    ]);

    // ========================================
    // AUDIT LOG
    // ========================================

    $action =
        $isActive === 1
            ? 'PRODUCT_ACTIVATED'
            : 'PRODUCT_DEACTIVATED';


    $details =
        $isActive === 1
            ? 'Activated product "' .
            $product['name'] .
            '".'
            : 'Deactivated product "' .
            $product['name'] .
            '".';


    write_audit(
        $pdo,
        $action,
        'product',
        $id,
        $details
    );

    json_out([
        'ok' => true,
        'message' => $isActive === 1
            ? 'Product activated successfully.'
            : 'Product deactivated successfully.'
    ]);

} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' => 'Unable to update product status.'
    ], 500);
}