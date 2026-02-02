<?php
/**
 * Get Single Category API
 * GET /api/categories/get.php?id={category_id}
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require authentication
require_auth();

// Get category ID
$category_id = get_get('id', null);

if ($category_id === null || !is_numeric($category_id)) {
    send_error_response('Category ID is required', HTTP_BAD_REQUEST);
}

$category_id = (int)$category_id;

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in categories/get.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Get category
$stmt = mysqli_prepare($conn, 
    "SELECT c.*, COUNT(v.id) as video_count 
     FROM categories c
     LEFT JOIN videos v ON c.id = v.category_id
     WHERE c.id = ?
     GROUP BY c.id"
);

if (!$stmt) {
    log_error('Failed to prepare statement in categories/get.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$category) {
    send_error_response('Category not found', HTTP_NOT_FOUND);
}

$category['video_count'] = (int)$category['video_count'];

// Return success response
send_success_response($category, 'Category retrieved successfully', HTTP_OK);

