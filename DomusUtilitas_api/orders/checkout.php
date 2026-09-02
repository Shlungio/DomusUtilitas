<?php
// POST /DomusUtilitas_api/orders/checkout.php
// Creates an order for the currently logged-in customer.

declare(strict_types=1);

require __DIR__ . '/../auth/_helpers.php';
require __DIR__ . '/../db.php';

require_role('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$body = get_json_body();
$items = $body['items'] ?? [];

if (!is_array($items) || count($items) === 0) {
    json_out([
        'ok' => false,
        'error' => 'Your cart is empty.'
    ], 400);
}


// Build a clean list of requested products
$requested = [];

foreach ($items as $item) {

    $productId = (int)($item['id'] ?? 0);
    $quantityRaw = $item['qty'] ?? null;

    if (
        $productId <= 0 ||
        filter_var($quantityRaw, FILTER_VALIDATE_INT) === false ||
        (int)$quantityRaw <= 0
    ) {
        json_out([
            'ok' => false,
            'error' => 'Invalid cart data.'
        ], 400);
    }

    $quantity = (int)$quantityRaw;

    // If the same product somehow appears twice,
    // combine the quantities.
    if (isset($requested[$productId])) {
        $requested[$productId] += $quantity;
    } else {
        $requested[$productId] = $quantity;
    }
}


$user = current_user();

try {

    $pdo->beginTransaction();


    // Lock the requested product rows during checkout.
    $productIds = array_keys($requested);

    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );


    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            price,
            stock,
            is_active
         FROM products
         WHERE id IN ($placeholders)
         FOR UPDATE"
    );

    $stmt->execute($productIds);

    $rows = $stmt->fetchAll();


    $products = [];

    foreach ($rows as $row) {
        $products[(int)$row['id']] = $row;
    }


    $orderItems = [];
    $totalAmount = 0.00;


    // Validate everything again on the SERVER.
    foreach ($requested as $productId => $quantity) {

        if (!isset($products[$productId])) {
            throw new InvalidArgumentException(
                'One of the products no longer exists.'
            );
        }


        $product = $products[$productId];


        if ((int)$product['is_active'] !== 1) {
            throw new InvalidArgumentException(
                $product['name'] . ' is no longer available.'
            );
        }


        if ((int)$product['stock'] < $quantity) {
            throw new InvalidArgumentException(
                'Not enough stock available for ' .
                $product['name'] . '.'
            );
        }


        $price = (float)$product['price'];

        $subtotal = round(
            $price * $quantity,
            2
        );


        $totalAmount = round(
            $totalAmount + $subtotal,
            2
        );


        $orderItems[] = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];

    }


    // Create order
    $stmt = $pdo->prepare(
        'INSERT INTO orders
            (user_id, total_amount, status)
         VALUES
            (?, ?, ?)'
    );

    $stmt->execute([
        $user['id'],
        $totalAmount,
        'Pending'
    ]);


    $orderId = (int)$pdo->lastInsertId();


    // Insert order items
    $insertItem = $pdo->prepare(
        'INSERT INTO order_items
            (
                order_id,
                product_id,
                product_name,
                price,
                quantity,
                subtotal
            )
         VALUES
            (?, ?, ?, ?, ?, ?)'
    );


    // Reduce inventory
    $reduceStock = $pdo->prepare(
        'UPDATE products
         SET stock = stock - ?
         WHERE id = ?'
    );


    foreach ($orderItems as $item) {

        $insertItem->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['price'],
            $item['quantity'],
            $item['subtotal']
        ]);


        $reduceStock->execute([
            $item['quantity'],
            $item['product_id']
        ]);

    }


    $pdo->commit();


    json_out([
        'ok' => true,
        'message' => 'Order placed successfully.',
        'order' => [
            'id' => $orderId,
            'total_amount' => $totalAmount,
            'status' => 'Pending'
        ]
    ], 201);


} catch (InvalidArgumentException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_out([
        'ok' => false,
        'error' => $e->getMessage()
    ], 400);


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_out([
        'ok' => false,
        'error' => 'Unable to complete checkout.'
    ], 500);
}