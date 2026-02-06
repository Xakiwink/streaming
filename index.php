<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduStream</title>
    <link rel="stylesheet" href="/streaming/frontend/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <!-- Logo -->
    <div class="auth-logo">
        <img src="/streaming/frontend/img/logo.png" alt="EduStream">
    </div>
    <div class="auth-app-name">EduStream</div>

    <!-- Login Card -->
    <div class="auth-card">
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in to continue</p>
        
        <form id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required>
                <div class="form-error"></div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                <div class="form-error"></div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
        
        <div class="auth-footer">
            Don't have an account? <a href="/streaming/frontend/pages/register.php">Sign Up</a>
        </div>
    </div>

    <script src="/streaming/frontend/js/api-client.js"></script>
    <script src="/streaming/frontend/js/auth-handler.js"></script>
    <script>
        // Check if already logged in
        checkAuth().then(user => {
            if (user) {
                window.location.href = '/streaming/frontend/pages/dashboard.php';
            }
        });

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm('loginForm')) {
                return;
            }
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';
            
            const result = await handleLogin(username, password);
            
            if (!result.success) {
                showAlert(result.message || 'Login failed', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign In';
            }
        });
    </script>
</body>
</html>
