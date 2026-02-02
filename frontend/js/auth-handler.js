/**
 * Auth handler
 * Login, register, logout, session check, form validation, alerts
 */

// ---------------------------------------------------------------------------
// Session check
// ---------------------------------------------------------------------------

async function checkAuth() {
    try {
        const response = await AuthAPI.check();
        return response.data;
    } catch (error) {
        return null;
    }
}

// ---------------------------------------------------------------------------
// Login / Register / Logout
// ---------------------------------------------------------------------------

async function handleLogin(username, password) {
    try {
        const response = await AuthAPI.login(username, password);
        if (response.success) {
            window.location.href = '/streaming/frontend/pages/dashboard.php';
        }
        return response;
    } catch (error) {
        return { success: false, message: error.message || 'Login failed' };
    }
}

async function handleRegister(username, email, password, role = 'student') {
    try {
        const response = await AuthAPI.register(username, email, password, role);
        if (response.success) {
            return await handleLogin(username, password);
        }
        return response;
    } catch (error) {
        return { success: false, message: error.message || 'Registration failed' };
    }
}

async function handleLogout() {
    try {
        await AuthAPI.logout();
    } catch (error) {
        console.error('Logout error:', error);
    }
    window.location.href = '/streaming/index.php';
}

// ---------------------------------------------------------------------------
// UI helpers
// ---------------------------------------------------------------------------

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => alertDiv.remove(), 5000);
}

// ---------------------------------------------------------------------------
// Form validation
// ---------------------------------------------------------------------------

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        const errorEl = input.parentElement.querySelector('.form-error');

        if (!input.value.trim()) {
            isValid = false;
            if (errorEl) {
                errorEl.textContent = 'This field is required';
                errorEl.classList.add('show');
            }
            input.style.borderColor = '#ef4444';
        } else {
            if (errorEl) errorEl.classList.remove('show');
            input.style.borderColor = '';
        }

        if (input.type === 'email' && input.value) {
            const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
            if (!ok) {
                isValid = false;
                if (errorEl) {
                    errorEl.textContent = 'Invalid email format';
                    errorEl.classList.add('show');
                }
                input.style.borderColor = '#ef4444';
            }
        }

        if (input.type === 'password' && input.value && input.value.length < 6) {
            isValid = false;
            if (errorEl) {
                errorEl.textContent = 'Password must be at least 6 characters';
                errorEl.classList.add('show');
            }
            input.style.borderColor = '#ef4444';
        }
    });

    return isValid;
}
