<?php
// GET /DomusUtilitas_api/orders/history.php
// Returns the currently logged-in customer's orders.

declare(strict_types=1);

require __DIR__ . '/../auth/_helpers.php';
require __DIR__ . '/../db.php';

require_role('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$user = current_user();

try {

    // Get the customer's orders
    $stmt = $pdo->prepare(
        'SELECT
            id,
            total_amount,
            status,
            created_at,
            updated_at
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC'
    );

    $stmt->execute([
        $user['id']
    ]);

    $orders = $stmt->fetchAll();


    // Get items for each order
    $itemStmt = $pdo->prepare(
        'SELECT
            product_id,
            product_name,
            price,
            quantity,
            subtotal
         FROM order_items
         WHERE order_id = ?
         ORDER BY id'
    );


    foreach ($orders as &$order) {

        $itemStmt->execute([
            $order['id']
        ]);

        $order['items'] =
            $itemStmt->fetchAll();

    }

    unset($order);


    json_out([
        'ok' => true,
        'orders' => $orders
    ]);


} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' => 'Unable to load order history.'
    ], 500);

}