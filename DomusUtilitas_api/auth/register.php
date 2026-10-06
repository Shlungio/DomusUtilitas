<?php
// POST /DomusUtilitas_api/auth/register.php
// Body:
// {
//   "username": "...",
//   "email": "...",
//   "password": "...",
//   "confirm_password": "..."
// }
//
// Always creates role = customer.
// New customer accounts require email verification.

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


$username =
    trim($body['username'] ?? '');

$email =
    strtolower(
        trim($body['email'] ?? '')
    );

$password =
    (string)($body['password'] ?? '');

$confirm =
    (string)($body['confirm_password'] ?? '');



// ========================================
// VALIDATION
// ========================================

if (
    $username === '' ||
    $email === '' ||
    $password === ''
) {

    json_out([
        'ok' => false,
        'error' =>
            'Username, email, and password are required.'
    ], 400);

}


if (strlen($username) < 3) {

    json_out([
        'ok' => false,
        'error' =>
            'Username must be at least 3 characters.'
    ], 400);

}


if (strlen($username) > 50) {

    json_out([
        'ok' => false,
        'error' =>
            'Username is too long.'
    ], 400);

}


if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    json_out([
        'ok' => false,
        'error' =>
            'Enter a valid email address.'
    ], 400);

}


if (strlen($email) > 150) {

    json_out([
        'ok' => false,
        'error' =>
            'Email address is too long.'
    ], 400);

}


if (strlen($password) < 6) {

    json_out([
        'ok' => false,
        'error' =>
            'Password must be at least 6 characters.'
    ], 400);

}


if ($password !== $confirm) {

    json_out([
        'ok' => false,
        'error' =>
            'Passwords do not match.'
    ], 400);

}



// ========================================
// CHECK USERNAME
// ========================================

$stmt = $pdo->prepare(
    'SELECT id
     FROM users
     WHERE username = ?
     LIMIT 1'
);


$stmt->execute([
    $username
]);


if ($stmt->fetch()) {

    json_out([
        'ok' => false,
        'error' =>
            'That username is already taken.'
    ], 409);

}



// ========================================
// CHECK EMAIL
// ========================================

$stmt = $pdo->prepare(
    'SELECT id
     FROM users
     WHERE email = ?
     LIMIT 1'
);


$stmt->execute([
    $email
]);


if ($stmt->fetch()) {

    json_out([
        'ok' => false,
        'error' =>
            'That email is already registered.'
    ], 409);

}



// ========================================
// CREATE ACCOUNT
// ========================================

$hash =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


try {

    $stmt = $pdo->prepare(
        'INSERT INTO users
        (
            username,
            email,
            password_hash,
            role,
            email_verified,
            email_verification_required
        )
        VALUES
        (?, ?, ?, ?, 0, 1)'
    );


    $stmt->execute([
        $username,
        $email,
        $hash,
        'customer'
    ]);


    $userId =
        (int)$pdo->lastInsertId();



    // ========================================
    // PENDING VERIFICATION SESSION
    // ========================================

    session_regenerate_id(true);


    // Do NOT create the normal logged-in
    // customer session yet.

    unset(
        $_SESSION['user_id'],
        $_SESSION['username'],
        $_SESSION['role']
    );


    $_SESSION[
        'pending_verification_user_id'
    ] = $userId;


    $_SESSION[
        'pending_verification_username'
    ] = $username;


    $_SESSION[
        'pending_verification_email'
    ] = $email;



    json_out([
        'ok' => true,

        'verification_required' =>
            true,

        'redirect' =>
            'VerifyEmailPage.html'
    ], 201);


} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' =>
            'Unable to create account.'
    ], 500);

}