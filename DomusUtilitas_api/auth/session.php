<?php
// GET /domusutilitas_api/auth/session.php
// Call this on page load from any page that needs to know who's logged in
// (e.g. to show "Hi, username" in the header, or redirect guests off admin pages).

declare(strict_types=1);

require __DIR__ . '/_helpers.php';

$user = current_user();

if (!$user) {
    json_out([
        'ok' => true,
        'logged_in' => false
    ]);
}

json_out([
    'ok' => true,
    'logged_in' => true,
    'user' => $user
]);