<?php
// POST /DomusUtilitas_api/auth/verify_email_otp.php
// Body: { "otp": "123456" }

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

$otp =
    trim(
        (string)($body['otp'] ?? '')
    );


// ========================================
// VALIDATE OTP FORMAT
// ========================================

if (!preg_match('/^\d{6}$/', $otp)) {

    json_out([
        'ok' => false,
        'error' =>
            'Enter the 6-digit verification code.'
    ], 400);

}


// ========================================
// GET PENDING USER
// ========================================

$userId =
    (int)(
        $_SESSION[
            'pending_verification_user_id'
        ] ?? 0
    );


if ($userId <= 0) {

    json_out([
        'ok' => false,
        'error' =>
            'No account is waiting for email verification.'
    ], 401);

}


try {

    // ========================================
    // GET USER
    // ========================================

    $stmt = $pdo->prepare(
        'SELECT
            id,
            username,
            role,
            email_verified,
            email_verification_required
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $userId
    ]);

    $user =
        $stmt->fetch();


    if (!$user) {

        json_out([
            'ok' => false,
            'error' =>
                'Account not found.'
        ], 404);

    }


    // ========================================
    // GET CURRENT OTP
    // ========================================

    $stmt = $pdo->prepare(
        'SELECT
            id,
            otp_hash,
            expires_at,
            attempts
         FROM email_otps
         WHERE user_id = ?
         ORDER BY id DESC
         LIMIT 1'
    );

    $stmt->execute([
        $userId
    ]);

    $otpRecord =
        $stmt->fetch();


    if (!$otpRecord) {

        json_out([
            'ok' => false,
            'error' =>
                'No verification code was found. Please request a new one.'
        ], 400);

    }


    // ========================================
    // CHECK ATTEMPT LIMIT
    // ========================================

    if (
        (int)$otpRecord['attempts']
        >= 5
    ) {

        json_out([
            'ok' => false,
            'error' =>
                'Too many incorrect attempts. Please request a new code.'
        ], 429);

    }


    // ========================================
    // CHECK EXPIRATION
    // ========================================

    $expiresAt =
        strtotime(
            $otpRecord['expires_at']
        );


    if (
        $expiresAt === false ||
        time() > $expiresAt
    ) {

        $stmt = $pdo->prepare(
            'DELETE FROM email_otps
             WHERE user_id = ?'
        );

        $stmt->execute([
            $userId
        ]);


        json_out([
            'ok' => false,
            'error' =>
                'The verification code has expired. Please request a new one.'
        ], 400);

    }


    // ========================================
    // VERIFY OTP
    // ========================================

    if (
        !password_verify(
            $otp,
            $otpRecord['otp_hash']
        )
    ) {

        $stmt = $pdo->prepare(
            'UPDATE email_otps
             SET attempts = attempts + 1
             WHERE id = ?'
        );

        $stmt->execute([
            $otpRecord['id']
        ]);


        $remaining =
            4 -
            (int)$otpRecord['attempts'];


        json_out([
            'ok' => false,
            'error' =>
                'Incorrect verification code. ' .
                $remaining .
                ' attempt(s) remaining.'
        ], 400);

    }


    // ========================================
    // VERIFY ACCOUNT
    // ========================================

    $pdo->beginTransaction();


    $stmt = $pdo->prepare(
        'UPDATE users
         SET
            email_verified = 1,
            email_verification_required = 0
         WHERE id = ?'
    );

    $stmt->execute([
        $userId
    ]);


    // OTP is single-use.
    $stmt = $pdo->prepare(
        'DELETE FROM email_otps
         WHERE user_id = ?'
    );

    $stmt->execute([
        $userId
    ]);


    $pdo->commit();


    // ========================================
    // CREATE NORMAL LOGIN SESSION
    // ========================================

    session_regenerate_id(true);


    $_SESSION['user_id'] =
        (int)$user['id'];

    $_SESSION['username'] =
        $user['username'];

    $_SESSION['role'] =
        $user['role'];


    unset(
        $_SESSION[
            'pending_verification_user_id'
        ],
        $_SESSION[
            'pending_verification_username'
        ],
        $_SESSION[
            'pending_verification_email'
        ]
    );


    json_out([
        'ok' => true,
        'message' =>
            'Email verified successfully.',
        'redirect' =>
            'ProductPage.html'
    ]);


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    json_out([
        'ok' => false,
        'error' =>
            'Unable to verify the email.'
    ], 500);

}