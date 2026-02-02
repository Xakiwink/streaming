<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Educational Video Streaming</title>
    <link rel="stylesheet" href="/streaming/frontend/css/main.css">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 50px;">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title text-center">Register</h1>
            </div>
            
            <form id="registerForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <div class="form-error"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="role">Role</label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                    </select>
                    <div class="form-error"></div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="text-center mt-2">
                <p>Already have an account? <a href="/streaming/index.php" class="nav-link">Login</a></p>
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
            submitBtn.textContent = 'Registering...';
            
            const result = await handleRegister(username, email, password, role);
            
            if (!result.success) {
                showAlert(result.message || 'Registration failed', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Register';
            }
        });
    </script>
</body>
</html>

