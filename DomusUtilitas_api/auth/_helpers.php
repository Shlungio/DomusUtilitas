<?php
// _helpers.php — shared by every file in /auth/. Starts the session and
// gives every endpoint a consistent way to send JSON back to the frontend.

declare(strict_types=1);

session_start();
header('Content-Type: application/json');

function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function current_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
    ];
}

function require_role(string $role): void {
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        json_out(['ok' => false, 'error' => 'Unauthorized.'], 401);
    }
}
