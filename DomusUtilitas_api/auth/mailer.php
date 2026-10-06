<?php

declare(strict_types=1);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require __DIR__ .
    '/../../vendor/autoload.php';


function sendOtpEmail(
    string $recipientEmail,
    string $otp
): void {

    $config =
        require __DIR__ .
        '/../mail_config.php';


    $mail =
        new PHPMailer(true);


    // SMTP SETTINGS
    $mail->isSMTP();

    $mail->Host =
        $config['host'];

    $mail->SMTPAuth =
        true;

    $mail->Username =
        $config['username'];

    $mail->Password =
        $config['password'];

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port =
        $config['port'];

    $mail->CharSet =
        'UTF-8';


    // SENDER
    $mail->setFrom(
        $config['from_email'],
        $config['from_name']
    );


    // RECEIVER
    $mail->addAddress(
        $recipientEmail
    );


    // EMAIL CONTENT
    $mail->isHTML(true);

    $mail->Subject =
        'Domus Utilitas Email Verification';


    $safeOtp =
        htmlspecialchars(
            $otp,
            ENT_QUOTES,
            'UTF-8'
        );


    $mail->Body =
        '
        <h2>Email Verification</h2>

        <p>
            Your Domus Utilitas
            verification code is:
        </p>

        <h1 style="letter-spacing: 6px;">
            ' . $safeOtp . '
        </h1>

        <p>
            This code expires in
            10 minutes.
        </p>

        <p>
            If you did not request
            this code, you can ignore
            this email.
        </p>
        ';


    $mail->AltBody =
        'Your Domus Utilitas verification code is: ' .
        $otp .
        '. This code expires in 10 minutes.';


    $mail->send();
}