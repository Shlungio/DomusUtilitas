<?php
// GET /DomusUtilitas_api/admin/dashboard.php
// Returns summary information for the admin dashboard.

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

    // ==========================
    // TOTAL PRODUCTS
    // ==========================

    $stmt = $pdo->query(
        'SELECT COUNT(*)
         FROM products'
    );

    $totalProducts =
        (int)$stmt->fetchColumn();



    // ==========================
    // TOTAL CUSTOMERS
    // ==========================

    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'customer'"
    );

    $totalCustomers =
        (int)$stmt->fetchColumn();



    // ==========================
    // TOTAL ORDERS
    // ==========================

    $stmt = $pdo->query(
        'SELECT COUNT(*)
         FROM orders'
    );

    $totalOrders =
        (int)$stmt->fetchColumn();



    // ==========================
    // PENDING ORDERS
    // ==========================

    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM orders
         WHERE status = 'Pending'"
    );

    $pendingOrders =
        (int)$stmt->fetchColumn();



    // ==========================
    // LOW STOCK PRODUCTS
    // ==========================

    $stmt = $pdo->query(
        'SELECT COUNT(*)
         FROM products
         WHERE stock <= 5
           AND is_active = 1'
    );

    $lowStockCount =
        (int)$stmt->fetchColumn();



    // ==========================
    // COMPLETED SALES
    // ==========================

    $stmt = $pdo->query(
        "SELECT COALESCE(
            SUM(total_amount),
            0
         )
         FROM orders
         WHERE status = 'Completed'"
    );

    $completedSales =
        (float)$stmt->fetchColumn();



    // ==========================
    // RECENT ORDERS
    // ==========================

    $stmt = $pdo->query(
        "SELECT
            o.id,
            o.total_amount,
            o.status,
            o.created_at,
            u.username
         FROM orders o

         LEFT JOIN users u
            ON o.user_id = u.id

         ORDER BY o.created_at DESC
         LIMIT 5"
    );

    $recentOrders =
        $stmt->fetchAll();



    // ==========================
    // LOW STOCK LIST
    // ==========================

    $stmt = $pdo->query(
        'SELECT
            id,
            name,
            stock,
            image_url
         FROM products
         WHERE stock <= 5
           AND is_active = 1
         ORDER BY stock ASC, name ASC
         LIMIT 5'
    );

    $lowStockProducts =
        $stmt->fetchAll();



    // ==========================
    // RESPONSE
    // ==========================

    json_out([

        'ok' => true,

        'summary' => [

            'total_products' =>
                $totalProducts,

            'total_orders' =>
                $totalOrders,

            'total_customers' =>
                $totalCustomers,

            'pending_orders' =>
                $pendingOrders,

            'low_stock_products' =>
                $lowStockCount,

            'completed_sales' =>
                $completedSales

        ],

        'recent_orders' =>
            $recentOrders,

        'low_stock' =>
            $lowStockProducts

    ]);


} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' =>
            'Unable to load dashboard.'
    ], 500);

}