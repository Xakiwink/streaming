<?php
/**
 * Create Video API
 * POST /api/videos/create.php
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Wrap in try-catch to handle any unexpected errors
try {
    // Require authentication - only instructors and admins can create videos
    require_role([ROLE_INSTRUCTOR, ROLE_ADMIN]);

    // Get JSON input
    $input = get_json_input();

    // Validate required fields
    $title = isset($input['title']) ? sanitize_string($input['title']) : '';
    $description = isset($input['description']) ? sanitize_string($input['description']) : '';
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : null;
    $video_url = isset($input['video_url']) ? sanitize_string($input['video_url']) : '';
    $thumbnail_url = isset($input['thumbnail_url']) ? trim((string) $input['thumbnail_url']) : null;
    if ($thumbnail_url !== null && $thumbnail_url === '') {
        $thumbnail_url = null;
    }
    if ($thumbnail_url !== null && strlen($thumbnail_url) > 500) {
        ob_clean();
        send_error_response('Thumbnail URL must be less than 500 characters', HTTP_BAD_REQUEST);
    }

    // Validate title
    $title_validation = validate_video_title($title);
    if (!$title_validation['valid']) {
        ob_clean();
        send_error_response($title_validation['error'], HTTP_BAD_REQUEST);
    }

    // Validate video URL (only if provided, can be from upload)
    if (!empty($video_url)) {
        $url_validation = validate_video_url($video_url);
        if (!$url_validation['valid']) {
            ob_clean();
            send_error_response($url_validation['error'], HTTP_BAD_REQUEST);
        }
    } else {
        ob_clean();
        send_error_response('Video URL is required', HTTP_BAD_REQUEST);
    }

    // Validate category if provided
    if ($category_id !== null && $category_id > 0) {
        $conn = get_db_connection();
        if (!$conn) {
            $error_msg = mysqli_connect_error() ?: 'Unknown database error';
            log_error('Database connection failed in videos/create.php', ['error' => $error_msg]);
            ob_clean();
            send_error_response('Database connection failed: ' . $error_msg, HTTP_INTERNAL_ERROR);
        }
        
        $stmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            mysqli_stmt_close($stmt);
            ob_clean();
            send_error_response('Category not found', HTTP_BAD_REQUEST);
        }
        mysqli_stmt_close($stmt);
    }

    // Get database connection
    $conn = get_db_connection();
    if (!$conn) {
        $error_msg = mysqli_connect_error() ?: 'Unknown database error';
        log_error('Database connection failed in videos/create.php', ['error' => $error_msg]);
        ob_clean();
        send_error_response('Database connection failed: ' . $error_msg, HTTP_INTERNAL_ERROR);
    }

    $user_id = get_current_user_id();

    // Insert video
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO videos (title, description, category_id, video_url, thumbnail_url, uploaded_by) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        log_error('Failed to prepare statement in videos/create.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    mysqli_stmt_bind_param($stmt, "ssissi", $title, $description, $category_id, $video_url, $thumbnail_url, $user_id);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        log_error('Failed to execute statement in videos/create.php', ['error' => $error]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    $video_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Log activity
    log_activity('video_created', $user_id, "Created video: $title", $_SERVER['REMOTE_ADDR'] ?? null);

    // Get created video
    $stmt = mysqli_prepare($conn, 
        "SELECT v.id, v.title, v.description, v.category_id, c.name as category_name,
         v.video_url, v.thumbnail_url, v.uploaded_by, u.username as uploaded_by_username,
         v.created_at, v.updated_at
         FROM videos v
         LEFT JOIN categories c ON v.category_id = c.id
         LEFT JOIN users u ON v.uploaded_by = u.id
         WHERE v.id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $video_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $video = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Clear output buffer before sending response
    ob_clean();

    // Return success response
    send_success_response($video, 'Video created successfully', HTTP_CREATED);
    
} catch (Exception $e) {
    log_error('Unexpected error in videos/create.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('An unexpected error occurred. Please try again later.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Fatal error in videos/create.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('A system error occurred. Please contact support.', HTTP_INTERNAL_ERROR);
}

