<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(ROLE_ADMIN, false);
$current_user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - EduStream</title>
    <link rel="stylesheet" href="/streaming/frontend/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <a href="/streaming/frontend/pages/dashboard.php" class="logo">EduStream</a>
                    <div style="color: var(--text-on-dark); opacity: 0.9; font-size: 0.875rem; margin-top: 4px;">
                        Admin Panel
                    </div>
                </div>
                <nav class="nav">
                    <a href="/streaming/frontend/pages/dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
                    <button onclick="handleLogout()" class="btn btn-secondary btn-sm">Logout</button>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 1.5rem; padding-bottom: 2rem;">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1 class="card-title" style="margin: 0;">User Management</h1>
                <button onclick="openUserModal()" class="btn btn-primary btn-sm">Add User</button>
            </div>
            
            <div id="usersContainer">
                <p>Loading users...</p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h1 class="card-title" style="margin: 0;">Category Management</h1>
                <button onclick="openCategoryModal()" class="btn btn-primary btn-sm">Add Category</button>
            </div>
            
            <div id="categoriesContainer">
                <p>Loading categories...</p>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="userModalTitle">Add User</h2>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label class="form-label" for="userUsername">Username</label>
                    <input type="text" id="userUsername" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="userEmail">Email</label>
                    <input type="email" id="userEmail" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="userPassword">Password</label>
                    <input type="password" id="userPassword" class="form-input">
                    <small>Leave blank to keep current password</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="userRole">Role</label>
                    <select id="userRole" class="form-select" required>
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="categoryModalTitle">Add Category</h2>
                <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <form id="categoryForm">
                <input type="hidden" id="categoryId">
                <div class="form-group">
                    <label class="form-label" for="categoryName">Name</label>
                    <input type="text" id="categoryName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="categoryDescription">Description</label>
                    <textarea id="categoryDescription" class="form-input form-textarea"></textarea>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/streaming/frontend/js/api-client.js"></script>
    <script src="/streaming/frontend/js/auth-handler.js"></script>
    <script>
        let editingUserId = null;
        let editingCategoryId = null;

        // Load users
        async function loadUsers() {
            try {
                const response = await UserAPI.list();
                if (response.success) {
                    const users = response.data.users;
                    const container = document.getElementById('usersContainer');
                    
                    if (users.length === 0) {
                        container.innerHTML = '<p>No users found.</p>';
                        return;
                    }
                    
                    container.innerHTML = `
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 0.75rem; text-align: left;">Username</th>
                                    <th style="padding: 0.75rem; text-align: left;">Email</th>
                                    <th style="padding: 0.75rem; text-align: left;">Role</th>
                                    <th style="padding: 0.75rem; text-align: left;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${users.map(user => `
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 0.75rem;">${escapeHtml(user.username)}</td>
                                        <td style="padding: 0.75rem;">${escapeHtml(user.email)}</td>
                                        <td style="padding: 0.75rem;">${escapeHtml(user.role)}</td>
                                        <td style="padding: 0.75rem;">
                                            <button class="btn btn-sm btn-secondary" onclick="editUser(${user.id})">Edit</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
            } catch (error) {
                document.getElementById('usersContainer').innerHTML = `<p class="alert alert-error">Error loading users: ${error.message}</p>`;
            }
        }

        // Load categories
        async function loadCategories() {
            try {
                const response = await CategoryAPI.list();
                if (response.success) {
                    const categories = response.data;
                    const container = document.getElementById('categoriesContainer');
                    
                    if (categories.length === 0) {
                        container.innerHTML = '<p>No categories found.</p>';
                        return;
                    }
                    
                    container.innerHTML = `
                        <div class="grid grid-3">
                            ${categories.map(cat => `
                                <div class="card">
                                    <h3>${escapeHtml(cat.name)}</h3>
                                    <p>${escapeHtml(cat.description || '')}</p>
                                    <p><small>Videos: ${cat.video_count}</small></p>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-secondary" onclick="editCategory(${cat.id})">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(${cat.id})">Delete</button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('categoriesContainer').innerHTML = `<p class="alert alert-error">Error loading categories: ${error.message}</p>`;
            }
        }

        // User functions
        function openUserModal(userId = null) {
            editingUserId = userId;
            const modal = document.getElementById('userModal');
            const title = document.getElementById('userModalTitle');
            
            if (userId) {
                title.textContent = 'Edit User';
                UserAPI.get(userId).then(response => {
                    if (response.success) {
                        const user = response.data;
                        document.getElementById('userId').value = user.id;
                        document.getElementById('userUsername').value = user.username;
                        document.getElementById('userEmail').value = user.email;
                        document.getElementById('userRole').value = user.role;
                        document.getElementById('userPassword').value = '';
                    }
                });
            } else {
                title.textContent = 'Add User';
                document.getElementById('userForm').reset();
                document.getElementById('userId').value = '';
            }
            
            modal.classList.add('show');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('show');
            editingUserId = null;
        }

        async function editUser(userId) {
            openUserModal(userId);
        }

        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) {
                return;
            }
            
            try {
                const response = await UserAPI.delete(userId);
                if (response.success) {
                    showAlert('User deleted successfully', 'success');
                    loadUsers();
                }
            } catch (error) {
                showAlert(error.message || 'Failed to delete user', 'error');
            }
        }

        // Category functions
        function openCategoryModal(categoryId = null) {
            editingCategoryId = categoryId;
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('categoryModalTitle');
            
            if (categoryId) {
                title.textContent = 'Edit Category';
                CategoryAPI.get(categoryId).then(response => {
                    if (response.success) {
                        const cat = response.data;
                        document.getElementById('categoryId').value = cat.id;
                        document.getElementById('categoryName').value = cat.name;
                        document.getElementById('categoryDescription').value = cat.description || '';
                    }
                });
            } else {
                title.textContent = 'Add Category';
                document.getElementById('categoryForm').reset();
                document.getElementById('categoryId').value = '';
            }
            
            modal.classList.add('show');
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('show');
            editingCategoryId = null;
        }

        async function editCategory(categoryId) {
            openCategoryModal(categoryId);
        }

        async function deleteCategory(categoryId) {
            if (!confirm('Are you sure you want to delete this category?')) {
                return;
            }
            
            try {
                const response = await CategoryAPI.delete(categoryId);
                if (response.success) {
                    showAlert('Category deleted successfully', 'success');
                    loadCategories();
                }
            } catch (error) {
                showAlert(error.message || 'Failed to delete category', 'error');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Form submissions
        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const userData = {
                username: document.getElementById('userUsername').value,
                email: document.getElementById('userEmail').value,
                role: document.getElementById('userRole').value
            };
            
            const password = document.getElementById('userPassword').value;
            if (password) {
                userData.password = password;
            }
            
            try {
                let response;
                if (editingUserId) {
                    response = await UserAPI.update(editingUserId, userData);
                } else {
                    response = await UserAPI.create(userData);
                }
                
                if (response.success) {
                    showAlert(editingUserId ? 'User updated successfully' : 'User created successfully', 'success');
                    closeUserModal();
                    loadUsers();
                }
            } catch (error) {
                showAlert(error.message || 'Failed to save user', 'error');
            }
        });

        document.getElementById('categoryForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const categoryData = {
                name: document.getElementById('categoryName').value,
                description: document.getElementById('categoryDescription').value
            };
            
            try {
                let response;
                if (editingCategoryId) {
                    response = await CategoryAPI.update(editingCategoryId, categoryData);
                } else {
                    response = await CategoryAPI.create(categoryData);
                }
                
                if (response.success) {
                    showAlert(editingCategoryId ? 'Category updated successfully' : 'Category created successfully', 'success');
                    closeCategoryModal();
                    loadCategories();
                }
            } catch (error) {
                showAlert(error.message || 'Failed to save category', 'error');
            }
        });

        // Close modals on outside click
        document.getElementById('userModal').addEventListener('click', (e) => {
            if (e.target.id === 'userModal') closeUserModal();
        });

        document.getElementById('categoryModal').addEventListener('click', (e) => {
            if (e.target.id === 'categoryModal') closeCategoryModal();
        });

        // Initialize
        loadUsers();
        loadCategories();
    </script>
</body>
</html>

