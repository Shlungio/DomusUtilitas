<?php
// POST /futuratech_api/auth/login.php
// Body: { "username": "...", "password": "..." }
// Works for both roles — the frontend decides where to redirect based on the
// returned "role" (e.g. role === 'admin' -> AdminDashboard.html, else -> HomePage.html).

declare(strict_types=1);
require __DIR__ . '/_helpers.php';
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$body = get_json_body();
$username = trim($body['username'] ?? '');
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
    json_out(['ok' => false, 'error' => 'Username and password are required.'], 400);
}

$stmt = $pdo->prepare('SELECT
    id,
    username,
    email,
    password_hash,
    role,
    email_verified,
    email_verification_required
FROM users
WHERE username = ?
LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Same error for "no such user" and "wrong password" — don't leak which one it was.
    json_out(['ok' => false, 'error' => 'Invalid username or password.'], 401);
}

if (
    $user['role'] === 'customer' &&
    (int)$user['email_verification_required'] === 1 &&
    (int)$user['email_verified'] === 0
) {

    session_regenerate_id(true);

    unset(
        $_SESSION['user_id'],
        $_SESSION['username'],
        $_SESSION['role']
    );

    $_SESSION['pending_verification_user_id'] =
        (int)$user['id'];

    $_SESSION['pending_verification_username'] =
        $user['username'];

    $_SESSION['pending_verification_email'] =
        $user['email'];

    json_out([
        'ok' => true,
        'verification_required' => true,
        'redirect' => 'VerifyEmailPage.html'
    ]);
}

session_regenerate_id(true); // prevent session fixation on login
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

json_out([
    'ok' => true,
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ],
]);
