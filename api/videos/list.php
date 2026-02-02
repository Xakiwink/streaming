<?php
/**
 * List Videos API
 * GET /api/videos/list.php
 */

require_once __DIR__ . '/../../includes/api-init.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

// Wrap in try-catch to handle any unexpected errors
try {
    // Require authentication
    require_auth();

    // Get query parameters
    $category_id = get_get('category_id', null);
    $page = max(1, (int)get_get('page', 1));
    $limit = max(1, min(100, (int)get_get('limit', DEFAULT_PAGE_SIZE)));
    $offset = ($page - 1) * $limit;

    // Get database connection
    $conn = get_db_connection();
    if (!$conn) {
        $error_msg = mysqli_connect_error() ?: 'Unknown database error';
        log_error('Database connection failed in videos/list.php', ['error' => $error_msg]);
        ob_clean();
        send_error_response('Database connection failed: ' . $error_msg, HTTP_INTERNAL_ERROR);
    }

    // Build query
    $query = "SELECT v.id, v.title, v.description, v.category_id, c.name as category_name, 
              v.video_url, v.thumbnail_url, v.uploaded_by, u.username as uploaded_by_username,
              v.created_at, v.updated_at
              FROM videos v
              LEFT JOIN categories c ON v.category_id = c.id
              LEFT JOIN users u ON v.uploaded_by = u.id
              WHERE 1=1";

    $params = [];
    $types = '';

    if ($category_id !== null) {
        $category_id = (int)$category_id;
        $query .= " AND v.category_id = ?";
        $params[] = $category_id;
        $types .= 'i';
    }

    $query .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Execute query
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        log_error('Failed to prepare statement in videos/list.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $videos = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $videos[] = $row;
    }

    mysqli_stmt_close($stmt);

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM videos";
    if ($category_id !== null) {
        $count_query .= " WHERE category_id = " . (int)$category_id;
    }
    $count_result = mysqli_query($conn, $count_query);
    $total = mysqli_fetch_assoc($count_result)['total'];

    // Clear output buffer before sending response
    ob_clean();

    // Return success response
    send_success_response([
        'videos' => $videos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ], 'Videos retrieved successfully', HTTP_OK);
    
} catch (Exception $e) {
    log_error('Unexpected error in videos/list.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('An unexpected error occurred. Please try again later.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Fatal error in videos/list.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_clean();
    send_error_response('A system error occurred. Please contact support.', HTTP_INTERNAL_ERROR);
}

