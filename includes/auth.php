<?php
/**
 * Authentication helpers
 * Session, current user, guards (require_auth / require_role), password, login/logout
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------

function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ---------------------------------------------------------------------------
// Current user state
// ---------------------------------------------------------------------------

function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function get_current_user_id() {
    start_session();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function get_current_user_role() {
    start_session();
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function get_logged_in_user() {
    start_session();
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role']
    ];
}

function has_role($required_roles) {
    $user_role = get_current_user_role();
    if (!$user_role) return false;
    if (is_array($required_roles)) return in_array($user_role, $required_roles);
    return $user_role === $required_roles;
}

// ---------------------------------------------------------------------------
// Guards (require auth / role)
// ---------------------------------------------------------------------------

function require_auth($return_json = true) {
    if (!is_logged_in()) {
        if ($return_json) {
            http_response_code(HTTP_UNAUTHORIZED);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => MSG_UNAUTHORIZED]);
            exit;
        }
        header('Location: /streaming/index.php');
        exit;
    }
}

function require_role($required_roles, $return_json = true) {
    require_auth($return_json);
    if (!has_role($required_roles)) {
        if ($return_json) {
            http_response_code(HTTP_FORBIDDEN);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => MSG_FORBIDDEN]);
            exit;
        }
        header('Location: /streaming/frontend/pages/dashboard.php');
        exit;
    }
}

// ---------------------------------------------------------------------------
// Password
// ---------------------------------------------------------------------------

function hash_password($username, $password) {
    return sha1($username . $password);
}

function verify_password($username, $password, $hashed_password) {
    return hash_password($username, $password) === $hashed_password;
}

// ---------------------------------------------------------------------------
// Login / Logout
// ---------------------------------------------------------------------------

function login_user($user) {
    start_session();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in_at'] = time();
}

function logout_user() {
    start_session();
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}
