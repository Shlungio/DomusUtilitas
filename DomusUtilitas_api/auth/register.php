<?php
// POST /domusutilitas_api/auth/register.php
// Body: { "username": "...", "password": "...", "confirm_password": "..." }
// Always creates role = 'customer'.
// Admin accounts are created directly in the database.

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);
}

$body = get_json_body();

$username = trim($body['username'] ?? '');
$password = (string)($body['password'] ?? '');
$confirm  = (string)($body['confirm_password'] ?? '');

if ($username === '' || $password === '') {
    json_out([
        'ok' => false,
        'error' => 'Username and password are required.'
    ], 400);
}

if (strlen($username) < 3) {
    json_out([
        'ok' => false,
        'error' => 'Username must be at least 3 characters.'
    ], 400);
}

if (strlen($password) < 6) {
    json_out([
        'ok' => false,
        'error' => 'Password must be at least 6 characters.'
    ], 400);
}

if ($password !== $confirm) {
    json_out([
        'ok' => false,
        'error' => 'Passwords do not match.'
    ], 400);
}

// Check if the username already exists
$stmt = $pdo->prepare(
    'SELECT id FROM users WHERE username = ?'
);

$stmt->execute([$username]);

if ($stmt->fetch()) {
    json_out([
        'ok' => false,
        'error' => 'That username is already taken.'
    ], 409);
}

// Securely hash the password before storing it
$hash = password_hash($password, PASSWORD_DEFAULT);

// Create the customer account
$stmt = $pdo->prepare(
    'INSERT INTO users (username, password_hash, role)
     VALUES (?, ?, ?)'
);

$stmt->execute([
    $username,
    $hash,
    'customer'
]);

$userId = (int)$pdo->lastInsertId();

// Automatically log the user in after registration
session_regenerate_id(true);

$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;
$_SESSION['role']     = 'customer';

json_out([
    'ok' => true,
    'user' => [
        'id' => $userId,
        'username' => $username,
        'role' => 'customer'
    ]
]);

