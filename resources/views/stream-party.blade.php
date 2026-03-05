<!DOCTYPE html>
<html>
<head>
    <title>Stream Party</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="create-form" style="display: block;">
        <h2>Create Room</h2>
        <input type="url" id="m3u8-url" placeholder="Paste video URL (YouTube, m3u8, mp4, etc.)" style="width:420px;" />
        <input type="url" id="referer-url" placeholder="Optional: Site URL (e.g. 123movienow.cc/...)" style="width:420px;" />
        <button onclick="createRoom()">Create</button>
        <p id="room-info"></p>
    </div>

    <div id="join-form" style="display: none;">
        <h2>Join Room</h2>
        <input type="text" id="room-id" placeholder="Room ID" />
        <input type="text" id="access-key" placeholder="Key" />
        <input type="text" id="username" placeholder="Your Name" />
        <button onclick="joinRoom()">Join</button>
    </div>

    <div id="room-view" style="display: none;">
        <h2 id="room-header">Room</h2>
        <p id="room-share-link" style="background: #e2e8f0; padding: 10px; border-radius: 5px; display: none;"></p>
        <!-- Native video (mp4 / m3u8 / yt-dlp extracted) -->
        <video id="video" controls autoplay muted width="640" height="360" style="display: none;"></video>
        <!-- YouTube IFrame player container -->
        <div id="yt-player" style="display: none;"></div>
        <div id="viewers">
            <h3>Live Viewers: <span id="count">0</span></h3>
            <ul id="viewer-list"></ul>
        </div>
        <button onclick="leaveRoom()">Leave</button>
    </div>

    <script>
        // ─── Register Ad-Blocker Service Worker ──────────────────────────────────
        // The SW intercepts ALL network requests from the browser and blocks
        // known ad-serving domains (YouTubeads, DoubleClick, IMA SDK, etc.).
        // Works for every user who visits the site — no extension needed.
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/ad-blocker-sw.js', { scope: '/' })
                .then(reg => console.log('[AdBlock SW] Registered, scope:', reg.scope))
                .catch(err => console.warn('[AdBlock SW] Registration failed:', err));
        }

        // ─── State ───────────────────────────────────────────────────────────────
        let currentRoomId = null;
        let hls = null;
        let liveUsers = [];
        let ytPlayer = null;
        let ytAPIReady = false;
        let pendingYtInit = null;

        // ─── YouTube IFrame API ───────────────────────────────────────────────────
        // Register the callback FIRST, then load the script dynamically.
        window.onYouTubeIframeAPIReady = function () {
            ytAPIReady = true;
            if (typeof pendingYtInit === 'function') {
                pendingYtInit();
                pendingYtInit = null;
            }
        };
        (function () {
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        })();

        // ─── Helpers ─────────────────────────────────────────────────────────────
        function extractYouTubeID(url) {
            const regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i;
            const match = url.match(regex);
            return match ? match[1] : null;
        }

        // ─── Create Room ─────────────────────────────────────────────────────────
        async function createRoom() {
            const url = document.getElementById('m3u8-url').value;
            const refUrl = document.getElementById('referer-url').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const res = await fetch('/rooms', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ m3u8_url: url, referer_url: refUrl })
            });
            const data = await res.json();
            const shareUrl = `${window.location.origin}/?join=1&roomId=${data.room_id}&key=${data.access_key}`;

            localStorage.setItem('adminRoom', data.room_id);
            document.getElementById('room-id').value = data.room_id;
            document.getElementById('access-key').value = data.access_key;
            document.getElementById('username').value = 'Admin (Host)';

            await joinRoom();

            const shareEl = document.getElementById('room-share-link');
            shareEl.style.display = 'block';
            shareEl.innerHTML = `<strong>Share this link to invite others:</strong> <a href="${shareUrl}" target="_blank">${shareUrl}</a>`;
        }

        // ─── Join Room ───────────────────────────────────────────────────────────
        async function joinRoom() {
            const roomId = document.getElementById('room-id').value;
            const key    = document.getElementById('access-key').value;
            const username = document.getElementById('username').value || 'Anon';

            const res = await fetch(`/rooms/${roomId}?key=${key}&username=${encodeURIComponent(username)}`);
            if (!res.ok) return alert('Invalid room/key');
            const { m3u8_url, referer_url } = await res.json();

            document.getElementById('create-form').style.display = 'none';
            document.getElementById('join-form').style.display   = 'none';
            document.getElementById('room-view').style.display   = 'block';
            document.getElementById('room-header').innerText = `Room: ${roomId}`;
            currentRoomId = roomId;

            const isAdmin = localStorage.getItem('adminRoom') == roomId;
            const video      = document.getElementById('video');
            const ytPlayerDiv = document.getElementById('yt-player');
            const ytVideoId  = extractYouTubeID(m3u8_url);

            const channel = Echo.join(`room.${roomId}`);

            if (ytVideoId) {
                // ── YOUTUBE (IFrame API — ads blocked by Service Worker) ────────
                video.style.display    = 'none';
                ytPlayerDiv.style.display = 'block';

                const initYTPlayer = () => {
                    if (ytPlayer) { ytPlayer.destroy(); ytPlayer = null; }
                    document.getElementById('yt-player').innerHTML = '';

                    ytPlayer = new YT.Player('yt-player', {
                        height: '360',
                        width:  '640',
                        videoId: ytVideoId,
                        host:   'https://www.youtube-nocookie.com',
                        playerVars: {
                            'controls':         isAdmin ? 1 : 0,
                            'disablekb':        isAdmin ? 0 : 1,
                            'autoplay':         1,
                            'mute':             1,
                            'rel':              0,
                            'modestbranding':   1,
                            'iv_load_policy':   3,
                        },
                        events: {
                            'onReady': (event) => {
                                if (!isAdmin) {
                                    document.getElementById('yt-player').style.pointerEvents = 'none';
                                }
                                setupYouTubeSync(channel, isAdmin, event.target);
                            },
                            'onError': (e) => {
                                // Error 150 / 101 = embed restricted by owner
                                // Fall back to yt-dlp server-side extraction
                                if (e.data === 150 || e.data === 101) {
                                    console.warn('[YT] Embed restricted (code', e.data, '), falling back to yt-dlp extraction…');
                                    ytPlayerDiv.style.display = 'none';
                                    video.style.display = 'block';
                                    if (!isAdmin) { video.removeAttribute('controls'); video.style.pointerEvents = 'none'; }
                                    else { video.setAttribute('controls', 'controls'); }
                                    document.getElementById('room-header').innerText = `Room: ${roomId} — Extracting stream…`;
                                    fetch(`/youtube-stream?url=${encodeURIComponent(m3u8_url)}`)
                                        .then(r => r.json())
                                        .then(ytData => {
                                            if (ytData.error) return alert('Fallback error: ' + ytData.error);
                                            document.getElementById('room-header').innerText = `Room: ${roomId} — ${ytData.title}`;
                                            video.src = `/proxy-video?url=${encodeURIComponent(ytData.stream_url)}`;
                                            video.play().catch(() => {});
                                            setupNativeVideoSync(channel, isAdmin, video);
                                        })
                                        .catch(err => alert('Stream extraction failed: ' + err.message));
                                } else {
                                    console.error('[YT] Player error code:', e.data);
                                    alert(`YouTube player error (code ${e.data}).`);
                                }
                            }
                        }
                    });
                };

                if (ytAPIReady) initYTPlayer();
                else pendingYtInit = initYTPlayer;

            } else {
                // ── NATIVE VIDEO (mp4, m3u8, proxy) ────────────────────────────
                video.style.display    = 'block';
                ytPlayerDiv.style.display = 'none';

                if (!isAdmin) { video.removeAttribute('controls'); video.style.pointerEvents = 'none'; }
                else { video.setAttribute('controls', 'controls'); }

                const isM3u8           = m3u8_url.toLowerCase().includes('.m3u8');
                const isProtectedDomain = m3u8_url.includes('hakunaymatata.com');
                let videoUrl = m3u8_url;

                if (isM3u8 && !isProtectedDomain && !referer_url) {
                    // Route through AdBlockService (strips ad segments from playlist)
                    videoUrl = `/clean-playlist?url=${encodeURIComponent(m3u8_url)}`;
                } else {
                    videoUrl = `/proxy-video?url=${encodeURIComponent(m3u8_url)}`;
                    if (referer_url) videoUrl += `&referer=${encodeURIComponent(referer_url)}`;
                }

                if (isM3u8 && !isProtectedDomain) {
                    if (Hls.isSupported()) {
                        if (hls) hls.destroy();
                        hls = new Hls();
                        hls.loadSource(videoUrl);
                        hls.attachMedia(video);
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = videoUrl;
                    }
                } else {
                    video.src = videoUrl;
                }

                setupNativeVideoSync(channel, isAdmin, video);
            }

            // ── Viewer Count ───────────────────────────────────────────────────
            channel
                .here(users  => { liveUsers = users; updateViewers(liveUsers); })
                .joining(user => { liveUsers.push(user); updateViewers(liveUsers); })
                .leaving(user => { liveUsers = liveUsers.filter(u => u.id !== user.id); updateViewers(liveUsers); });
        }

        // ─── YouTube Sync ─────────────────────────────────────────────────────────
        function setupYouTubeSync(channel, isAdmin, player) {
            let lastKnownTime = 0;

            if (isAdmin) {
                setInterval(() => {
                    const state = player.getPlayerState();
                    const currentTime = player.getCurrentTime();
                    if (state === YT.PlayerState.PLAYING || Math.abs(currentTime - lastKnownTime) > 0.5) {
                        channel.whisper('sync-yt', { time: currentTime, state, rate: player.getPlaybackRate() });
                        lastKnownTime = currentTime;
                    }
                }, 1000);

                player.addEventListener('onStateChange', (e) => {
                    channel.whisper('sync-yt', { time: player.getCurrentTime(), state: e.data, rate: player.getPlaybackRate() });
                });
            } else {
                channel.listenForWhisper('sync-yt', (e) => {
                    if (Math.abs(player.getCurrentTime() - e.time) > 0.5) player.seekTo(e.time, true);
                    if (e.rate && player.getPlaybackRate() !== e.rate) player.setPlaybackRate(e.rate);
                    const s = player.getPlayerState();
                    if (e.state === YT.PlayerState.PLAYING && s !== YT.PlayerState.PLAYING) player.playVideo();
                    else if (e.state === YT.PlayerState.PAUSED && s !== YT.PlayerState.PAUSED) player.pauseVideo();
                });
            }
        }

        // ─── Native Video Sync ────────────────────────────────────────────────────
        function setupNativeVideoSync(channel, isAdmin, video) {
            channel.listenForWhisper('sync', (e) => {
                if (isAdmin) return;
                if (e.rate !== undefined && video.playbackRate !== e.rate) video.playbackRate = e.rate;
                if (Math.abs(video.currentTime - e.time) > 0.5) video.currentTime = e.time;
                if (e.paused && !video.paused) video.pause();
                else if (!e.paused && video.paused) video.play().catch(() => {});
            });

            if (isAdmin) {
                const getPayload = () => ({ time: video.currentTime, paused: video.paused, rate: video.playbackRate });
                channel.joining(() => channel.whisper('sync', getPayload()));
                video.addEventListener('play',       () => channel.whisper('sync', getPayload()));
                video.addEventListener('pause',      () => channel.whisper('sync', getPayload()));
                video.addEventListener('seeked',     () => channel.whisper('sync', getPayload()));
                video.addEventListener('seeking',    () => channel.whisper('sync', getPayload()));
                video.addEventListener('ratechange', () => channel.whisper('sync', getPayload()));
                setInterval(() => { if (!video.paused) channel.whisper('sync', getPayload()); }, 2000);
            }
        }

        // ─── UI ───────────────────────────────────────────────────────────────────
        function updateViewers(users) {
            document.getElementById('count').textContent = users.length;
            document.getElementById('viewer-list').innerHTML = users.map(u => `<li>${u.name}</li>`).join('');
        }

        function leaveRoom() {
            if (currentRoomId) Echo.leave(`room.${currentRoomId}`);
            location.reload();
        }

        // ─── Auto-fill from URL params (?join=1&roomId=xx&key=yy) ────────────────
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('join') || urlParams.has('roomId')) {
            document.getElementById('create-form').style.display = 'none';
            document.getElementById('join-form').style.display   = 'block';
            if (urlParams.has('roomId')) document.getElementById('room-id').value    = urlParams.get('roomId');
            if (urlParams.has('key'))    document.getElementById('access-key').value = urlParams.get('key');
        }
    </script>
</body>
</html>