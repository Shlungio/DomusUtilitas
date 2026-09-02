<?php
// POST /DomusUtilitas_api/admin/add_product.php
// Adds a new product. Admin only.

declare(strict_types=1);

require __DIR__ . '/../auth/_helpers.php';
require __DIR__ . '/../db.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$body = get_json_body();

$name = trim($body['name'] ?? '');
$subcategoryId = (int)($body['subcategory_id'] ?? 0);
$price = $body['price'] ?? null;
$stock = $body['stock'] ?? null;
$imageUrl = trim($body['image_url'] ?? '');

if ($name === '') {
    json_out([
        'ok' => false,
        'error' => 'Product name is required.'
    ], 400);
}

if ($subcategoryId <= 0) {
    json_out([
        'ok' => false,
        'error' => 'Please select a subcategory.'
    ], 400);
}

if (!is_numeric($price) || (float)$price < 0) {
    json_out([
        'ok' => false,
        'error' => 'Enter a valid product price.'
    ], 400);
}

if (
    filter_var($stock, FILTER_VALIDATE_INT) === false ||
    (int)$stock < 0
) {
    json_out([
        'ok' => false,
        'error' => 'Stock must be a whole number of 0 or greater.'
    ], 400);
}

if (strlen($name) > 150) {
    json_out([
        'ok' => false,
        'error' => 'Product name is too long.'
    ], 400);
}

if (strlen($imageUrl) > 500) {
    json_out([
        'ok' => false,
        'error' => 'Image URL is too long.'
    ], 400);
}

try {

    // Make sure the selected subcategory actually exists.
    $stmt = $pdo->prepare(
        'SELECT id FROM subcategories WHERE id = ?'
    );

    $stmt->execute([$subcategoryId]);

    if (!$stmt->fetch()) {
        json_out([
            'ok' => false,
            'error' => 'Selected subcategory does not exist.'
        ], 400);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO products
            (subcategory_id, name, price, stock, image_url, is_active)
         VALUES
            (?, ?, ?, ?, ?, 1)'
    );

    $stmt->execute([
        $subcategoryId,
        $name,
        (float)$price,
        (int)$stock,
        $imageUrl !== '' ? $imageUrl : null
    ]);

    $productId = (int)$pdo->lastInsertId();

    json_out([
        'ok' => true,
        'message' => 'Product added successfully.',
        'product_id' => $productId
    ], 201);

} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' => 'Unable to add product.'
    ], 500);
}