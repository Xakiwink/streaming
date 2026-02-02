<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Educational Video Streaming</title>
    <link rel="stylesheet" href="/streaming/frontend/css/main.css">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 100px;">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title text-center">Login</h1>
            </div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <div class="form-error"></div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="text-center mt-2">
                <p>Don't have an account? <a href="/streaming/frontend/pages/register.php" class="nav-link">Register</a></p>
            </div>
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
            submitBtn.textContent = 'Logging in...';
            
            const result = await handleLogin(username, password);
            
            if (!result.success) {
                showAlert(result.message || 'Login failed', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            }
        });
    </script>
</body>
</html>

