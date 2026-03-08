<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hustel — Create a Stream Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --radius:    16px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Ambient background orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 600px; height: 600px;
            background: var(--accent);
            top: -150px; left: -150px;
        }
        body::after {
            width: 500px; height: 500px;
            background: #1d4ed8;
            bottom: -100px; right: -100px;
        }

        .page { position: relative; z-index: 1; width: 100%; padding: 24px; }

        /* ── Logo ── */
        .logo {
            text-align: center;
            margin-bottom: 48px;
        }
        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            border-radius: 14px;
            margin-bottom: 14px;
            box-shadow: 0 0 40px rgba(124,58,237,0.4);
        }
        .logo-icon svg { width: 28px; height: 28px; fill: #fff; }
        .logo h1 { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .logo p  { color: var(--muted); font-size: 14px; margin-top: 6px; }

        /* ── Card ── */
        .card {
            max-width: 520px;
            margin: 0 auto;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 36px 40px;
            backdrop-filter: blur(20px);
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .card-sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* ── Form ── */
        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .field input {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            color: var(--text);
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .field input::placeholder { color: var(--muted); }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
        }
        .field-hint {
            font-size: 11px;
            color: var(--muted);
            margin-top: 6px;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 24px 0;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-family: inherit;
            width: 100%;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            color: #fff;
            box-shadow: 0 4px 20px rgba(124,58,237,0.35);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 28px rgba(124,58,237,0.5);
        }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn svg { width: 18px; height: 18px; }

        /* ── Type selector ── */
        .type-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .type-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }
        .type-card:hover { border-color: rgba(124,58,237,0.4); }
        .type-card.active {
            border-color: var(--accent);
            background: rgba(124,58,237,0.1);
        }
        .type-card .type-icon { font-size: 20px; margin-bottom: 6px; }
        .type-card .type-name { font-size: 13px; font-weight: 600; }
        .type-card .type-desc { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* ── Toast ── */
        .toast {
            display: none;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
        }
        .toast.error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #fca5a5; }
        .toast.loading { background: rgba(124,58,237,0.1); border: 1px solid rgba(124,58,237,0.3); color: var(--accent-lt); }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.2);
            border-top-color: var(--accent-lt);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<div class="page">
    <!-- Logo -->
    <div class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24"><path d="M4 8L12 3L20 8V16L12 21L4 16V8Z"/><path d="M12 3V21M4 8L20 16M20 8L4 16" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
        <h1>Hustel</h1>
        <p>Watch together, in real time.</p>
        
        <form style="margin-top:20px;" method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button style="background:transparent; border:1px solid rgba(255,255,255,0.1); color:var(--text); padding:6px 12px; border-radius:8px; cursor:pointer;" type="submit">Logout Admin</button>
        </form>
    </div>

    <!-- Create Room Card -->
    <div class="card">
        <div class="card-title">Create a Stream Room</div>
        <div class="card-sub">Paste any video link below. Viewers you approve will watch in perfect sync.</div>

        <!-- Video type quick-select -->
        <div class="type-grid">
            <div class="type-card active" onclick="selectType('yt')" id="type-yt">
                <div class="type-icon">▶️</div>
                <div class="type-name">YouTube</div>
                <div class="type-desc">youtube.com / youtu.be</div>
            </div>
            <div class="type-card" onclick="selectType('stream')" id="type-stream">
                <div class="type-icon">📡</div>
                <div class="type-name">Live Stream</div>
                <div class="type-desc">m3u8 / HLS / mp4</div>
            </div>
        </div>

        <div class="field">
            <label>Video URL</label>
            <input type="url" id="video-url"
                   placeholder="https://youtube.com/watch?v=... or m3u8 link" />
        </div>

        <div class="field" id="referer-field" style="display:none;">
            <label>Referer URL <span style="font-weight:400;text-transform:none;">(optional)</span></label>
            <input type="url" id="referer-url" placeholder="https://siteurl.com/page" />
            <div class="field-hint">Required for some protected streams to bypass Referer checks.</div>
        </div>

        <button class="btn btn-primary" onclick="createRoom()" id="create-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Create Room
        </button>

        <div class="toast loading" id="toast-loading">
            <div class="spinner"></div>
            Creating your room…
        </div>
        <div class="toast error" id="toast-error">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span id="toast-error-msg">Something went wrong.</span>
        </div>
    </div>
</div>

<script>
    // Register service worker (ad blocker)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/ad-blocker-sw.js', { scope: '/' })
            .catch(e => console.warn('[AdBlock SW]', e));
    }

    let selectedType = 'yt';

    function selectType(type) {
        selectedType = type;
        document.getElementById('type-yt').classList.toggle('active', type === 'yt');
        document.getElementById('type-stream').classList.toggle('active', type === 'stream');
        document.getElementById('referer-field').style.display = type === 'stream' ? 'block' : 'none';
    }

    async function createRoom() {
        const url    = document.getElementById('video-url').value.trim();
        const refUrl = document.getElementById('referer-url').value.trim();

        if (!url) {
            showError('Please paste a video URL first.');
            return;
        }

        const btn = document.getElementById('create-btn');
        btn.disabled = true;
        showLoading(true);
        hideError();

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res  = await fetch('/rooms', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body:    JSON.stringify({ m3u8_url: url, referer_url: refUrl }),
            });

            if (!res.ok) throw new Error('Server error ' + res.status);
            const data = await res.json();

            // Redirect admin to the room view
            window.location.href = `/room/${data.room_id}?key=${data.access_key}&name=Admin+%28Host%29`;
        } catch (err) {
            showError(err.message);
            btn.disabled = false;
            showLoading(false);
        }
    }

    function showLoading(v) {
        document.getElementById('toast-loading').style.display = v ? 'flex' : 'none';
    }
    function showError(msg) {
        const t = document.getElementById('toast-error');
        document.getElementById('toast-error-msg').textContent = msg;
        t.style.display = 'flex';
    }
    function hideError() {
        document.getElementById('toast-error').style.display = 'none';
    }
</script>
</body>
</html>
