<?php
// POST /DomusUtilitas_api/profile/update.php
// Updates the currently logged-in customer's profile.

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

$user = current_user();
$body = get_json_body();

$fullName = trim($body['full_name'] ?? '');
$email    = trim($body['email'] ?? '');
$phone    = trim($body['phone'] ?? '');
$address  = trim($body['address'] ?? '');


// ==========================
// VALIDATION
// ==========================

if (strlen($fullName) > 100) {
    json_out([
        'ok' => false,
        'error' => 'Full name must be 100 characters or less.'
    ], 400);
}

if (strlen($email) > 150) {
    json_out([
        'ok' => false,
        'error' => 'Email must be 150 characters or less.'
    ], 400);
}

if (
    $email !== '' &&
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    json_out([
        'ok' => false,
        'error' => 'Please enter a valid email address.'
    ], 400);
}

if (strlen($phone) > 30) {
    json_out([
        'ok' => false,
        'error' => 'Phone number must be 30 characters or less.'
    ], 400);
}

if (
    $phone !== '' &&
    !preg_match('/^[0-9+\-\s().]+$/', $phone)
) {
    json_out([
        'ok' => false,
        'error' => 'Please enter a valid phone number.'
    ], 400);
}

if (strlen($address) > 255) {
    json_out([
        'ok' => false,
        'error' => 'Address must be 255 characters or less.'
    ], 400);
}


// Store empty optional fields as NULL
$fullName = $fullName === '' ? null : $fullName;
$email    = $email === '' ? null : $email;
$phone    = $phone === '' ? null : $phone;
$address  = $address === '' ? null : $address;


try {

    $stmt = $pdo->prepare(
        'UPDATE users
         SET
            full_name = ?,
            email = ?,
            phone = ?,
            address = ?
         WHERE id = ?'
    );

    $stmt->execute([
        $fullName,
        $email,
        $phone,
        $address,
        $user['id']
    ]);


    // Return the freshly updated profile
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


    json_out([
        'ok' => true,
        'message' => 'Profile updated successfully.',
        'profile' => $profile
    ]);


} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' => 'Unable to update profile.'
    ], 500);

}