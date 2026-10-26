
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/config/smtp-config.php';

$mail = new PHPMailer(true);

try {
    // SMTP server settings
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USERNAME;
    $mail->Password   = $SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $SMTP_PORT;
    $mail->Timeout    = 20;

    // Sender and test recipient
    $mail->setFrom($SMTP_FROM_EMAIL, $SMTP_FROM_NAME);

    // Send the test to your own Gmail address
    $mail->addAddress($SMTP_USERNAME);

    $mail->isHTML(true);
    $mail->Subject = 'Support Portal - SMTP Test';
    $mail->Body = '<h3>SMTP test successful!</h3>
                   <p>Your Customer Support Portal can send email.</p>';

    $mail->AltBody =
        'SMTP test successful! Your Customer Support Portal can send email.';

    $mail->send();

    echo 'SUCCESS: Test email sent. Check your Gmail inbox.';

} 
catch (Exception $e) {
    error_log('SMTP test failed: ' . $mail->ErrorInfo);

    http_response_code(500);
    echo 'FAILED: Email could not be sent. Check the PHP error log.';
}
