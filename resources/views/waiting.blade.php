<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hustel — Join Request</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:        #07070f;
            --surface:   rgba(255,255,255,0.04);
            --border:    rgba(255,255,255,0.08);
            --accent:    #7c3aed;
            --accent-lt: #a78bfa;
            --text:      #e2e8f0;
            --muted:     #64748b;
            --green:     #10b981;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: var(--accent);
            border-radius: 50%;
            filter: blur(130px);
            opacity: 0.12;
            top: -200px; left: -200px;
            pointer-events: none;
        }

        .card {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            margin: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(20px);
            text-align: center;
        }

        /* ── States ── */
        #state-form   { display: block; }
        #state-waiting { display: none; }
        #state-denied  { display: none; }

        .room-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(124,58,237,0.15);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent-lt);
            margin-bottom: 20px;
        }
        .room-badge::before {
            content: '';
            width: 6px; height: 6px;
            background: var(--accent-lt);
            border-radius: 50%;
        }

        h2 { font-size: 22px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.3px; }
        .sub { font-size: 13px; color: var(--muted); margin-bottom: 28px; line-height: 1.6; }

        .field { text-align: left; margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .field input {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 14px;
            color: var(--text);
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field input::placeholder { color: var(--muted); }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
        }

        .btn {
            width: 100%; padding: 13px;
            border: none; border-radius: 10px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            color: #fff;
            box-shadow: 0 4px 20px rgba(124,58,237,0.35);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 28px rgba(124,58,237,0.5); }
        .btn-primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

        /* ── Waiting animation ── */
        .pulse-ring {
            position: relative;
            width: 80px; height: 80px;
            margin: 0 auto 24px;
        }
        .pulse-ring::before, .pulse-ring::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 2px solid var(--accent);
            border-radius: 50%;
            animation: pulseRing 2s ease-out infinite;
        }
        .pulse-ring::after { animation-delay: 1s; }
        .pulse-ring-inner {
            position: absolute;
            inset: 20px;
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        @keyframes pulseRing {
            0%   { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.7); opacity: 0; }
        }

        .waiting-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .waiting-sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .dots::after {
            content: '';
            animation: dots 1.5s steps(4, end) infinite;
        }
        @keyframes dots {
            0%   { content: ''; }
            25%  { content: '.'; }
            50%  { content: '..'; }
            75%  { content: '...'; }
        }

        .uid-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 11px;
            color: var(--muted);
            font-family: 'Courier New', monospace;
        }

        /* ── Denied State ── */
        .denied-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
    </style>
</head>
<body>
<div class="card">
    <!-- ── State: Enter Name Form ── -->
    <div id="state-form">
        <div class="room-badge">Room {{ $roomId }}</div>
        <h2>You're invited! 🎉</h2>
        <p class="sub">Enter your name to request access. The host will let you in.</p>

        <div class="field">
            <label>Your Display Name</label>
            <input type="text" id="viewer-name" placeholder="e.g. Alex, Luna, 123…" maxlength="40" />
        </div>

        <button class="btn btn-primary" onclick="sendRequest()" id="req-btn">
            Request to Join
        </button>
    </div>

    <!-- ── State: Waiting for Approval ── -->
    <div id="state-waiting">
        <div class="pulse-ring">
            <div class="pulse-ring-inner">⏳</div>
        </div>
        <div class="waiting-name" id="waiting-name-label"></div>
        <div class="waiting-sub">Waiting for the host to approve you<span class="dots"></span></div>
        <div class="uid-tag">
            <span>Your ID:</span>
            <span id="uid-display"></span>
        </div>
    </div>

    <!-- ── State: Denied ── -->
    <div id="state-denied">
        <span class="denied-icon">🚫</span>
        <h2>Request Declined</h2>
        <p class="sub">The host declined your join request. You can try again with a different name.</p>
        <button class="btn btn-primary" onclick="resetForm()">Try Again</button>
    </div>
</div>

<script>
    const ROOM_ID  = '{{ $roomId }}';
    const ROOM_KEY = '{{ $roomKey }}';

    // Register Ad-Blocker SW
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/ad-blocker-sw.js', { scope: '/' })
            .catch(e => console.warn('[AdBlock SW]', e));
    }

    let tempId = null;
    let viewerName = '';
    let channel = null;

    function generateId() {
        return 'usr_' + Math.random().toString(36).substr(2, 8).toUpperCase();
    }

    function setState(state) {
        document.getElementById('state-form').style.display    = state === 'form'    ? 'block' : 'none';
        document.getElementById('state-waiting').style.display = state === 'waiting' ? 'block' : 'none';
        document.getElementById('state-denied').style.display  = state === 'denied'  ? 'block' : 'none';
    }

    async function sendRequest() {
        viewerName = document.getElementById('viewer-name').value.trim();
        if (!viewerName) {
            document.getElementById('viewer-name').focus();
            return;
        }

        tempId = generateId();
        document.getElementById('req-btn').disabled = true;

        // Show waiting UI
        document.getElementById('waiting-name-label').textContent = viewerName;
        document.getElementById('uid-display').textContent = tempId;
        setState('waiting');

        // Log in as a guest so the presence channel can authenticate
        await fetch(`/rooms/${ROOM_ID}?key=${ROOM_KEY}&username=${encodeURIComponent(viewerName)}`)
            .catch(() => {});

        // Join lobby presence channel and whisper the join request
        channel = Echo.join(`lobby.${ROOM_ID}`)
            .here(() => {
                // Once connected, whisper the join request to the admin
                channel.whisper('join-request', { name: viewerName, tempId: tempId });
            })
            .listenForWhisper('approved', (e) => {
                if (e.tempId === tempId) {
                    // Admin approved us — redirect to the actual room
                    window.location.href = `/room/${ROOM_ID}?key=${ROOM_KEY}&name=${encodeURIComponent(viewerName)}&uid=${tempId}`;
                }
            })
            .listenForWhisper('rejected', (e) => {
                if (e.tempId === tempId) {
                    setState('denied');
                }
            });
    }

    function resetForm() {
        if (channel) { Echo.leave(`lobby.${ROOM_ID}`); channel = null; }
        document.getElementById('viewer-name').value = '';
        document.getElementById('req-btn').disabled = false;
        setState('form');
    }
</script>
</body>
</html>
