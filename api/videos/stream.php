<?php
/**
 * Stream Video by ID (authenticated)
 * GET /api/videos/stream.php?id={video_id}
 *
 * Serves the video file so the app can play it with session cookies.
 * - Local paths (/streaming/uploads/videos/...) → stream from uploads/videos/
 * - External http(s) URLs → 302 redirect (for real external links)
 *
 * Does not use api-init.php so we can send video Content-Type instead of JSON.
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    send_error_response('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

try {
    require_auth();

    $video_id = get_get('id', null);
    if ($video_id === null || !is_numeric($video_id)) {
        ob_clean();
        send_error_response('Video ID is required', HTTP_BAD_REQUEST);
    }
    $video_id = (int)$video_id;

    $conn = get_db_connection();
    if (!$conn) {
        log_error('Database connection failed in videos/stream.php', ['error' => mysqli_connect_error() ?: 'Unknown']);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    $stmt = mysqli_prepare($conn, "SELECT id, title, video_url FROM videos WHERE id = ?");
    if (!$stmt) {
        log_error('Failed to prepare statement in videos/stream.php', ['error' => mysqli_error($conn)]);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }
    mysqli_stmt_bind_param($stmt, 'i', $video_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $video = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$video) {
        ob_clean();
        send_error_response('Video not found', HTTP_NOT_FOUND);
    }

    $video_url = trim($video['video_url'] ?? '');

    // External URL: redirect so the client (e.g. ExoPlayer) can request it
    if (preg_match('#^https?://#i', $video_url)) {
        ob_clean();
        header('Location: ' . $video_url, true, 302);
        exit;
    }

    // Local path: e.g. /streaming/uploads/videos/filename.mp4 → uploads/videos/filename.mp4
    $base_dir = realpath(__DIR__ . '/../../uploads/videos');
    if ($base_dir === false || !is_dir($base_dir)) {
        log_error('Uploads videos dir missing in stream.php', ['base' => __DIR__ . '/../../uploads/videos']);
        ob_clean();
        send_error_response('Video file not available', HTTP_NOT_FOUND);
    }

    $filename = basename($video_url);
    if ($filename === '' || preg_match('#[./\\\\]#', $filename)) {
        ob_clean();
        send_error_response('Invalid video path', HTTP_BAD_REQUEST);
    }
    $file_path = $base_dir . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($file_path)) {
        log_error('Video file not found on disk', ['path' => $file_path, 'video_id' => $video_id]);
        ob_clean();
        send_error_response('Video not found', HTTP_NOT_FOUND);
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime = 'video/mp4';
    if ($ext === 'webm') $mime = 'video/webm';
    elseif ($ext === 'ogg' || $ext === 'ogv') $mime = 'video/ogg';

    $size = filesize($file_path);

    // Support Range for seeking
    $range = $_SERVER['HTTP_RANGE'] ?? '';
    if ($range !== '' && preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
        $start = (int)$m[1];
        $end = $m[2] === '' ? $size - 1 : min((int)$m[2], $size - 1);
        $length = $end - $start + 1;
        ob_clean();
        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $length);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        http_response_code(206);
        $fp = fopen($file_path, 'rb');
        if ($fp) {
            fseek($fp, $start);
            $buf_size = 8192;
            while ($length > 0 && !feof($fp)) {
                $read = min($buf_size, $length);
                echo fread($fp, $read);
                $length -= $read;
            }
            fclose($fp);
        }
        exit;
    }

    ob_clean();
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $size);
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
    http_response_code(200);
    readfile($file_path);
    exit;

} catch (Exception $e) {
    log_error('Unexpected error in videos/stream.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('An unexpected error occurred.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Fatal error in videos/stream.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('A system error occurred.', HTTP_INTERNAL_ERROR);
}
