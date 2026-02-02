<?php
/**
 * Delete Video API
 * DELETE /api/videos/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Allow DELETE or POST (some setups strip DELETE body/query)
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Require authentication
require_auth();

// Get video ID: query string first (most reliable for DELETE), then JSON body
$video_id = 0;
if (isset($_GET['id']) && $_GET['id'] !== '') {
    $video_id = (int) $_GET['id'];
}
if ($video_id <= 0) {
    $input = get_json_input();
    $video_id = isset($input['id']) ? (int) $input['id'] : 0;
}
if ($video_id <= 0) {
    send_error_response('Video ID is required', HTTP_BAD_REQUEST);
}

// Get database connection
$conn = get_db_connection();
if (!$conn) {
    log_error('Database connection failed in videos/delete.php');
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

// Check if video exists and get current data
$stmt = mysqli_prepare($conn, "SELECT * FROM videos WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $video_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$video = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$video) {
    send_error_response('Video not found', HTTP_NOT_FOUND);
}

// Check permissions: admin can delete any video, instructor can only delete their own
$user_id = get_current_user_id();
$user_role = get_current_user_role();

if ($user_role !== ROLE_ADMIN && $video['uploaded_by'] != $user_id) {
    send_error_response('You can only delete your own videos', HTTP_FORBIDDEN);
}

// Delete video
$stmt = mysqli_prepare($conn, "DELETE FROM videos WHERE id = ?");

if (!$stmt) {
    log_error('Failed to prepare statement in videos/delete.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_bind_param($stmt, "i", $video_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    log_error('Failed to execute statement in videos/delete.php', ['error' => mysqli_error($conn)]);
    send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
}

mysqli_stmt_close($stmt);

// Log activity
log_activity('video_deleted', $user_id, "Deleted video: {$video['title']}", $_SERVER['REMOTE_ADDR'] ?? null);

// Return success response
send_success_response(null, 'Video deleted successfully', HTTP_OK);

