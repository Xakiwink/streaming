/**
 * API client
 * Single place for all API calls (auth, videos, categories, users)
 */

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

const API_BASE_URL = '/streaming/api';

// ---------------------------------------------------------------------------
// Core request
// ---------------------------------------------------------------------------

async function apiRequest(endpoint, method = 'GET', data = null, headers = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const options = {
        method,
        headers: { 'Content-Type': 'application/json', ...headers },
        credentials: 'same-origin'
    };

    if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response:', text);
            throw new Error('Invalid response from server. Please check server logs.');
        }
        if (!response.ok) throw new Error(result.message || 'Request failed');
        return result;
    } catch (error) {
        console.error('API request error:', error);
        throw error;
    }
}

// ---------------------------------------------------------------------------
// Auth API
// ---------------------------------------------------------------------------

const AuthAPI = {
    register: (username, email, password, role = 'student') =>
        apiRequest('/auth/register.php', 'POST', { username, email, password, role }),

    login: (username, password) =>
        apiRequest('/auth/login.php', 'POST', { username, password }),

    logout: () =>
        apiRequest('/auth/logout.php', 'POST'),

    check: () =>
        apiRequest('/auth/check.php', 'GET')
};

// ---------------------------------------------------------------------------
// Video API
// ---------------------------------------------------------------------------

const VideoAPI = {
    list: (categoryId = null, page = 1, limit = 20) => {
        let endpoint = `/videos/list.php?page=${page}&limit=${limit}`;
        if (categoryId) endpoint += `&category_id=${categoryId}`;
        return apiRequest(endpoint, 'GET');
    },

    get: (videoId) =>
        apiRequest(`/videos/get.php?id=${videoId}`, 'GET'),

    create: (videoData) =>
        apiRequest('/videos/create.php', 'POST', videoData),

    update: (videoId, videoData) =>
        apiRequest('/videos/update.php', 'PUT', { id: videoId, ...videoData }),

    delete: (videoId) =>
        apiRequest('/videos/delete.php', 'POST', { id: videoId })
};

// ---------------------------------------------------------------------------
// Category API
// ---------------------------------------------------------------------------

const CategoryAPI = {
    list: () =>
        apiRequest('/categories/list.php', 'GET'),

    get: (categoryId) =>
        apiRequest(`/categories/get.php?id=${categoryId}`, 'GET'),

    create: (categoryData) =>
        apiRequest('/categories/create.php', 'POST', categoryData),

    update: (categoryId, categoryData) =>
        apiRequest('/categories/update.php', 'PUT', { id: categoryId, ...categoryData }),

    delete: (categoryId) =>
        apiRequest('/categories/delete.php', 'DELETE', { id: categoryId })
};

// ---------------------------------------------------------------------------
// User API (admin)
// ---------------------------------------------------------------------------

const UserAPI = {
    list: (page = 1, limit = 20, role = null) => {
        let endpoint = `/users/list.php?page=${page}&limit=${limit}`;
        if (role) endpoint += `&role=${role}`;
        return apiRequest(endpoint, 'GET');
    },

    get: (userId) =>
        apiRequest(`/users/get.php?id=${userId}`, 'GET'),

    create: (userData) =>
        apiRequest('/users/create.php', 'POST', userData),

    update: (userId, userData) =>
        apiRequest('/users/update.php', 'PUT', { id: userId, ...userData }),

    delete: (userId) =>
        apiRequest('/users/delete.php', 'DELETE', { id: userId })
};
