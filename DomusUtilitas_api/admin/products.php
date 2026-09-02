<?php
// GET /DomusUtilitas_api/admin/products.php
// Returns all products for administrators, including inactive products.
// ADMIN products


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
            p.id,
            p.name,
            p.price,
            p.stock,
            p.image_url,
            p.is_active,
            p.created_at,
            p.updated_at,
            s.id AS subcategory_id,
            s.name AS subcategory_name,
            c.id AS category_id,
            c.name AS category_name
        FROM products p
        INNER JOIN subcategories s
            ON p.subcategory_id = s.id
        INNER JOIN categories c
            ON s.category_id = c.id
        ORDER BY c.name, s.name, p.name'
    );

    json_out([
        'ok' => true,
        'products' => $stmt->fetchAll()
    ]);

} catch (PDOException $e) {
    json_out([
        'ok' => false,
        'error' => 'Unable to load products.'
    ], 500);
}