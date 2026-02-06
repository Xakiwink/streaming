<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EduStream</title>
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

    <!-- Register Card -->
    <div class="auth-card">
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join us to get started</p>
        
        <form id="registerForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Choose a username" required>
                <div class="form-error"></div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
                <div class="form-error"></div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required>
                <div class="form-error"></div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="role">Account Type</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="student">Student</option>
                    <option value="instructor">Instructor</option>
                </select>
                <div class="form-error"></div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <div class="auth-footer">
            Already have an account? <a href="/streaming/index.php">Sign In</a>
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
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!validateForm('registerForm')) {
                return;
            }
            
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating account...';
            
            const result = await handleRegister(username, email, password, role);
            
            if (!result.success) {
                showAlert(result.message || 'Registration failed', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Account';
            }
        });
    </script>
</body>
</html>
