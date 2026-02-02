<?php
require_once __DIR__ . '/../../includes/auth.php';
require_auth(false);
$current_user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Educational Video Streaming</title>
    <link rel="stylesheet" href="/streaming/frontend/css/main.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/streaming/frontend/pages/dashboard.php" class="logo">EduStream</a>
                <nav class="nav">
                    <span>Welcome, <?php echo htmlspecialchars($current_user['username']); ?> (<?php echo htmlspecialchars($current_user['role']); ?>)</span>
                    <?php if ($current_user['role'] === 'admin'): ?>
                        <a href="/streaming/admin/dashboard.php" class="nav-link">Admin Panel</a>
                    <?php endif; ?>
                    <?php if (in_array($current_user['role'], ['admin', 'instructor'])): ?>
                        <button id="addVideoBtn" onclick="openVideoModal()" class="btn btn-primary btn-sm">Add Video</button>
                    <?php endif; ?>
                    <button onclick="handleLogout()" class="btn btn-secondary btn-sm">Logout</button>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 2rem;">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Educational Videos</h1>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="categoryFilter">Filter by Category</label>
                <select id="categoryFilter" class="form-select">
                    <option value="">All Categories</option>
                </select>
            </div>
            
            <div id="videosContainer" class="grid grid-3">
                <p>Loading videos...</p>
            </div>
            
            <div id="pagination" class="text-center mt-3"></div>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add Video</h2>
                <button class="modal-close" onclick="closeVideoModal()">&times;</button>
            </div>
            <form id="videoForm">
                <input type="hidden" id="videoId">
                <div class="form-group">
                    <label class="form-label" for="videoTitle">Title</label>
                    <input type="text" id="videoTitle" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="videoDescription">Description</label>
                    <textarea id="videoDescription" class="form-input form-textarea"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="videoCategory">Category</label>
                    <select id="videoCategory" class="form-select">
                        <option value="">No Category</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Video Source</label>
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: inline-flex; align-items: center; margin-right: 1rem;">
                            <input type="radio" name="videoSource" value="url" checked onchange="toggleVideoSource()">
                            <span style="margin-left: 0.5rem;">Video URL</span>
                        </label>
                        <label style="display: inline-flex; align-items: center;">
                            <input type="radio" name="videoSource" value="upload" onchange="toggleVideoSource()">
                            <span style="margin-left: 0.5rem;">Upload from Local Storage</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" id="videoUrlGroup">
                    <label class="form-label" for="videoUrl">Video URL</label>
                    <input type="url" id="videoUrl" class="form-input" placeholder="https://... or YouTube / Vimeo link">
                    <small style="color: var(--text-secondary);">Direct video link (MP4, WebM, OGG) or YouTube / Vimeo URL</small>
                </div>
                <div class="form-group" id="videoUploadGroup" style="display: none;">
                    <label class="form-label" for="videoFile">Select Video File</label>
                    <input type="file" id="videoFile" class="form-input" accept="video/mp4,video/webm,video/ogg">
                    <small style="color: var(--text-secondary);">Supported formats: MP4, WebM, OGG</small>
                    <div id="videoUploadProgress" style="display: none; margin-top: 0.5rem;">
                        <div style="background: var(--bg-tertiary); border-radius: var(--radius); padding: 0.5rem;">
                            <div id="videoUploadProgressBar" style="background: var(--primary-color); height: 20px; border-radius: var(--radius); width: 0%; transition: width 0.3s;"></div>
                        </div>
                        <small id="videoUploadStatus" style="display: block; margin-top: 0.25rem;"></small>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Thumbnail Source</label>
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: inline-flex; align-items: center; margin-right: 1rem;">
                            <input type="radio" name="thumbnailSource" value="url" checked onchange="toggleThumbnailSource()">
                            <span style="margin-left: 0.5rem;">Thumbnail URL</span>
                        </label>
                        <label style="display: inline-flex; align-items: center;">
                            <input type="radio" name="thumbnailSource" value="upload" onchange="toggleThumbnailSource()">
                            <span style="margin-left: 0.5rem;">Upload Image</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" id="thumbnailUrlGroup">
                    <label class="form-label" for="videoThumbnail">Thumbnail URL (optional)</label>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        <input type="text" id="videoThumbnail" class="form-input" placeholder="https://... or image URL" style="flex: 1; min-width: 200px;">
                        <button type="button" class="btn btn-secondary btn-sm" id="useThumbFromVideoBtn" title="Use thumbnail from the video URL above (YouTube)">Use from video URL</button>
                    </div>
                    <small style="color: var(--text-secondary);">Any image URL (e.g. YouTube thumbnail, Imgur, or your site)</small>
                </div>
                <div class="form-group" id="thumbnailUploadGroup" style="display: none;">
                    <label class="form-label" for="thumbnailFile">Select Thumbnail Image</label>
                    <input type="file" id="thumbnailFile" class="form-input" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <small style="color: var(--text-secondary);">Supported formats: JPEG, PNG, GIF, WebP</small>
                    <div id="thumbnailUploadProgress" style="display: none; margin-top: 0.5rem;">
                        <div style="background: var(--bg-tertiary); border-radius: var(--radius); padding: 0.5rem;">
                            <div id="thumbnailUploadProgressBar" style="background: var(--primary-color); height: 20px; border-radius: var(--radius); width: 0%; transition: width 0.3s;"></div>
                        </div>
                        <small id="thumbnailUploadStatus" style="display: block; margin-top: 0.25rem;"></small>
                    </div>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="closeVideoModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/streaming/frontend/js/api-client.js"></script>
    <script src="/streaming/frontend/js/auth-handler.js"></script>
    <script>
        let currentPage = 1;
        let categories = [];
        let editingVideoId = null;

        // Load categories
        async function loadCategories() {
            try {
                const response = await CategoryAPI.list();
                if (response.success) {
                    categories = response.data;
                    const categoryFilter = document.getElementById('categoryFilter');
                    const videoCategory = document.getElementById('videoCategory');
                    
                    categories.forEach(cat => {
                        const option1 = document.createElement('option');
                        option1.value = cat.id;
                        option1.textContent = cat.name;
                        categoryFilter.appendChild(option1);
                        
                        const option2 = document.createElement('option');
                        option2.value = cat.id;
                        option2.textContent = cat.name;
                        videoCategory.appendChild(option2);
                    });
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        // Load videos
        async function loadVideos(page = 1, categoryId = null) {
            currentPage = page;
            const container = document.getElementById('videosContainer');
            container.innerHTML = '<p>Loading videos...</p>';
            
            try {
                const response = await VideoAPI.list(categoryId, page, 20);
                if (response.success) {
                    const videos = response.data.videos;
                    const pagination = response.data.pagination;
                    
                    if (videos.length === 0) {
                        container.innerHTML = '<p>No videos found.</p>';
                        return;
                    }
                    
                    container.innerHTML = videos.map(video => {
                        let thumbSrc = video.thumbnail_url
                            ? (video.thumbnail_url.startsWith('http') ? video.thumbnail_url : (window.location.origin + (video.thumbnail_url.startsWith('/') ? video.thumbnail_url : '/' + video.thumbnail_url)))
                            : (getThumbnailFromVideoUrl(video.video_url) || '');
                        return `
                        <div class="video-card" onclick="viewVideo(${video.id})">
                            <div class="video-thumbnail">
                                ${thumbSrc ? `<img src="${escapeHtml(thumbSrc)}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;" alt="" onerror="this.onerror=null;this.parentElement.innerHTML='<span style=&quot;display:flex;width:100%;height:100%;align-items:center;justify-content:center;font-size:3rem;color:#6b7280&quot;>📹</span>'">` : '<span style="display:flex;width:100%;height:100%;align-items:center;justify-content:center;font-size:3rem;color:#6b7280">📹</span>'}
                            </div>
                            <div class="video-info">
                                <h3 class="video-title">${escapeHtml(video.title)}</h3>
                                <div class="video-meta">
                                    ${video.category_name ? `<span>${escapeHtml(video.category_name)}</span> • ` : ''}
                                    <span>${escapeHtml(video.uploaded_by_username)}</span>
                                </div>
                                <p class="video-description">${escapeHtml(video.description || '')}</p>
                                ${canEditVideo(video) ? `
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); editVideo(${video.id})">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteVideo(${video.id})">Delete</button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    }).join('');
                    
                    // Pagination
                    const paginationDiv = document.getElementById('pagination');
                    if (pagination.pages > 1) {
                        let paginationHTML = '';
                        if (pagination.page > 1) {
                            paginationHTML += `<button class="btn btn-secondary btn-sm" onclick="loadVideos(${pagination.page - 1}, ${categoryId || 'null'})">Previous</button> `;
                        }
                        paginationHTML += `<span>Page ${pagination.page} of ${pagination.pages}</span>`;
                        if (pagination.page < pagination.pages) {
                            paginationHTML += ` <button class="btn btn-secondary btn-sm" onclick="loadVideos(${pagination.page + 1}, ${categoryId || 'null'})">Next</button>`;
                        }
                        paginationDiv.innerHTML = paginationHTML;
                    } else {
                        paginationDiv.innerHTML = '';
                    }
                }
            } catch (error) {
                container.innerHTML = `<p class="alert alert-error">Error loading videos: ${error.message}</p>`;
            }
        }

        function canEditVideo(video) {
            const userRole = '<?php echo $current_user["role"]; ?>';
            const userId = <?php echo $current_user["id"]; ?>;
            return userRole === 'admin' || (userRole === 'instructor' && video.uploaded_by == userId);
        }

        function viewVideo(videoId) {
            window.location.href = `/streaming/frontend/pages/video.php?id=${videoId}`;
        }

        function openVideoModal(videoId = null) {
            try {
                editingVideoId = videoId;
                const modal = document.getElementById('videoModal');
                const form = document.getElementById('videoForm');
                const title = document.getElementById('modalTitle');
                
                if (!modal || !form || !title) {
                    console.error('Modal elements not found');
                    alert('Error: Could not open video form. Please refresh the page.');
                    return;
                }
                
                // Reset upload states
                uploadedVideoUrl = null;
                uploadedThumbnailUrl = null;
                const videoProgress = document.getElementById('videoUploadProgress');
                const thumbProgress = document.getElementById('thumbnailUploadProgress');
                if (videoProgress) videoProgress.style.display = 'none';
                if (thumbProgress) thumbProgress.style.display = 'none';
                
                if (videoId) {
                    title.textContent = 'Edit Video';
                    // Load video data
                    VideoAPI.get(videoId).then(response => {
                        if (response.success) {
                            const video = response.data;
                            const videoIdInput = document.getElementById('videoId');
                            const videoTitle = document.getElementById('videoTitle');
                            const videoDesc = document.getElementById('videoDescription');
                            const videoCat = document.getElementById('videoCategory');
                            const videoUrl = document.getElementById('videoUrl');
                            const videoThumb = document.getElementById('videoThumbnail');
                            
                            if (videoIdInput) videoIdInput.value = video.id;
                            if (videoTitle) videoTitle.value = video.title;
                            if (videoDesc) videoDesc.value = video.description || '';
                            if (videoCat) videoCat.value = video.category_id || '';
                            if (videoUrl) videoUrl.value = video.video_url;
                            if (videoThumb) videoThumb.value = video.thumbnail_url || '';
                            
                            // Set to URL mode for editing
                            const urlRadio = document.querySelector('input[name="videoSource"][value="url"]');
                            const thumbUrlRadio = document.querySelector('input[name="thumbnailSource"][value="url"]');
                            if (urlRadio) urlRadio.checked = true;
                            if (thumbUrlRadio) thumbUrlRadio.checked = true;
                            toggleVideoSource();
                            toggleThumbnailSource();
                        }
                    }).catch(error => {
                        console.error('Error loading video:', error);
                        showAlert('Failed to load video data', 'error');
                    });
                } else {
                    title.textContent = 'Add Video';
                    form.reset();
                    const videoIdInput = document.getElementById('videoId');
                    if (videoIdInput) videoIdInput.value = '';
                    
                    // Reset to default (URL mode)
                    const urlRadio = document.querySelector('input[name="videoSource"][value="url"]');
                    const thumbUrlRadio = document.querySelector('input[name="thumbnailSource"][value="url"]');
                    if (urlRadio) urlRadio.checked = true;
                    if (thumbUrlRadio) thumbUrlRadio.checked = true;
                    toggleVideoSource();
                    toggleThumbnailSource();
                }
                
                modal.classList.add('show');
            } catch (error) {
                console.error('Error opening video modal:', error);
                alert('Error opening video form: ' + error.message);
            }
        }

        function closeVideoModal() {
            document.getElementById('videoModal').classList.remove('show');
            editingVideoId = null;
        }

        async function editVideo(videoId) {
            openVideoModal(videoId);
        }

        async function deleteVideo(videoId) {
            if (!confirm('Are you sure you want to delete this video?')) {
                return;
            }
            
            try {
                const response = await VideoAPI.delete(videoId);
                if (response.success) {
                    showAlert('Video deleted successfully', 'success');
                    loadVideos(currentPage, document.getElementById('categoryFilter').value || null);
                }
            } catch (error) {
                showAlert(error.message || 'Failed to delete video', 'error');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function toggleVideoSource() {
            const source = document.querySelector('input[name="videoSource"]:checked');
            if (!source) return;
            
            const sourceValue = source.value;
            const urlGroup = document.getElementById('videoUrlGroup');
            const uploadGroup = document.getElementById('videoUploadGroup');
            
            if (sourceValue === 'url') {
                urlGroup.style.display = 'block';
                uploadGroup.style.display = 'none';
                const urlInput = document.getElementById('videoUrl');
                if (urlInput) urlInput.required = true;
                const fileInput = document.getElementById('videoFile');
                if (fileInput) fileInput.required = false;
            } else {
                urlGroup.style.display = 'none';
                uploadGroup.style.display = 'block';
                const urlInput = document.getElementById('videoUrl');
                if (urlInput) urlInput.required = false;
                const fileInput = document.getElementById('videoFile');
                if (fileInput) fileInput.required = true;
            }
        }

        function toggleThumbnailSource() {
            const source = document.querySelector('input[name="thumbnailSource"]:checked');
            if (!source) return;
            
            const sourceValue = source.value;
            const urlGroup = document.getElementById('thumbnailUrlGroup');
            const uploadGroup = document.getElementById('thumbnailUploadGroup');
            
            if (sourceValue === 'url') {
                urlGroup.style.display = 'block';
                uploadGroup.style.display = 'none';
            } else {
                urlGroup.style.display = 'none';
                uploadGroup.style.display = 'block';
            }
        }

        /** Get thumbnail image URL from a YouTube or Vimeo video URL, or null. */
        function getThumbnailFromVideoUrl(videoUrl) {
            if (!videoUrl || typeof videoUrl !== 'string') return null;
            const u = videoUrl.trim();
            let id = null;
            if (u.includes('youtube.com/watch') || u.includes('youtube.com/v/')) {
                const m = u.match(/[?&]v=([^&]+)/);
                id = m ? { type: 'youtube', id: m[1] } : null;
            } else if (u.includes('youtu.be/')) {
                const m = u.match(/youtu\.be\/([^/?&]+)/);
                id = m ? { type: 'youtube', id: m[1] } : null;
            } else if (u.includes('youtube.com/embed/')) {
                const m = u.match(/youtube\.com\/embed\/([^/?&]+)/);
                id = m ? { type: 'youtube', id: m[1] } : null;
            } else if (u.includes('vimeo.com/')) {
                const m = u.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                id = m ? { type: 'vimeo', id: m[1] } : null;
            }
            if (!id) return null;
            if (id.type === 'youtube') {
                return 'https://img.youtube.com/vi/' + id.id + '/hqdefault.jpg';
            }
            if (id.type === 'vimeo') {
                return 'https://vumbnail.com/' + id.id + '.jpg';
            }
            return null;
        }

        document.getElementById('useThumbFromVideoBtn').addEventListener('click', function() {
            const videoUrlInput = document.getElementById('videoUrl');
            const thumbInput = document.getElementById('videoThumbnail');
            if (!videoUrlInput || !thumbInput) return;
            const thumb = getThumbnailFromVideoUrl(videoUrlInput.value);
            if (thumb) {
                thumbInput.value = thumb;
                showAlert('Thumbnail URL set from video URL', 'success');
            } else {
                showAlert('Paste a YouTube or Vimeo video URL in the Video URL field first', 'error');
            }
        });

        let uploadedVideoUrl = null;
        let uploadedThumbnailUrl = null;

        async function uploadVideoFile() {
            const fileInput = document.getElementById('videoFile');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                return null;
            }

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('video_file', file);

            const progressDiv = document.getElementById('videoUploadProgress');
            const progressBar = document.getElementById('videoUploadProgressBar');
            const statusText = document.getElementById('videoUploadStatus');
            
            if (progressDiv) progressDiv.style.display = 'block';
            if (progressBar) progressBar.style.width = '0%';
            if (statusText) statusText.textContent = 'Uploading...';

            try {
                const response = await fetch('/streaming/api/videos/upload.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    throw new Error('Invalid response from server');
                }

                if (!response.ok || !result.success) {
                    throw new Error('VIDEO:' + (result.message || 'Upload failed'));
                }

                if (progressBar) progressBar.style.width = '100%';
                if (statusText) statusText.textContent = 'Upload complete!';
                uploadedVideoUrl = result.data.url;
                return result.data.url;
            } catch (error) {
                if (progressBar) progressBar.style.width = '0%';
                const msg = error.message.replace(/^VIDEO:/, '');
                if (statusText) statusText.textContent = 'Upload failed: ' + msg;
                showAlert('Video upload failed: ' + msg, 'error');
                throw new Error(msg);
            }
        }

        async function uploadThumbnailFile() {
            const fileInput = document.getElementById('thumbnailFile');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                return null;
            }

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('thumbnail_file', file);

            const progressDiv = document.getElementById('thumbnailUploadProgress');
            const progressBar = document.getElementById('thumbnailUploadProgressBar');
            const statusText = document.getElementById('thumbnailUploadStatus');
            
            if (progressDiv) progressDiv.style.display = 'block';
            if (progressBar) progressBar.style.width = '0%';
            if (statusText) statusText.textContent = 'Uploading...';

            try {
                const response = await fetch('/streaming/api/videos/upload_thumbnail.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    throw new Error('Invalid response from server');
                }

                if (!response.ok || !result.success) {
                    throw new Error('THUMBNAIL:' + (result.message || 'Upload failed'));
                }

                if (progressBar) progressBar.style.width = '100%';
                if (statusText) statusText.textContent = 'Upload complete!';
                uploadedThumbnailUrl = result.data.url;
                return result.data.url;
            } catch (error) {
                if (progressBar) progressBar.style.width = '0%';
                if (statusText) statusText.textContent = 'Upload failed: ' + error.message.replace(/^THUMBNAIL:/, '');
                const msg = error.message.replace(/^THUMBNAIL:/, '');
                showAlert('Thumbnail upload failed: ' + msg, 'error');
                throw new Error(msg);
            }
        }

        // Initialize
        loadCategories().then(() => {
            loadVideos();
        });

        // Debug: Check if button and modal exist
        window.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('addVideoBtn');
            if (addBtn) {
                console.log('Add Video button found');
                // Also add event listener as backup
                addBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openVideoModal();
                });
            } else {
                console.warn('Add Video button not found');
            }
            
            // Check if modal exists
            const modal = document.getElementById('videoModal');
            if (modal) {
                console.log('Video modal found');
            } else {
                console.error('Video modal not found!');
            }
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', (e) => {
            loadVideos(1, e.target.value || null);
        });

        // Video form submission
        document.getElementById('videoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            try {
                // Upload files if needed
                const videoSource = document.querySelector('input[name="videoSource"]:checked').value;
                const thumbnailSource = document.querySelector('input[name="thumbnailSource"]:checked').value;
                
                let videoUrl = null;
                let thumbnailUrl = null;
                
                if (videoSource === 'upload') {
                    const fileInput = document.getElementById('videoFile');
                    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                        throw new Error('Please select a video file to upload');
                    }
                    videoUrl = await uploadVideoFile();
                } else {
                    videoUrl = document.getElementById('videoUrl').value;
                }
                
                if (thumbnailSource === 'upload') {
                    const thumbInput = document.getElementById('thumbnailFile');
                    if (!thumbInput || !thumbInput.files || !thumbInput.files[0]) {
                        throw new Error('Please select a thumbnail image to upload');
                    }
                    thumbnailUrl = await uploadThumbnailFile();
                } else {
                    thumbnailUrl = document.getElementById('videoThumbnail').value || null;
                }
                
                if (!videoUrl) {
                    throw new Error(videoSource === 'upload' ? 'Video upload failed. Try again or use a smaller file.' : 'Video URL or file is required');
                }
                
                const videoData = {
                    title: document.getElementById('videoTitle').value,
                    description: document.getElementById('videoDescription').value,
                    category_id: document.getElementById('videoCategory').value || null,
                    video_url: videoUrl,
                    thumbnail_url: thumbnailUrl
                };
                
                let response;
                if (editingVideoId) {
                    response = await VideoAPI.update(editingVideoId, videoData);
                } else {
                    response = await VideoAPI.create(videoData);
                }
                
                if (response.success) {
                    showAlert(editingVideoId ? 'Video updated successfully' : 'Video created successfully', 'success');
                    closeVideoModal();
                    loadVideos(currentPage, document.getElementById('categoryFilter').value || null);
                }
            } catch (error) {
                showAlert(error.message || 'Failed to save video', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save';
            }
        });

        // Close modal on outside click
        document.getElementById('videoModal').addEventListener('click', (e) => {
            if (e.target.id === 'videoModal') {
                closeVideoModal();
            }
        });
    </script>
</body>
</html>

