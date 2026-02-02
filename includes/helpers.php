<?php
/**
 * Helper functions
 * Logging, user lookup, DB helpers, video transcoding
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// ---------------------------------------------------------------------------
// Logging
// ---------------------------------------------------------------------------

function log_activity($action, $user_id = null, $details = '', $ip_address = null) {
    $conn = get_db_connection();
    if (!$conn) return false;

    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)"
    );
    if (!$stmt) {
        error_log("Failed to prepare log statement: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "siss", $action, $user_id, $details, $ip_address);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

function log_error($message, $context = []) {
    $log_dir = dirname(LOG_ERROR);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' ' . json_encode($context) : '';
    file_put_contents(LOG_ERROR, "[$timestamp] $message$context_str\n", FILE_APPEND);
}

// ---------------------------------------------------------------------------
// User lookup
// ---------------------------------------------------------------------------

function get_user_by_id($user_id) {
    $conn = get_db_connection();
    if (!$conn) return null;

    $stmt = mysqli_prepare($conn,
        "SELECT id, username, email, role, created_at FROM users WHERE id = ?"
    );
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user;
}

function get_user_by_username($username) {
    $conn = get_db_connection();
    if (!$conn) return null;

    $stmt = mysqli_prepare($conn,
        "SELECT id, username, email, password, role, created_at FROM users WHERE username = ?"
    );
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user;
}

// ---------------------------------------------------------------------------
// User checks (exists)
// ---------------------------------------------------------------------------

function username_exists($username, $exclude_id = null) {
    $conn = get_db_connection();
    if (!$conn) return false;

    if ($exclude_id) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $username, $exclude_id);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    return $exists;
}

function email_exists($email, $exclude_id = null) {
    $conn = get_db_connection();
    if (!$conn) return false;

    if ($exclude_id) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $exclude_id);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    return $exists;
}

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------

function db_escape($string) {
    $conn = get_db_connection();
    return $conn ? mysqli_real_escape_string($conn, $string) : '';
}

// ---------------------------------------------------------------------------
// Video transcoding (FFmpeg → H.264/AAC for browser compatibility)
// ---------------------------------------------------------------------------

function transcode_video_to_h264($input_path) {
    if (!is_file($input_path) || !is_readable($input_path)) {
        return null;
    }

    $dir = dirname($input_path);
    $base = pathinfo($input_path, PATHINFO_FILENAME);
    $temp_output = $dir . '/' . $base . '_conv.mp4';
    $final_output = $dir . '/' . $base . '.mp4';
    if ($temp_output === $input_path) {
        $temp_output = $dir . '/' . $base . '_converted.mp4';
    }

    if (trim((string) shell_exec('which ffmpeg 2>/dev/null')) === '') {
        log_error('transcode_video_to_h264: ffmpeg not found');
        return null;
    }

    $cmd = sprintf(
        "ffmpeg -i %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart -y %s 2>&1",
        escapeshellarg($input_path),
        escapeshellarg($temp_output)
    );
    exec($cmd, $lines, $code);

    if ($code !== 0) {
        log_error('transcode_video_to_h264 failed', [
            'code' => $code,
            'input' => $input_path,
            'log' => implode("\n", array_slice($lines, -5))
        ]);
        if (is_file($temp_output)) @unlink($temp_output);
        return null;
    }

    if (!is_file($temp_output) || filesize($temp_output) < 1024) {
        log_error('transcode_video_to_h264: output missing or too small', ['output' => $temp_output]);
        if (is_file($temp_output)) @unlink($temp_output);
        return null;
    }

    if ($input_path !== $temp_output) {
        @unlink($input_path);
    }
    if ($temp_output !== $final_output) {
        if (file_exists($final_output)) @unlink($final_output);
        rename($temp_output, $final_output);
        return $final_output;
    }

    return $temp_output;
}
