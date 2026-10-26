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
?>