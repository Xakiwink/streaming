<?php
/**
 * Check Authentication Status API
 * GET /api/auth/check.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/response.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Check if user is logged in
if (is_logged_in()) {
    $user = get_logged_in_user();
    send_success_response($user, 'User is authenticated', HTTP_OK);
} else {
    send_error_response('Not authenticated', HTTP_UNAUTHORIZED);
}

