<?php
/**
 * Validation and request helpers
 * Sanitizers, validators (auth / video / category), request input
 */

// ---------------------------------------------------------------------------
// Sanitizers
// ---------------------------------------------------------------------------

function sanitize_string($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitize_email($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

// ---------------------------------------------------------------------------
// Auth validators (username, email, password, role)
// ---------------------------------------------------------------------------

function validate_username($username) {
    if (empty($username)) return ['valid' => false, 'error' => 'Username is required'];
    if (strlen($username) < 3) return ['valid' => false, 'error' => 'Username must be at least 3 characters'];
    if (strlen($username) > 50) return ['valid' => false, 'error' => 'Username must be less than 50 characters'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
    }
    return ['valid' => true, 'error' => ''];
}

function validate_email($email) {
    if (empty($email)) return ['valid' => false, 'error' => 'Email is required'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['valid' => false, 'error' => 'Invalid email format'];
    if (strlen($email) > 100) return ['valid' => false, 'error' => 'Email must be less than 100 characters'];
    return ['valid' => true, 'error' => ''];
}

function validate_password($password) {
    if (empty($password)) return ['valid' => false, 'error' => 'Password is required'];
    if (strlen($password) < 6) return ['valid' => false, 'error' => 'Password must be at least 6 characters'];
    return ['valid' => true, 'error' => ''];
}

function validate_role($role) {
    $valid_roles = [ROLE_ADMIN, ROLE_INSTRUCTOR, ROLE_STUDENT];
    if (empty($role)) return ['valid' => false, 'error' => 'Role is required'];
    if (!in_array($role, $valid_roles)) return ['valid' => false, 'error' => 'Invalid role'];
    return ['valid' => true, 'error' => ''];
}

// ---------------------------------------------------------------------------
// Video / category validators
// ---------------------------------------------------------------------------

function validate_video_title($title) {
    if (empty($title)) return ['valid' => false, 'error' => 'Video title is required'];
    if (strlen($title) > 200) return ['valid' => false, 'error' => 'Title must be less than 200 characters'];
    return ['valid' => true, 'error' => ''];
}

function validate_video_url($url) {
    if (empty($url)) return ['valid' => false, 'error' => 'Video URL is required'];

    $is_relative = (strpos($url, '/') === 0 && strpos($url, '//') !== 0);
    if (!$is_relative && !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'error' => 'Invalid URL format'];
    }
    if ($is_relative && !preg_match('#^/[a-zA-Z0-9/_.\-]+$#', $url)) {
        return ['valid' => false, 'error' => 'Invalid path format'];
    }
    if (strlen($url) > 500) return ['valid' => false, 'error' => 'URL must be less than 500 characters'];

    return ['valid' => true, 'error' => ''];
}

function validate_category_name($name) {
    if (empty($name)) return ['valid' => false, 'error' => 'Category name is required'];
    if (strlen($name) > 100) return ['valid' => false, 'error' => 'Category name must be less than 100 characters'];
    return ['valid' => true, 'error' => ''];
}

// ---------------------------------------------------------------------------
// Request input (POST, GET, JSON body)
// ---------------------------------------------------------------------------

function get_post($key, $default = null) {
    return isset($_POST[$key]) ? sanitize_string($_POST[$key]) : $default;
}

function get_get($key, $default = null) {
    return isset($_GET[$key]) ? sanitize_string($_GET[$key]) : $default;
}

function get_json_input() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return [];
    return $data ?: [];
}
