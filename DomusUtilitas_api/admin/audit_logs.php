<?php
// GET /DomusUtilitas_api/admin/audit_logs.php
// Returns the latest admin audit logs.

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
            id,
            admin_id,
            admin_username,
            action,
            entity_type,
            entity_id,
            details,
            created_at
         FROM audit_logs
         ORDER BY created_at DESC, id DESC
         LIMIT 200'
    );


    json_out([
        'ok' => true,
        'logs' => $stmt->fetchAll()
    ]);


} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' =>
            'Unable to load audit logs.'
    ], 500);

}