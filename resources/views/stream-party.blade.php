<!DOCTYPE html>
<html>
<head>
    <title>Stream Party</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script> <!-- For m3u8 -->
    @vite(['resources/css/app.css', 'resources/js/app.js']) <!-- Laravel Echo -->
</head>
<body>
    <div id="create-form" style="display: block;">
        <h2>Create Room</h2>
        <input type="url" id="m3u8-url" placeholder="Paste m3u8 link" />
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
        <!-- Add autoplay muted so it starts automatically in most browsers -->
        <video id="video" controls autoplay muted width="640" height="360"></video>
        <div id="viewers">
            <h3>Live Viewers: <span id="count">0</span></h3>
            <ul id="viewer-list"></ul>
        </div>
        <button onclick="leaveRoom()">Leave</button>
    </div>

    <script>
        let currentRoomId = null; // Track room (Changed from const to let)
        let hls = null;
        let liveUsers = [];

        async function createRoom() {
            const url = document.getElementById('m3u8-url').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch('/rooms', { 
                method: 'POST', 
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }, 
                body: JSON.stringify({m3u8_url: url}) 
            });
            const data = await res.json();
            const shareUrl = `${window.location.origin}/?join=1&roomId=${data.room_id}&key=${data.access_key}`;
            
            // Auto join as Admin
            localStorage.setItem('adminRoom', data.room_id);
            document.getElementById('room-id').value = data.room_id;
            document.getElementById('access-key').value = data.access_key;
            document.getElementById('username').value = 'Admin (Host)';
            
            await joinRoom();
            
            // Show share link in the room
            const shareEl = document.getElementById('room-share-link');
            shareEl.style.display = 'block';
            shareEl.innerHTML = `<strong>Share this link to invite others:</strong> <a href="${shareUrl}" target="_blank">${shareUrl}</a>`;
        }

        async function joinRoom() {
            const roomId = document.getElementById('room-id').value;
            const key = document.getElementById('access-key').value;
            const username = document.getElementById('username').value || 'Anon';

            // Validate key, get stream, and login as guest
            const res = await fetch(`/rooms/${roomId}?key=${key}&username=${encodeURIComponent(username)}`);
            if (!res.ok) return alert('Invalid room/key');
            const {m3u8_url} = await res.json();

            // Hide forms, show video
            document.getElementById('create-form').style.display = 'none';
            document.getElementById('join-form').style.display = 'none';
            document.getElementById('room-view').style.display = 'block';
            document.getElementById('room-header').innerText = `Room: ${roomId}`;
            currentRoomId = roomId;

            const isAdmin = localStorage.getItem('adminRoom') == roomId;
            const video = document.getElementById('video');
            
            if (!isAdmin) {
                video.removeAttribute('controls');
                video.style.pointerEvents = 'none';
            } else {
                video.setAttribute('controls', 'controls');
            }

            // Play stream with HLS.js or natively for mp4
            if (m3u8_url.toLowerCase().includes('.m3u8')) {
                if (Hls.isSupported()) {
                    hls = new Hls();
                    hls.loadSource(m3u8_url);
                    hls.attachMedia(video);
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = m3u8_url;
                }
            } else {
                // Direct video link (mp4, webm, etc.)
                video.src = m3u8_url;
            }

            // Join WebSocket channel
            const channel = Echo.join(`room.${roomId}`);
            
            channel.here((users) => {
                    liveUsers = users;
                    updateViewers(liveUsers);
                })
                .joining((user) => {
                    console.log(user.name + ' joined');
                    liveUsers.push(user);
                    updateViewers(liveUsers);
                    
                    if (isAdmin) {
                        channel.whisper('sync', { time: video.currentTime, paused: video.paused });
                    }
                })
                .leaving((user) => {
                    console.log(user.name + ' left');
                    liveUsers = liveUsers.filter(u => u.id !== user.id);
                    updateViewers(liveUsers);
                })
                .listenForWhisper('sync', (e) => {
                    if (isAdmin) return;
                    
                    if (Math.abs(video.currentTime - e.time) > 2) {
                        video.currentTime = e.time;
                    }
                    if (e.paused && !video.paused) {
                        video.pause();
                    } else if (!e.paused && video.paused) {
                        video.play().catch(() => console.log('Autoplay blocked'));
                    }
                });

            if (isAdmin) {
                video.addEventListener('play', () => channel.whisper('sync', { time: video.currentTime, paused: false }));
                video.addEventListener('pause', () => channel.whisper('sync', { time: video.currentTime, paused: true }));
                video.addEventListener('seeked', () => channel.whisper('sync', { time: video.currentTime, paused: video.paused }));
                
                setInterval(() => {
                    if (!video.paused) {
                        channel.whisper('sync', { time: video.currentTime, paused: false });
                    }
                }, 4000);
            }
        }

        function updateViewers(users) {
            document.getElementById('count').textContent = users.length;
            const list = document.getElementById('viewer-list');
            list.innerHTML = users.map(u => `<li>${u.name}</li>`).join('');
        }

        function leaveRoom() {
            if (currentRoomId) {
                Echo.leave(`room.${currentRoomId}`);
            }
            location.reload(); // Simple reset
        }

        // Show join form if ?join param or roomId param
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('join') || urlParams.has('roomId')) {
            document.getElementById('create-form').style.display = 'none';
            document.getElementById('join-form').style.display = 'block';
            
            if (urlParams.has('roomId')) {
                document.getElementById('room-id').value = urlParams.get('roomId');
            }
            if (urlParams.has('key')) {
                document.getElementById('access-key').value = urlParams.get('key');
            }
        }
    </script>
</body>
</html>