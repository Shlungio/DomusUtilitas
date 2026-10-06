<?php
// GET /DomusUtilitas_api/profile/get.php
// Returns the profile of the currently logged-in customer.

declare(strict_types=1);

require __DIR__ . '/../auth/_helpers.php';
require __DIR__ . '/../db.php';

require_role('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$user = current_user();

try {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            username,
            full_name,
            email,
            phone,
            address,
            role,
            created_at,
            updated_at
         FROM users
         WHERE id = ?'
    );

    $stmt->execute([
        $user['id']
    ]);

    $profile = $stmt->fetch();

    if (!$profile) {
        json_out([
            'ok' => false,
            'error' => 'Profile not found.'
        ], 404);
    }

    json_out([
        'ok' => true,
        'profile' => $profile
    ]);

} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' => 'Unable to load profile.'
    ], 500);

}