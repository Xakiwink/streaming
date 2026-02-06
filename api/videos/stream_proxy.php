<?php
/**
 * Stream proxy for YouTube/Vimeo → direct stream for ExoPlayer
 * GET /api/videos/stream_proxy.php?id={video_id}
 *
 * Requires auth. Loads video by id; if video_url is YouTube or Vimeo, tries to resolve
 * to a direct stream URL (via yt-dlp if available) and redirects. Otherwise redirects
 * to the original URL. ExoPlayer can then play the direct stream when available.
 *
 * Server: install yt-dlp for YouTube/Vimeo resolution (e.g. apt install yt-dlp).
 * If yt-dlp is not available, redirects to original URL (app will fall back to WebView).
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
        log_error('Database connection failed in stream_proxy.php', ['error' => mysqli_connect_error() ?: 'Unknown']);
        ob_clean();
        send_error_response(MSG_DATABASE_ERROR, HTTP_INTERNAL_ERROR);
    }

    $stmt = mysqli_prepare($conn, "SELECT id, video_url FROM videos WHERE id = ?");
    if (!$stmt) {
        log_error('Failed to prepare statement in stream_proxy.php', ['error' => mysqli_error($conn)]);
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
    if ($video_url === '' || !preg_match('#^https?://#i', $video_url)) {
        ob_clean();
        send_error_response('Invalid video URL', HTTP_BAD_REQUEST);
    }

    $is_youtube = (stripos($video_url, 'youtube.com') !== false || stripos($video_url, 'youtu.be') !== false);
    $is_vimeo = (stripos($video_url, 'vimeo.com') !== false);

    if ($is_youtube || $is_vimeo) {
        $escaped_url = escapeshellarg($video_url);
        $cmd = 'yt-dlp -g -f "best[ext=mp4]/best" --no-warnings --no-playlist ' . $escaped_url . ' 2>/dev/null';
        $stream_url = trim((string)shell_exec($cmd));
        if ($stream_url !== '' && preg_match('#^https?://#i', $stream_url)) {
            ob_clean();
            header('Location: ' . $stream_url, true, 302);
            exit;
        }
    }

    ob_clean();
    header('Location: ' . $video_url, true, 302);
    exit;

} catch (Exception $e) {
    log_error('Unexpected error in stream_proxy.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('An unexpected error occurred.', HTTP_INTERNAL_ERROR);
} catch (Error $e) {
    log_error('Unexpected error in stream_proxy.php', ['message' => $e->getMessage()]);
    ob_clean();
    send_error_response('An unexpected error occurred.', HTTP_INTERNAL_ERROR);
}
