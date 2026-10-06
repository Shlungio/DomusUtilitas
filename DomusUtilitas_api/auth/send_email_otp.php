<?php
// POST /DomusUtilitas_api/auth/send_email_otp.php
// Generates and emails a verification OTP.

declare(strict_types=1);

require __DIR__ . '/_helpers.php';
require __DIR__ . '/../db.php';
require __DIR__ . '/mailer.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    json_out([
        'ok' => false,
        'error' => 'Method not allowed.'
    ], 405);

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
    // LOAD USER
    // ========================================

    $stmt = $pdo->prepare(
        'SELECT
            id,
            username,
            email,
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


    if (
        (int)$user[
            'email_verified'
        ] === 1
    ) {

        json_out([
            'ok' => false,
            'error' =>
                'This email is already verified.'
        ], 400);

    }


    if (
        empty($user['email'])
    ) {

        json_out([
            'ok' => false,
            'error' =>
                'This account has no email address.'
        ], 400);

    }



   // ========================================
// RESEND COOLDOWN - 15 SECONDS
// ========================================

$stmt = $pdo->prepare(
    'SELECT
        GREATEST(
            0,
            15 - TIMESTAMPDIFF(
                SECOND,
                created_at,
                NOW()
            )
        ) AS retry_after
     FROM email_otps
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 1'
);

$stmt->execute([
    $userId
]);

$lastOtp = $stmt->fetch();


if ($lastOtp) {

    $retryAfter =
        (int)$lastOtp['retry_after'];


    if ($retryAfter > 0) {

        json_out([
            'ok' => false,
            'error' =>
                'Please wait before requesting another verification code.',
            'retry_after' =>
                $retryAfter
        ], 429);

    }

}


    // ========================================
    // REMOVE OLD OTP
    // ========================================

    $stmt = $pdo->prepare(
        'DELETE FROM email_otps
         WHERE user_id = ?'
    );

    $stmt->execute([
        $userId
    ]);



    // ========================================
    // GENERATE OTP
    // ========================================

    $otp =
        (string)random_int(
            100000,
            999999
        );


    $otpHash =
        password_hash(
            $otp,
            PASSWORD_DEFAULT
        );



    // ========================================
    // STORE OTP
    // ========================================

    $stmt = $pdo->prepare(
        'INSERT INTO email_otps
        (
            user_id,
            otp_hash,
            expires_at,
            attempts
        )
        VALUES
        (
            ?,
            ?,
            DATE_ADD(
                NOW(),
                INTERVAL 10 MINUTE
            ),
            0
        )'
    );


    $stmt->execute([
        $userId,
        $otpHash
    ]);



    // ========================================
    // SEND EMAIL
    // ========================================

    try {

        sendOtpEmail(
            $user['email'],
            $otp
        );

    } catch (Throwable $e) {

        // Remove unusable OTP if
        // the email could not be sent.

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
                'Unable to send the verification email.'
        ], 500);

    }



   json_out([
    'ok' => true,
    'message' =>
        'Verification code sent successfully.',
    'expires_in' =>
        600,
    'resend_after' =>
        15
]);

} catch (PDOException $e) {

    json_out([
        'ok' => false,
        'error' =>
            'Unable to create verification code.'
    ], 500);

}