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


// ==========================
// BUILD CLEAN PRODUCT LIST
// ==========================

$requested = [];

foreach ($items as $item) {

    $productId =
        (int)($item['id'] ?? 0);

    $quantityRaw =
        $item['qty'] ?? null;


    if (
        $productId <= 0 ||
        filter_var(
            $quantityRaw,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$quantityRaw <= 0
    ) {

        json_out([
            'ok' => false,
            'error' => 'Invalid cart data.'
        ], 400);

    }


    $quantity =
        (int)$quantityRaw;


    // Combine duplicate product entries
    if (isset($requested[$productId])) {

        $requested[$productId] +=
            $quantity;

    } else {

        $requested[$productId] =
            $quantity;

    }

}


$user = current_user();


try {

    $pdo->beginTransaction();


    // ==========================
    // GET DELIVERY PROFILE
    // ==========================

    $stmt = $pdo->prepare(
        'SELECT
            full_name,
            phone,
            address
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $user['id']
    ]);

    $profile =
        $stmt->fetch();


    if (!$profile) {

        throw new InvalidArgumentException(
            'Customer profile not found.'
        );

    }


    $deliveryName =
        trim(
            (string)($profile['full_name'] ?? '')
        );

    $deliveryPhone =
        trim(
            (string)($profile['phone'] ?? '')
        );

    $deliveryAddress =
        trim(
            (string)($profile['address'] ?? '')
        );


    // Require delivery information
    if (
        $deliveryName === '' ||
        $deliveryPhone === '' ||
        $deliveryAddress === ''
    ) {

        throw new InvalidArgumentException(
            'Please complete your full name, phone number, and delivery address in your profile before checking out.'
        );

    }



    // ==========================
    // LOAD AND LOCK PRODUCTS
    // ==========================

    $productIds =
        array_keys($requested);


    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($productIds),
                '?'
            )
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


    $stmt->execute(
        $productIds
    );


    $rows =
        $stmt->fetchAll();


    $products = [];

    foreach ($rows as $row) {

        $products[
            (int)$row['id']
        ] = $row;

    }



    // ==========================
    // VALIDATE PRODUCTS
    // ==========================

    $orderItems = [];
    $totalAmount = 0.00;


    foreach (
        $requested
        as $productId => $quantity
    ) {

        if (
            !isset(
                $products[$productId]
            )
        ) {

            throw new InvalidArgumentException(
                'One of the products no longer exists.'
            );

        }


        $product =
            $products[$productId];


        if (
            (int)$product['is_active'] !== 1
        ) {

            throw new InvalidArgumentException(
                $product['name'] .
                ' is no longer available.'
            );

        }


        if (
            (int)$product['stock'] <
            $quantity
        ) {

            throw new InvalidArgumentException(
                'Not enough stock available for ' .
                $product['name'] . '.'
            );

        }


        $price =
            (float)$product['price'];


        $subtotal =
            round(
                $price * $quantity,
                2
            );


        $totalAmount =
            round(
                $totalAmount + $subtotal,
                2
            );


        $orderItems[] = [

            'product_id' =>
                $productId,

            'product_name' =>
                $product['name'],

            'price' =>
                $price,

            'quantity' =>
                $quantity,

            'subtotal' =>
                $subtotal

        ];

    }



    // ==========================
    // CREATE ORDER
    // ==========================

    $stmt = $pdo->prepare(
        'INSERT INTO orders
        (
            user_id,
            total_amount,
            status,
            delivery_name,
            delivery_phone,
            delivery_address
        )
        VALUES
        (?, ?, ?, ?, ?, ?)'
    );


    $stmt->execute([

        $user['id'],

        $totalAmount,

        'Pending',

        $deliveryName,

        $deliveryPhone,

        $deliveryAddress

    ]);


    $orderId =
        (int)$pdo->lastInsertId();



    // ==========================
    // INSERT ORDER ITEMS
    // ==========================

    $insertItem =
        $pdo->prepare(
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



    // ==========================
    // REDUCE INVENTORY
    // ==========================

    $reduceStock =
        $pdo->prepare(
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



    // ==========================
    // FINISH TRANSACTION
    // ==========================

    $pdo->commit();



    json_out([

        'ok' => true,

        'message' =>
            'Order placed successfully.',

        'order' => [

            'id' =>
                $orderId,

            'total_amount' =>
                $totalAmount,

            'status' =>
                'Pending',

            'delivery_name' =>
                $deliveryName,

            'delivery_phone' =>
                $deliveryPhone,

            'delivery_address' =>
                $deliveryAddress

        ]

    ], 201);



} catch (InvalidArgumentException $e) {


    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    json_out([

        'ok' => false,

        'error' =>
            $e->getMessage()

    ], 400);



} catch (Throwable $e) {


    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    json_out([

        'ok' => false,

        'error' =>
            'Unable to complete checkout.'

    ], 500);

}