<?php
function jsonResponse($success, $message, $data = [])
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| JSON response helper
|--------------------------------------------------------------------------
*/
function aiJsonResponse($success, $message, $data = [], $httpCode = 200)
{
	http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Add a customer notification to the email queue.
 *
 * This function queues an email; it does not send it.
 */
function queueCustomerReplyEmail($conn,$tenantId,$ticketId,$messageId,$recipientEmail,$subject)
{
    $emailTable = $_SESSION[SITE]['DBPF'].'email_queue';
	//It uses INSERT IGNORE so a duplicate (tenant_id, msg_id) does not create a second notification job.
    $query = "INSERT IGNORE INTO $emailTable (tenant_id, ticket_id, msg_id, recipient_email, subject, status)
              VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log('Email queue prepare failed: ' . mysqli_error($conn));
        return false;
    }
    mysqli_stmt_bind_param($stmt,'iiiss',$tenantId,$ticketId,$messageId,$recipientEmail,$subject);
    $success = mysqli_stmt_execute($stmt);
    if (!$success) {
        error_log('Email queue insert failed: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    return $success;
}
?>