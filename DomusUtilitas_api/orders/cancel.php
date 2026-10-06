<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/_helpers.php';

require_role('customer');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);

}


$data = get_json_body();

$orderId =
    isset($data['id'])
        ? (int) $data['id']
        : 0;


if ($orderId <= 0) {

    json_out([
        'ok' => false,
        'error' => 'Invalid order ID.'
    ], 400);

}


try {

    $pdo->beginTransaction();


    // ==========================
    // GET CUSTOMER ORDER
    // ==========================

    $stmt = $pdo->prepare(
        "
        SELECT
            id,
            status
        FROM orders
        WHERE id = ?
          AND user_id = ?
        FOR UPDATE
        "
    );

    $stmt->execute([
        $orderId,
        $_SESSION['user_id']
    ]);

    $order =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {

        throw new Exception(
            'Order not found.'
        );

    }


    // CUSTOMER CAN ONLY CANCEL PENDING
    if ($order['status'] !== 'Pending') {

        throw new Exception(
            'Only pending orders can be cancelled.'
        );

    }


    // ==========================
    // GET ORDER ITEMS
    // ==========================

    $stmt = $pdo->prepare(
        "
        SELECT
            product_id,
            quantity
        FROM order_items
        WHERE order_id = ?
        "
    );

    $stmt->execute([
        $orderId
    ]);

    $items =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    // ==========================
    // RESTORE STOCK
    // ==========================

    $restoreStock =
        $pdo->prepare(
            "
            UPDATE products
            SET stock = stock + ?
            WHERE id = ?
            "
        );


    foreach ($items as $item) {

        if ($item['product_id'] !== null) {

            $restoreStock->execute([
                $item['quantity'],
                $item['product_id']
            ]);

        }

    }


    // ==========================
    // CANCEL ORDER
    // ==========================

    $stmt = $pdo->prepare(
        "
        UPDATE orders
        SET status = 'Cancelled'
        WHERE id = ?
        "
    );

    $stmt->execute([
        $orderId
    ]);


    $pdo->commit();


    json_out([
        'ok' => true,
        'message' => 'Order cancelled successfully.'
    ]);


} catch (Throwable $error) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    json_out([
        'ok' => false,
        'error' => $error->getMessage()
    ], 400);

}