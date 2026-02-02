<?php
/**
 * List Categories API
 * GET /api/categories/list.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/response.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require authentication
require_auth();

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in categories/list.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Get all categories
$query = "SELECT c.*, COUNT(v.id) as video_count 
          FROM categories c
          LEFT JOIN videos v ON c.id = v.category_id
          GROUP BY c.id
          ORDER BY c.name ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    log_error('Failed to query categories in categories/list.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['video_count'] = (int)$row['video_count'];
    $categories[] = $row;
}

// Return success response
send_success_response($categories, 'Categories retrieved successfully', HTTP_OK);

