<?php
/**
 * Delete Category API
 * DELETE /api/categories/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require admin role
require_role(ROLE_ADMIN);

// Get JSON input (some clients send DELETE with body)
$input = get_json_input();

// Get category ID from input or query string
$category_id = isset($input['id']) ? (int)$input['id'] : (int)get_get('id', 0);

if ($category_id <= 0) {
    send_error_response('Category ID is required', HTTP_BAD_REQUEST);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in categories/delete.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Check if category exists
$stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$category) {
    send_error_response('Category not found', HTTP_NOT_FOUND);
}

// Check if category has videos (foreign key will handle this, but we can check first)
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM videos WHERE category_id = ?");
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$video_count = mysqli_fetch_assoc($result)['count'];
mysqli_stmt_close($stmt);

if ($video_count > 0) {
    send_error_response('Cannot delete category with existing videos. Please reassign or delete videos first.', HTTP_BAD_REQUEST);
}

// Delete category
$stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");

if (!$stmt) {
    log_error('Failed to prepare statement in categories/delete.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "i", $category_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in categories/delete.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_close($stmt);

// Log activity
log_activity('category_deleted', get_current_user_id(), "Deleted category: {$category['name']}", $_SERVER['REMOTE_ADDR'] ?? null);

// Return success response
send_success_response(null, 'Category deleted successfully', HTTP_OK);

