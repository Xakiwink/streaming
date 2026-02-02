<?php
/**
 * API response helpers
 * JSON success / error / generic
 */

require_once __DIR__ . '/../config/constants.php';

// ---------------------------------------------------------------------------
// Success / Error / Generic
// ---------------------------------------------------------------------------

function send_success_response($data = null, $message = MSG_SUCCESS, $status_code = HTTP_OK) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

function send_error_response($message = MSG_ERROR, $status_code = HTTP_BAD_REQUEST, $errors = []) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => $message];
    if (!empty($errors)) $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

function send_json_response($success, $data = null, $message = '', $status_code = HTTP_OK) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}
