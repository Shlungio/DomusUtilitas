<?php
// POST /DomusUtilitas_api/admin/update_order_status.php
// Updates an order's status.
// If an order becomes Cancelled, its product quantities are restored once.
// Cancelled orders cannot be reopened.

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

$orderId = (int)($body['order_id'] ?? 0);
$newStatus = trim($body['status'] ?? '');

$allowedStatuses = [
    'Pending',
    'Processing',
    'Shipped',
    'Completed',
    'Cancelled'
];


// ==========================
// VALIDATION
// ==========================

if ($orderId <= 0) {
    json_out([
        'ok' => false,
        'error' => 'Invalid order ID.'
    ], 400);
}


if (!in_array($newStatus, $allowedStatuses, true)) {
    json_out([
        'ok' => false,
        'error' => 'Invalid order status.'
    ], 400);
}


try {

    // Everything happens together.
    // If one part fails, nothing is permanently changed.
    $pdo->beginTransaction();


    // ==========================
    // GET CURRENT ORDER STATUS
    // ==========================

    $stmt = $pdo->prepare(
        'SELECT id, status
         FROM orders
         WHERE id = ?
         FOR UPDATE'
    );

    $stmt->execute([
        $orderId
    ]);

    $order = $stmt->fetch();


    if (!$order) {

        $pdo->rollBack();

        json_out([
            'ok' => false,
            'error' => 'Order not found.'
        ], 404);

    }


    $currentStatus =
        $order['status'];


    // ==========================
    // SAME STATUS
    // ==========================

    if ($currentStatus === $newStatus) {

        $pdo->commit();

        json_out([
            'ok' => true,
            'message' => 'Order status is already ' . $newStatus . '.'
        ]);

    }


    // ==========================
    // CANCELLED IS FINAL
    // ==========================

    if ($currentStatus === 'Cancelled') {

        $pdo->rollBack();

        json_out([
            'ok' => false,
            'error' => 'Cancelled orders cannot be reopened or changed.'
        ], 400);

    }


    // ==========================
    // RESTORE STOCK
    // ONLY WHEN BECOMING CANCELLED
    // ==========================

    if ($newStatus === 'Cancelled') {

        // Get every item belonging to this order.
        $itemStmt = $pdo->prepare(
            'SELECT
                product_id,
                quantity
             FROM order_items
             WHERE order_id = ?'
        );

        $itemStmt->execute([
            $orderId
        ]);

        $items =
            $itemStmt->fetchAll();


        // Add the purchased quantities back
        // into inventory.
        $restoreStock = $pdo->prepare(
            'UPDATE products
             SET stock = stock + ?
             WHERE id = ?'
        );


        foreach ($items as $item) {

            // product_id may be NULL if a product
            // had been permanently deleted.
            if ($item['product_id'] === null) {
                continue;
            }


            $restoreStock->execute([
                (int)$item['quantity'],
                (int)$item['product_id']
            ]);

        }

    }


        // ==========================
        // UPDATE ORDER STATUS
        // ==========================

        $stmt = $pdo->prepare(
            'UPDATE orders
            SET status = ?
            WHERE id = ?'
        );

        $stmt->execute([
        $newStatus,
        $orderId
    ]);


    // ========================================
    // AUDIT LOG
    // ========================================

    $details =
        'Changed order #' .
        $orderId .
        ' status from ' .
        $currentStatus .
        ' to ' .
        $newStatus .
        '.';


    if ($newStatus === 'Cancelled') {

        $details .=
            ' Product stock was restored.';

    }


    write_audit(
        $pdo,
        'ORDER_STATUS_CHANGED',
        'order',
        $orderId,
        $details
    );


    // Make all database changes permanent.
    $pdo->commit();

    json_out([
        'ok' => true,
        'message' =>
            $newStatus === 'Cancelled'
                ? 'Order cancelled and stock restored successfully.'
                : 'Order status updated successfully.'
    ]);


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    json_out([
        'ok' => false,
        'error' => 'Unable to update order status.'
    ], 500);

}