<?php
// POST /DomusUtilitas_api/admin/edit_product.php
// Updates an existing product. Admin only.

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


$id =
    (int)($body['id'] ?? 0);

$name =
    trim($body['name'] ?? '');

$brand =
    trim($body['brand'] ?? '');

$model =
    trim($body['model'] ?? '');

$description =
    trim($body['description'] ?? '');

$specifications =
    trim($body['specifications'] ?? '');

$subcategoryId =
    (int)($body['subcategory_id'] ?? 0);

$price =
    $body['price'] ?? null;

$stock =
    $body['stock'] ?? null;

$imageUrl =
    trim($body['image_url'] ?? '');



// ==========================
// VALIDATION
// ==========================

if ($id <= 0) {

    json_out([
        'ok' => false,
        'error' => 'Invalid product ID.'
    ], 400);

}


if ($name === '') {

    json_out([
        'ok' => false,
        'error' => 'Product name is required.'
    ], 400);

}


if (strlen($name) > 150) {

    json_out([
        'ok' => false,
        'error' => 'Product name is too long.'
    ], 400);

}


if (strlen($brand) > 100) {

    json_out([
        'ok' => false,
        'error' => 'Brand is too long.'
    ], 400);

}


if (strlen($model) > 150) {

    json_out([
        'ok' => false,
        'error' => 'Model is too long.'
    ], 400);

}


if ($subcategoryId <= 0) {

    json_out([
        'ok' => false,
        'error' => 'Please select a subcategory.'
    ], 400);

}


if (
    !is_numeric($price) ||
    (float)$price < 0
) {

    json_out([
        'ok' => false,
        'error' => 'Enter a valid product price.'
    ], 400);

}


if (
    filter_var(
        $stock,
        FILTER_VALIDATE_INT
    ) === false ||
    (int)$stock < 0
) {

    json_out([
        'ok' => false,
        'error' =>
            'Stock must be a whole number of 0 or greater.'
    ], 400);

}


if (strlen($imageUrl) > 500) {

    json_out([
        'ok' => false,
        'error' => 'Image URL is too long.'
    ], 400);

}



// ==========================
// DATABASE
// ==========================

try {

    // ==========================
    // CHECK PRODUCT EXISTS
    // ==========================

    $stmt = $pdo->prepare(
    'SELECT
        id,
        name,
        stock
     FROM products
     WHERE id = ?'
    );


    $stmt->execute([
        $id
    ]);


    $existingProduct =
        $stmt->fetch();


    if (!$existingProduct) {

        json_out([
            'ok' => false,
            'error' => 'Product not found.'
        ], 404);

    }


    // ==========================
    // CHECK SUBCATEGORY EXISTS
    // ==========================

    $stmt = $pdo->prepare(
        'SELECT id
         FROM subcategories
         WHERE id = ?'
    );


    $stmt->execute([
        $subcategoryId
    ]);


    if (!$stmt->fetch()) {

        json_out([
            'ok' => false,
            'error' =>
                'Selected subcategory does not exist.'
        ], 400);

    }



    // ==========================
    // UPDATE PRODUCT
    // ==========================

    $stmt = $pdo->prepare(
        'UPDATE products
         SET
            subcategory_id = ?,
            name = ?,
            brand = ?,
            model = ?,
            description = ?,
            specifications = ?,
            price = ?,
            stock = ?,
            image_url = ?
         WHERE id = ?'
    );


    $stmt->execute([

        $subcategoryId,

        $name,

        $brand !== ''
            ? $brand
            : null,

        $model !== ''
            ? $model
            : null,

        $description !== ''
            ? $description
            : null,

        $specifications !== ''
            ? $specifications
            : null,

        (float)$price,

        (int)$stock,

        $imageUrl !== ''
            ? $imageUrl
            : null,

        $id

    ]);

    // ========================================
    // AUDIT LOG
    // ========================================

    $details =
        'Updated product "' .
        $name .
        '".';


    if (
        (int)$existingProduct['stock']
        !==
        (int)$stock
    ) {

        $details .=
            ' Stock: ' .
            (int)$existingProduct['stock'] .
            ' → ' .
            (int)$stock .
            '.';

    }


    write_audit(
        $pdo,
        'PRODUCT_UPDATED',
        'product',
        $id,
        $details
    );

    json_out([
        'ok' => true,
        'message' =>
            'Product updated successfully.'
    ]);


} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' =>
            'Unable to update product.'
    ], 500);

}