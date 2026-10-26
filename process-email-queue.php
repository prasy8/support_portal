<?php

/*
 * Customer Support Portal - Email Queue Worker
 *
 * Run from the command line:
 * php process-email-queue.php
 It processes one email at a time, claims jobs transactionally, and retries failures up to the configured limit.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

include("includes/config/config.php");
include("includes/config/smtp-config.php");

$autoloadFile = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoloadFile)) {
    exit("PHPMailer is not installed. Run composer require phpmailer/phpmailer\n");
}

require_once $autoloadFile;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$emailTable   = $_SESSION[SITE]['DBPF'] . 'email_queue';
$messageTable = $_SESSION[SITE]['DBPF'] . 'ticket_messages';

echo "Email worker started.\n";

while (true) {

    $job = null;

    /*
     * Claim one pending email job.
     * The transaction prevents two workers from claiming the
     * same pending job at the same time.
     */
    try {

        mysqli_begin_transaction($conn);

        $query = "SELECT email_id, tenant_id, ticket_id, msg_id, recipient_email, subject, attempts, max_attempts
                  FROM $emailTable
                  WHERE status = 'pending' AND attempts < max_attempts
                  ORDER BY email_id ASC
                  LIMIT 1
                  FOR UPDATE";
        $result = mysqli_query($conn, $query);
        if (!$result) {
            throw new RuntimeException(mysqli_error($conn));
        }
        $job = mysqli_fetch_assoc($result);
        if(!$job) {
            mysqli_commit($conn);
            echo "No pending emails. Worker finished.\n";
            break;
        }
        $emailId = (int)$job['email_id'];

        $updateQry = "UPDATE $emailTable
                      SET status = 'processing', attempts = attempts + 1, last_attempt_at = NOW()
                      WHERE email_id = ? AND status = 'pending'";
        $stmt = mysqli_prepare($conn, $updateQry);
        mysqli_stmt_bind_param($stmt, 'i', $emailId);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new RuntimeException('Unable to claim email job.');
        }
        mysqli_stmt_close($stmt);

        // Keep the in-memory attempt count in sync.
        $job['attempts'] = (int)$job['attempts'] + 1;

        mysqli_commit($conn);

    } 
	catch (Throwable $e) {

        mysqli_rollback($conn);
        error_log('Email worker claim error: ' . $e->getMessage());
        echo "Unable to claim a job. Worker stopped.\n";
        exit(1);
    }

    /*
     * 2. Load the public ticket message.
     * Never send internal notes to customers.
     */
    try {

        $query = "SELECT message
                  FROM $messageTable
                  WHERE msg_id = ? AND tenant_id = ? AND ticket_id = ? AND is_internal = 0 LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);

        $tenantId = (int)$job['tenant_id'];
        $ticketId = (int)$job['ticket_id'];
        $messageId = (int)$job['msg_id'];

        mysqli_stmt_bind_param($stmt,'iii',$messageId,$tenantId,$ticketId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $messageRow = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if (!$messageRow) {
            throw new RuntimeException('Public ticket message not found.');
        }

        /*
         * Send email through Gmail SMTP.
         */
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = $SMTP_USERNAME;
        $mail->Password   = $SMTP_PASSWORD;
        $mail->Port       = (int)$SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        if ($SMTP_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } 
		elseif ($SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->setFrom($SMTP_FROM_EMAIL, $SMTP_FROM_NAME);
        $mail->addAddress($job['recipient_email']);
        $mail->Subject = $job['subject'];
        $messageText = (string)$messageRow['message'];

        $mail->isHTML(true);

        $mail->Body = '
            <div style="font-family:Arial,sans-serif;line-height:1.6">
                <h2>Customer Support Reply</h2>
                <p>' . nl2br(htmlspecialchars($messageText,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8')) . '</p>
                <hr>
                <p style="color:#666;font-size:12px">
                    This email was sent by the Customer Support Portal.
                </p>
            </div>';

        $mail->AltBody = $messageText;
        $mail->send();

        /*
         * Mark the job as sent.
         */
        $updateQry = "UPDATE $emailTable
                      SET status = 'sent',sent_at = NOW(),last_error = NULL
                      WHERE email_id = ? AND status = 'processing'";
        $stmt = mysqli_prepare($conn, $updateQry);
        mysqli_stmt_bind_param($stmt, 'i', $emailId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo "Email job #{$emailId} sent successfully.\n";

    } 
	catch (Throwable $e) {

        /*
         * Retry or mark as permanently failed.
         */
        $errorMessage = substr($e->getMessage(), 0, 2000);
        error_log('Email job #' . $emailId . ' failed: ' . $errorMessage);

        $attempts = (int)$job['attempts'];
        $maxAttempts = (int)$job['max_attempts'];

        $newStatus = ($attempts >= $maxAttempts) ? 'failed' : 'pending';

        $updateQry = "UPDATE $emailTable
                      SET status = ?, last_error = ?
                      WHERE email_id = ? AND status = 'processing'";
        $stmt = mysqli_prepare($conn, $updateQry);
        mysqli_stmt_bind_param($stmt,'ssi',$newStatus,$errorMessage,$emailId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo "Email job #{$emailId}: {$newStatus}.\n";
    }
}

?>
