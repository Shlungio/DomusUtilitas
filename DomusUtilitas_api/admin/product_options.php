<?php
// GET /DomusUtilitas_api/admin/product_options.php
// Returns categories and subcategories for the Add/Edit Product forms.

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
            c.id AS category_id,
            c.name AS category_name,
            s.id AS subcategory_id,
            s.name AS subcategory_name
        FROM categories c
        LEFT JOIN subcategories s
            ON s.category_id = c.id
        ORDER BY c.name, s.name'
    );

    $rows = $stmt->fetchAll();

    $categories = [];

    foreach ($rows as $row) {
        $categoryId = (int)$row['category_id'];

        if (!isset($categories[$categoryId])) {
            $categories[$categoryId] = [
                'id' => $categoryId,
                'name' => $row['category_name'],
                'subcategories' => []
            ];
        }

        if ($row['subcategory_id'] !== null) {
            $categories[$categoryId]['subcategories'][] = [
                'id' => (int)$row['subcategory_id'],
                'name' => $row['subcategory_name']
            ];
        }
    }

    json_out([
        'ok' => true,
        'categories' => array_values($categories)
    ]);

} catch (PDOException $e) {
    json_out([
        'ok' => false,
        'error' => 'Unable to load product options.'
    ], 500);
}