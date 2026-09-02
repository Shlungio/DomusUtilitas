<?php
// GET /DomusUtilitas_api/admin/orders.php
// Returns all customer orders. Admin only.

declare(strict_types=1);

require __DIR__ . '/../auth/_helpers.php';
require __DIR__ . '/../db.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

try {

    $stmt = $pdo->query(
        'SELECT
            o.id,
            o.user_id,
            o.total_amount,
            o.status,
            o.created_at,
            o.updated_at,
            u.username
         FROM orders o
         LEFT JOIN users u
            ON o.user_id = u.id
         ORDER BY o.created_at DESC, o.id DESC'
    );

    $orders = $stmt->fetchAll();


    $itemStmt = $pdo->prepare(
        'SELECT
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
        'error' => 'Unable to load customer orders.'
    ], 500);

}