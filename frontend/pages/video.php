<?php
require_once __DIR__ . '/../../includes/auth.php';
require_auth(false);
$current_user = get_logged_in_user();
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video - EduStream</title>
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
                        Welcome, <?php echo htmlspecialchars($current_user['username']); ?>
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
        <div id="videoContainer">
            <p>Loading video...</p>
        </div>
    </div>

    <script src="/streaming/frontend/js/api-client.js"></script>
    <script src="/streaming/frontend/js/auth-handler.js"></script>
    <script>
        const videoId = <?php echo $video_id; ?>;

        async function loadVideo() {
            if (!videoId) {
                document.getElementById('videoContainer').innerHTML = '<p class="alert alert-error">Invalid video ID</p>';
                return;
            }

            try {
                const response = await VideoAPI.get(videoId);
                if (response.success) {
                    const video = response.data;
                    const embed = getEmbedInfo(video.video_url);
                    const playerHtml = embed
                        ? `<iframe src="${escapeHtml(embed.embedUrl)}" style="width: 100%; aspect-ratio: 16/9; min-height: 360px; border: none;" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" title="${escapeHtml(video.title)}"></iframe>`
                        : buildNativePlayerHtml(video);
                    document.getElementById('videoContainer').innerHTML = `
                        <div class="card">
                            <h1 class="card-title">${escapeHtml(video.title)}</h1>
                            <div class="video-meta mb-2">
                                ${video.category_name ? `<span>Category: ${escapeHtml(video.category_name)}</span> • ` : ''}
                                <span>Uploaded by: ${escapeHtml(video.uploaded_by_username)}</span> • 
                                <span>${new Date(video.created_at).toLocaleDateString()}</span>
                            </div>
                            <div style="margin-bottom: 1.5rem; min-height: 360px;">
                                ${playerHtml}
                                ${embed ? '' : '<p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary, #666);">If you only hear audio and no picture, the file may use a video codec your browser doesn’t support. Re-encode to <strong>H.264 (MP4)</strong> for best compatibility.</p>'}
                            </div>
                            <div>
                                <h2>Description</h2>
                                <p>${escapeHtml(video.description || 'No description available.')}</p>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('videoContainer').innerHTML = '<p class="alert alert-error">Video not found</p>';
                }
            } catch (error) {
                document.getElementById('videoContainer').innerHTML = `<p class="alert alert-error">Error loading video: ${error.message}</p>`;
            }
        }

        function escapeHtml(text) {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Detect YouTube / Vimeo (and similar) URLs and return embed URL, or null for direct video files.
         */
        function getEmbedInfo(url) {
            if (!url || typeof url !== 'string') return null;
            const u = url.trim();
            let id = null;
            let embedUrl = null;
            if (u.includes('youtube.com/watch') || u.includes('youtube.com/v/')) {
                const m = u.match(/[?&]v=([^&]+)/);
                id = m ? m[1] : null;
                if (id) embedUrl = 'https://www.youtube.com/embed/' + id;
            } else if (u.includes('youtu.be/')) {
                const m = u.match(/youtu\.be\/([^/?&]+)/);
                id = m ? m[1] : null;
                if (id) embedUrl = 'https://www.youtube.com/embed/' + id;
            } else if (u.includes('youtube.com/embed/')) {
                const m = u.match(/youtube\.com\/embed\/([^/?&]+)/);
                id = m ? m[1] : null;
                if (id) embedUrl = 'https://www.youtube.com/embed/' + id;
            } else if (u.includes('vimeo.com/')) {
                const m = u.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                id = m ? m[1] : null;
                if (id) embedUrl = 'https://player.vimeo.com/video/' + id;
            }
            return embedUrl ? { embedUrl } : null;
        }

        function buildNativePlayerHtml(video) {
            const videoUrl = (video.video_url && video.video_url.startsWith('/'))
                ? (window.location.origin + video.video_url)
                : video.video_url;
            const ext = (video.video_url || '').split('.').pop().toLowerCase();
            const mime = ext === 'webm' ? 'video/webm' : (ext === 'ogg' ? 'video/ogg' : 'video/mp4');
            return `<video controls preload="metadata" style="width: 100%; max-height: 600px; min-height: 360px; background: #000;">
                <source src="${escapeHtml(videoUrl)}" type="${escapeHtml(mime)}">
                Your browser does not support the video tag.
            </video>`;
        }

        loadVideo();
    </script>
</body>
</html>

