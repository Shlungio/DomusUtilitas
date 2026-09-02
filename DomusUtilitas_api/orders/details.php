<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/_helpers.php';

require_role('customer');


$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


if ($orderId <= 0) {

    json_out([
        'ok' => false,
        'error' => 'Invalid order ID.'
    ], 400);

}


try {

    // GET ORDER
    $stmt = $pdo->prepare(
        "
        SELECT
            id,
            total_amount,
            status,
            created_at
        FROM orders
        WHERE id = ?
          AND user_id = ?
        LIMIT 1
        "
    );

    $stmt->execute([
        $orderId,
        $_SESSION['user_id']
    ]);

    $order =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {

        json_out([
            'ok' => false,
            'error' => 'Order not found.'
        ], 404);

    }


    // GET ORDER ITEMS
    $stmt = $pdo->prepare(
        "
        SELECT
            product_name,
            price,
            quantity,
            subtotal
        FROM order_items
        WHERE order_id = ?
        ORDER BY id ASC
        "
    );

    $stmt->execute([
        $orderId
    ]);

    $items =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    // RETURN RECEIPT
    json_out([
        'ok' => true,

        'order' => [
            'id' => (int) $order['id'],
            'total_amount' => (float) $order['total_amount'],
            'status' => $order['status'],
            'created_at' => $order['created_at'],
            'items' => $items
        ]
    ]);


} catch (Throwable $error) {

    json_out([
        'ok' => false,
        'error' => $error->getMessage()
    ], 500);

}