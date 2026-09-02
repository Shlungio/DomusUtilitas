<?php
// GET /DomusUtilitas_api/products.php
// Returns all active products with their category and subcategory names.

declare(strict_types=1);

require __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare(
        'SELECT
            p.id,
            p.name,
            p.price,
            p.stock,
            p.image_url,
            c.name AS category_name,
            s.name AS subcategory_name
        FROM products p
        INNER JOIN subcategories s
            ON p.subcategory_id = s.id
        INNER JOIN categories c
            ON s.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY c.name, s.name, p.name'
    );

    $stmt->execute();

    $products = $stmt->fetchAll();

    echo json_encode([
        'ok' => true,
        'products' => $products
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => 'Unable to load products.'
    ]);
}