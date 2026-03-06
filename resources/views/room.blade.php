<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hustel — Room {{ $roomId }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:        #07070f;
            --surface:   rgba(255,255,255,0.04);
            --surface2:  rgba(255,255,255,0.07);
            --border:    rgba(255,255,255,0.08);
            --accent:    #7c3aed;
            --accent-lt: #a78bfa;
            --text:      #e2e8f0;
            --muted:     #64748b;
            --green:     #10b981;
            --red:       #ef4444;
            --sidebar-w: 320px;
            --topbar-h:  56px;
        }

        /* ── Reset & Base ── */
        html, body {
            height: 100%;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* ── Top Bar ── */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: var(--topbar-h);
            background: rgba(7,7,15,0.85);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 200;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            flex-shrink: 0;
        }
        .topbar-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .logo-sm {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px; height: 30px;
            background: linear-gradient(135deg, var(--accent), #4f46e5);
            border-radius: 8px;
            flex-shrink: 0;
        }
        .logo-sm svg { width: 15px; height: 15px; fill: #fff; }
        .room-label {
            font-size: 13px; font-weight: 700;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .room-label span { color: var(--muted); font-weight: 400; }
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 100px;
            padding: 3px 9px;
            font-size: 10px; font-weight: 700;
            color: #fca5a5;
            flex-shrink: 0;
        }
        .live-badge::before {
            content: '';
            width: 5px; height: 5px;
            background: var(--red);
            border-radius: 50%;
            animation: livePulse 1.5s ease-in-out infinite;
        }
        @keyframes livePulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }
        .topbar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-sm {
            padding: 6px 12px; border-radius: 8px;
            font-size: 12px; font-weight: 600;
            cursor: pointer; border: none; font-family: inherit;
            transition: all 0.18s;
            white-space: nowrap;
        }
        .btn-ghost {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }
        .btn-ghost:hover { background: var(--surface2); }
        .btn-danger {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }
        .btn-danger:hover { background: rgba(239,68,68,0.2); }
        /* Hide invite button text on very small screens */
        @media (max-width: 420px) {
            .btn-invite-text { display: none; }
        }

        /* ── Share Banner ── */
        .share-banner {
            display: flex; align-items: center; gap: 10px;
            background: rgba(124,58,237,0.08);
            border-bottom: 1px solid rgba(124,58,237,0.2);
            padding: 8px 16px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }
        .share-banner-label { font-size: 12px; font-weight: 600; color: var(--accent-lt); white-space: nowrap; }
        .share-url {
            flex: 1;
            min-width: 0;
            font-size: 11px;
            color: var(--muted);
            font-family: 'Courier New', monospace;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .copy-btn {
            padding: 4px 10px;
            background: rgba(124,58,237,0.2);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 6px;
            color: var(--accent-lt);
            font-size: 11px; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: all 0.18s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .copy-btn:hover { background: rgba(124,58,237,0.35); }

        /* ════════════════════════════════════════
           MAIN LAYOUT — Desktop first (≥1024px)
        ════════════════════════════════════════ */
        .main {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr var(--sidebar-w);
            grid-template-rows: 1fr;
            min-height: 0;
            overflow: hidden;
        }

        /* ── Video Area ── */
        .video-area {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
            overflow-y: auto;
        }
        .player-wrap {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .player-wrap video,
        .player-wrap #yt-player,
        .player-wrap iframe { width: 100% !important; height: 100% !important; display: block; }
        #player-wrap:-webkit-full-screen { aspect-ratio: auto; width: 100%; height: 100%; border-radius: 0; border: none; }
        #player-wrap:fullscreen          { aspect-ratio: auto; width: 100%; height: 100%; border-radius: 0; border: none; }
        #video { display: block; }
        #yt-player { display: none; }

        .viewer-controls {
            position: absolute; bottom: 12px; right: 12px;
            display: flex; gap: 8px; z-index: 10;
        }
        .v-btn {
            background: rgba(0,0,0,0.65); color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 7px 11px; border-radius: 8px;
            cursor: pointer; font-size: 12px; font-weight: 600;
            backdrop-filter: blur(4px); transition: 0.2s;
        }
        .v-btn:hover { background: rgba(0,0,0,0.85); }

        .loading-overlay {
            position: absolute; inset: 0;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.8);
            gap: 14px;
            border-radius: inherit;
        }
        .loading-overlay.show { display: flex; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner-lg {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: var(--accent-lt);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        .loading-label { font-size: 13px; color: var(--muted); }

        /* ── Sidebar ── */
        .sidebar {
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
        }

        /* ── Tab / Panel Switcher Bar ──
           Shown on ALL screen sizes (mobile + desktop).
           Each breakpoint styles it differently.           */
        .sidebar-tabs {
            flex-shrink: 0;
            border-bottom: 1px solid var(--border);
            background: rgba(0,0,0,0.3);
        }
        .sidebar-tabs-inner {
            display: flex;
        }

        /* ── DESKTOP tab button style (default) ── */
        .tab-btn {
            flex: 1;
            padding: 13px 6px 11px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--muted);
            font-size: 11px; font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.18s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            letter-spacing: 0.3px;
            position: relative;
        }
        .tab-btn-icon {
            font-size: 18px;
            line-height: 1;
            transition: transform 0.18s;
        }
        .tab-btn:hover .tab-btn-icon { transform: scale(1.15); }
        .tab-btn-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .tab-btn.active {
            color: var(--accent-lt);
            border-bottom-color: var(--accent);
            background: rgba(124,58,237,0.05);
        }
        .tab-btn.active .tab-btn-icon { transform: scale(1.1); }
        .tab-notif {
            display: none;
            width: 6px; height: 6px;
            background: #f59e0b;
            border-radius: 50%;
            position: absolute;
            top: 8px; right: calc(50% - 22px);
            animation: dotPulse 1.8s ease-in-out infinite;
        }
        @keyframes dotPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(0.7); }
        }
        .tab-notif.show { display: block; }

        /* Numeric request count badge on the bell icon */
        .req-tab-badge {
            display: none;
            position: absolute;
            top: -6px; right: -10px;
            min-width: 18px; height: 18px;
            background: #ef4444;
            border: 2px solid var(--bg);
            border-radius: 100px;
            font-size: 10px; font-weight: 800;
            color: #fff;
            align-items: center;
            justify-content: center;
            line-height: 1;
            padding: 0 4px;
            animation: badgePop 0.25s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes badgePop {
            from { transform: scale(0); }
            to   { transform: scale(1); }
        }

        /* ── Tab Panes ── */
        .tab-pane { display: none; flex: 1; flex-direction: column; min-height: 0; overflow: hidden; }
        .tab-pane.active { display: flex; }

        .panel {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        .panel-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px 10px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .panel-title {
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.6px; text-transform: uppercase;
            color: var(--muted);
            display: flex; align-items: center; gap: 8px;
        }
        .count-badge {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 1px 7px;
            font-size: 10px; font-weight: 700;
            color: var(--text);
        }
        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .panel-body::-webkit-scrollbar { width: 4px; }
        .panel-body::-webkit-scrollbar-track { background: transparent; }
        .panel-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* ── Viewer Card ── */
        .viewer-card {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px;
            border-radius: 10px;
            transition: background 0.15s;
            margin-bottom: 3px;
        }
        .viewer-card:hover { background: var(--surface); }
        .avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
            flex-shrink: 0;
        }
        .viewer-info { flex: 1; min-width: 0; }
        .viewer-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .viewer-uid  { font-size: 10px; color: var(--muted); font-family: 'Courier New', monospace; }
        .host-chip {
            padding: 2px 8px; border-radius: 100px;
            font-size: 10px; font-weight: 700;
            background: rgba(124,58,237,0.2);
            border: 1px solid rgba(124,58,237,0.3);
            color: var(--accent-lt);
            flex-shrink: 0;
        }

        /* ── Divider between panels ── */
        .panel-divider { border: none; border-top: 1px solid var(--border); margin: 0; flex-shrink: 0; }

        /* ── Request Card ── */
        .req-panel { flex-shrink: 0; }
        .req-card {
            background: rgba(251,191,36,0.06);
            border: 1px solid rgba(251,191,36,0.18);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
            display: flex; align-items: center; gap: 10px;
        }
        .req-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: rgba(251,191,36,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .req-info { flex: 1; min-width: 0; }
        .req-name { font-size: 13px; font-weight: 700; }
        .req-uid { font-size: 10px; color: var(--muted); font-family: 'Courier New', monospace; margin-top: 2px; }
        .req-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .btn-approve {
            padding: 5px 11px; border-radius: 7px; border: none;
            background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3);
            color: #6ee7b7; font-size: 11px; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: all 0.18s;
        }
        .btn-approve:hover { background: rgba(16,185,129,0.25); }
        .btn-reject {
            padding: 5px 10px; border-radius: 7px; border: none;
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5; font-size: 11px; font-weight: 700;
            cursor: pointer; font-family: inherit; transition: all 0.18s;
        }
        .btn-reject:hover { background: rgba(239,68,68,0.2); }
        .btn-kick {
            width: 26px; height: 26px;
            border-radius: 50%; border: none;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            color: #fca5a5;
            font-size: 13px; font-weight: 700;
            cursor: pointer; transition: all 0.18s;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-family: inherit;
        }
        .btn-kick:hover { background: rgba(239,68,68,0.3); color: #fff; transform: scale(1.1); }

        /* ── Chat Panel ── */
        .chat-panel {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            border-bottom: 1px solid var(--border);
            overflow: hidden;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 0;
        }
        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-track { background: transparent; }
        .chat-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .chat-msg { display: flex; flex-direction: column; gap: 4px; }
        .chat-header { display: flex; align-items: baseline; gap: 6px; }
        .chat-author { font-size: 11px; font-weight: 700; color: var(--muted); }
        .chat-author.host { color: var(--accent-lt); }
        .chat-author.me   { color: var(--green); }
        .chat-time { font-size: 9px; color: rgba(255,255,255,0.3); }

        .chat-bubble {
            background: var(--surface2);
            padding: 8px 12px;
            border-radius: 4px 12px 12px 12px;
            font-size: 13px;
            color: var(--text);
            line-height: 1.4;
            word-break: break-word;
            width: fit-content;
            max-width: 90%;
        }
        .chat-msg.me .chat-bubble {
            background: rgba(124,58,237,0.15);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 12px 4px 12px 12px;
            align-self: flex-end;
        }
        .chat-msg.me .chat-header { flex-direction: row-reverse; }

        .chat-input-area {
            padding: 10px 12px;
            border-top: 1px solid var(--border);
            background: rgba(0,0,0,0.2);
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }
        .chat-input {
            flex: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            color: var(--text);
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
            min-width: 0;
        }
        .chat-input:focus { border-color: var(--accent); }
        .btn-send {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .btn-send:hover { background: #6d28d9; }
        .btn-send:disabled { opacity: 0.5; cursor: not-allowed; }

        .empty-state {
            text-align: center;
            padding: 20px 16px;
            color: var(--muted);
            font-size: 12px;
        }
        .empty-state .empty-icon { font-size: 24px; margin-bottom: 8px; }

        /* ── Avatar colours ── */
        .av-0 { background: rgba(124,58,237,0.25); color: #a78bfa; }
        .av-1 { background: rgba(16,185,129,0.2);  color: #6ee7b7; }
        .av-2 { background: rgba(245,158,11,0.2);  color: #fcd34d; }
        .av-3 { background: rgba(239,68,68,0.2);   color: #fca5a5; }
        .av-4 { background: rgba(59,130,246,0.2);  color: #93c5fd; }
        .av-5 { background: rgba(236,72,153,0.2);  color: #f9a8d4; }

        /* ── Notification dot ── */
        .notif-dot {
            width: 7px; height: 7px;
            background: #f59e0b;
            border-radius: 50%;
            display: none;
        }
        .notif-dot.show { display: inline-block; }

        /* ════════════════════════════════════════
           DESKTOP  (≥768px) — ONE panel at a time, height = video
        ════════════════════════════════════════ */
        @media (min-width: 768px) {
            /* Sidebar stretches from top of main to bottom;
               the active pane is height-synced via JS.      */
            .sidebar {
                align-items: stretch;
                justify-content: flex-start;
            }
            /* Only the ACTIVE pane is shown — fill its JS-set height */
            .tab-pane        { display: none; flex: unset; }
            .tab-pane.active { display: flex; flex: unset; min-height: 0; overflow: hidden; }
            /* Panel inside a pane fills it */
            .tab-pane > .chat-panel,
            .tab-pane > .panel,
            .tab-pane > .panel.req-panel { flex: 1; min-height: 0; }
        }

        /* ════════════════════════════════════════
           TABLET  (768px – 1023px)
        ════════════════════════════════════════ */
        @media (max-width: 1023px) {
            :root { --sidebar-w: 280px; }
            .video-area { padding: 14px; }
            .player-wrap { border-radius: 10px; }
        }

        /* ════════════════════════════════════════
           MOBILE  (< 768px) — stacked layout
        ════════════════════════════════════════ */
        @media (max-width: 767px) {
            /* Stack video then sidebar vertically */
            .main {
                display: flex;
                flex-direction: column;
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* Video section — full width, compact padding */
            .video-area {
                padding: 10px;
                flex-shrink: 0;
            }
            .player-wrap { border-radius: 8px; }

            /* Sidebar fills remaining viewport height */
            .sidebar {
                border-left: none;
                border-top: 1px solid var(--border);
                flex: 1;
                min-height: 420px;
                display: flex;
                flex-direction: column;
            }

            /* Show tab bar on mobile */
            .sidebar-tabs { display: block; }

            /* Tab pane sizing — fill sidebar */
            .tab-pane { flex: 1; }

            /* Desktop-only panel wrappers — hide panel headers inside panes on mobile
               (the tab bar already labels them) */
            .tab-pane .panel-header { display: none; }
            /* Keep the chat-panel header hidden too */
            .chat-panel > .panel-header { display: none; }

            /* Chat panel fills its pane */
            .chat-panel {
                border-bottom: none;
                flex: 1;
            }

            /* Requests panel in mobile pane — allow scroll */
            .req-panel {
                flex: 1;
                min-height: 0;
                overflow-y: auto;
            }

            /* Topbar compact */
            .topbar { padding: 0 12px; gap: 8px; }
            .room-label { font-size: 12px; max-width: 120px; }
            .btn-sm { padding: 5px 10px; font-size: 11px; }

            /* Share banner compact */
            .share-banner { padding: 6px 12px; gap: 8px; }
            .share-banner-label { font-size: 11px; }
        }

        /* ════════════════════════════════════════
           VERY SMALL  (< 400px)
        ════════════════════════════════════════ */
        @media (max-width: 399px) {
            .live-badge { display: none; }
            .share-banner { display: none; } /* reclaim space */
            .viewer-controls .v-btn { font-size: 11px; padding: 6px 9px; }
        }

        /* ════════════════════════════════════════
           OTHER IMPROVEMENTS
        ════════════════════════════════════════ */

        /* 1. Lock the desktop page — no body scroll.
              Only the sidebar panels and video-area scroll internally. */
        @media (min-width: 768px) {
            body { overflow: hidden; height: 100vh; height: 100dvh; }
        }

        /* 2. Smooth scroll inside chat & panel bodies */
        .chat-messages,
        .panel-body { scroll-behavior: smooth; }

        /* 3. Chat message slide-up + fade entrance animation */
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .chat-msg {
            animation: msgIn 0.22s ease-out both;
        }

        /* 4. Improved focus ring on chat input (glowing border) */
        .chat-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.18);
        }

        /* 5. Send button micro-animation on hover */
        .btn-send:not(:disabled):hover {
            background: #6d28d9;
            transform: scale(1.06);
            box-shadow: 0 4px 14px rgba(124,58,237,0.4);
        }
        .btn-send { transition: background 0.2s, transform 0.15s, box-shadow 0.2s; }

        /* 6. Viewer card avatar pulse for the HOST */
        .host-chip {
            animation: hostGlow 3s ease-in-out infinite;
        }
        @keyframes hostGlow {
            0%, 100% { box-shadow: none; }
            50% { box-shadow: 0 0 8px rgba(124,58,237,0.5); }
        }

        /* 7. Request card glow on entrance */
        @keyframes reqIn {
            from { opacity: 0; transform: scale(0.97); }
            to   { opacity: 1; transform: scale(1); }
        }
        .req-card { animation: reqIn 0.2s ease-out both; }

        /* 8. Improved scrollbar on video-area (desktop) */
        .video-area::-webkit-scrollbar { width: 4px; }
        .video-area::-webkit-scrollbar-track { background: transparent; }
        .video-area::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* 9. Mobile tab pane smooth fade-in when switching */
        @media (max-width: 767px) {
            .tab-pane.active {
                animation: paneIn 0.18s ease-out both;
            }
            @keyframes paneIn {
                from { opacity: 0; }
                to   { opacity: 1; }
            }
        }

        /* 10. Video player subtle inner shadow to embed it in the bg */
        .player-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06);
            pointer-events: none;
            z-index: 2;
        }

        /* 11. Topbar left room-label — fade overflow with gradient */
        .room-label {
            -webkit-mask-image: linear-gradient(to right, black 80%, transparent 100%);
            mask-image: linear-gradient(to right, black 80%, transparent 100%);
        }

        /* 12. Tab btn hover state */
        .tab-btn:hover:not(.active) {
            background: var(--surface);
            color: var(--text);
        }
    </style>

</head>
<body>

<!-- ── Top Bar ── -->
<header class="topbar">
    <div class="topbar-left">
        <div class="logo-sm">
            <svg viewBox="0 0 24 24"><path d="M4 8L12 3L20 8V16L12 21L4 16V8Z"/></svg>
        </div>
        <span class="room-label">Hustel <span>/ Room {{ $roomId }}</span></span>
        <span class="live-badge">LIVE</span>
    </div>
    <div class="topbar-right">
        <button class="btn-sm btn-ghost" onclick="copyShareLink()">🔗 <span class="btn-invite-text">Copy Invite</span></button>
        <button class="btn-sm btn-danger" onclick="leaveRoom()">Leave</button>
    </div>
</header>

<!-- ── Share Banner ── -->
<div class="share-banner" id="share-banner">
    <span class="share-banner-label">📨 Invite Link</span>
    <span class="share-url" id="join-url">{{ $joinUrl }}</span>
    <button class="copy-btn" onclick="copyShareLink()">Copy</button>
</div>

<!-- ── Main Layout ── -->
<div class="main">

    <!-- Video Area -->
    <div class="video-area">
        <div class="player-wrap" id="player-wrap">
            <video id="video" controls autoplay muted playsinline></video>
            <div id="yt-player"></div>
            <div class="loading-overlay show" id="loading-overlay">
                <div class="spinner-lg"></div>
                <span class="loading-label" id="loading-label">Loading stream…</span>
            </div>
            @if(!$isAdmin)
            <div class="viewer-controls">
                <button class="v-btn" id="audio-btn" onclick="toggleAudio()">🔇 Unmute</button>
                <button class="v-btn" onclick="toggleFullScreen()">⛶ Fullscreen</button>
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <aside class="sidebar">

        <!-- Tab Bar (desktop switcher + mobile tabs) -->
        <nav class="sidebar-tabs" id="sidebar-tabs">
            <div class="sidebar-tabs-inner">
                <button class="tab-btn active" id="tab-chat" onclick="switchTab('chat')">
                    <span class="tab-btn-icon">💬</span>
                    <span class="tab-btn-label">Chat</span>
                </button>
                <button class="tab-btn" id="tab-viewers" onclick="switchTab('viewers')">
                    <span class="tab-btn-icon">👥</span>
                    <span class="tab-btn-label">Viewers</span>
                </button>
                @if($isAdmin)
                <button class="tab-btn" id="tab-requests" onclick="switchTab('requests')">
                    <span class="tab-btn-icon" style="position:relative;display:inline-block;">
                        🔔
                        <span class="req-tab-badge" id="tab-req-badge"></span>
                    </span>
                    <span class="tab-btn-label">Requests</span>
                </button>
                @endif
            </div>
        </nav>

        <!-- ── Chat Pane ── -->
        <div class="tab-pane active" id="pane-chat">
            <div class="chat-panel">
                <div class="panel-header">
                    <span class="panel-title">💬 Live Chat</span>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="empty-state">
                        <div class="empty-icon">💭</div>
                        Say hello to the room!
                    </div>
                </div>
                <form class="chat-input-area" id="chat-form" onsubmit="sendChatMessage(event)">
                    <input type="text" id="chat-input" class="chat-input" placeholder="Type a message..." maxlength="1000" autocomplete="off" required>
                    <button type="submit" class="btn-send" id="chat-send-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- ── Viewers Pane ── -->
        <div class="tab-pane" id="pane-viewers">
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">
                        👥 Viewers
                        <span class="count-badge" id="viewer-count">0</span>
                    </span>
                </div>
                <div class="panel-body" id="viewer-list">
                    <div class="empty-state">
                        <div class="empty-icon">🌐</div>
                        No viewers yet
                    </div>
                </div>
            </div>
        </div>

        @if($isAdmin)
        <!-- ── Requests Pane ── -->
        <div class="tab-pane" id="pane-requests">
            <div class="panel req-panel" id="requests-panel">
                <div class="panel-header">
                    <span class="panel-title">
                        🔔 Requests
                        <span class="count-badge" id="req-count">0</span>
                        <span class="notif-dot" id="req-dot"></span>
                    </span>
                </div>
                <div class="panel-body" id="req-list">
                    <div class="empty-state" id="req-empty">
                        <div class="empty-icon">✅</div>
                        No pending requests
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Desktop stacked viewers + requests (shown only on desktop) -->
        <!-- On desktop the tab panes act as normal stacked containers -->

    </aside>
</div>

<script>
    // ── Config (injected from PHP) ────────────────────────────────────────────
    const ROOM_ID   = '{{ $roomId }}';
    const ROOM_KEY  = '{{ $key }}';
    const JOIN_URL  = '{{ $joinUrl }}';
    const M3U8_URL  = '{!! addslashes($room->m3u8_url) !!}';
    const REFERER   = '{!! addslashes($room->referer_url ?? "") !!}';
    const IS_ADMIN  = {{ $isAdmin ? 'true' : 'false' }};
    const CURRENT_USER_ID = {{ Auth::id() ?? 'null' }};
    const CURRENT_USER_NAME = IS_ADMIN ? 'Admin (Host)' : new URLSearchParams(window.location.search).get('name') || 'Viewer';

    // ── Ad-Blocker SW ────────────────────────────────────────────────────────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/ad-blocker-sw.js', { scope: '/' })
            .catch(e => console.warn('[AdBlock SW]', e));
    }

    // ── State ─────────────────────────────────────────────────────────────────
    let liveUsers    = [];
    let pendingReqs  = {};  // { tempId: { name, tempId } }
    let hls          = null;
    let ytPlayer     = null;
    let ytAPIReady   = false;
    let pendingYtInit = null;
    let lobbyChannel = null;
    let roomChannel  = null;
    let renderedMessageIds = new Set();

    const avatarClasses = ['av-0','av-1','av-2','av-3','av-4','av-5'];
    function avatarClass(str) {
        let h = 0;
        for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) & 0xffff;
        return avatarClasses[h % avatarClasses.length];
    }
    function initials(name) {
        return name.trim().split(/\s+/).map(w => w[0]).join('').toUpperCase().slice(0,2);
    }

    // ── YouTube IFrame Setup ─────────────────────────────────────────────────
    window.onYouTubeIframeAPIReady = function() {
        ytAPIReady = true;
        if (typeof pendingYtInit === 'function') { pendingYtInit(); pendingYtInit = null; }
    };
    (function() {
        const s = document.createElement('script');
        s.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(s);
    })();

    function extractYouTubeID(url) {
        const r = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i;
        const m = url.match(r);
        return m ? m[1] : null;
    }

    // ── Initialise Room ───────────────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', function() {
        initWebSocket(); // MUST be first so roomChannel exists before video sync sets up
        initVideo();
    });

    function setLoading(show, label = 'Loading stream…') {
        const ov = document.getElementById('loading-overlay');
        ov.classList.toggle('show', show);
        document.getElementById('loading-label').textContent = label;
    }

    function initVideo() {
        const video  = document.getElementById('video');
        const ytDiv  = document.getElementById('yt-player');
        const ytId   = extractYouTubeID(M3U8_URL);

        if (ytId) {
            video.style.display  = 'none';
            ytDiv.style.display  = 'block';
            setLoading(true, 'Loading YouTube player…');

            const build = () => {
                ytPlayer = new YT.Player('yt-player', {
                    height: '100%', width: '100%',
                    videoId: ytId,
                    host: 'https://www.youtube-nocookie.com',
                    playerVars: { 
                        controls: IS_ADMIN ? 1 : 0, 
                        disablekb: IS_ADMIN ? 0 : 1, 
                        autoplay: 1, mute: 1, rel: 0, modestbranding: 1, iv_load_policy: 3 
                    },
                    events: {
                        onReady: () => {
                            setLoading(false);
                            if (IS_ADMIN) {
                                setInterval(() => {
                                    if (ytPlayer.getPlayerState() === YT.PlayerState.PLAYING || ytPlayer.getPlayerState() === YT.PlayerState.PAUSED) {
                                        roomChannel?.whisper('sync', { 
                                            time: ytPlayer.getCurrentTime(), 
                                            paused: ytPlayer.getPlayerState() !== YT.PlayerState.PLAYING, 
                                            rate: ytPlayer.getPlaybackRate() 
                                        });
                                    }
                                }, 2000);
                            } else {
                                ytDiv.style.pointerEvents = 'none';
                            }
                        },
                        onStateChange: (e) => {
                            if (IS_ADMIN) {
                                roomChannel?.whisper('sync', { 
                                    time: ytPlayer.getCurrentTime(), 
                                    paused: e.data !== YT.PlayerState.PLAYING, 
                                    rate: ytPlayer.getPlaybackRate() 
                                });
                            }
                        },
                        onError: (e) => {
                            if (e.data === 150 || e.data === 101) {
                                ytDiv.style.display = 'none';
                                video.style.display = 'block';
                                if (!IS_ADMIN) { video.removeAttribute('controls'); video.style.pointerEvents = 'none'; }
                                setLoading(true, 'Embed restricted — extracting via yt-dlp…');
                                fetch(`/youtube-stream?url=${encodeURIComponent(M3U8_URL)}`)
                                    .then(r => r.json())
                                    .then(d => {
                                        if (d.error) { setLoading(false); alert(d.error); return; }
                                        video.src = `/proxy-video?url=${encodeURIComponent(d.stream_url)}`;
                                        video.play().catch(()=>{});
                                        setLoading(false);
                                        setupNativeSync(video);
                                    });
                            }
                        }
                    }
                });
            };
            if (ytAPIReady) build(); else pendingYtInit = build;

        } else {
            video.style.display = 'block';
            ytDiv.style.display = 'none';
            if (!IS_ADMIN) { video.removeAttribute('controls'); video.style.pointerEvents = 'none'; }
            setLoading(true);

            const isM3u8 = M3U8_URL.toLowerCase().includes('.m3u8');
            const isProtected = M3U8_URL.includes('hakunaymatata.com');
            let videoUrl = M3U8_URL;

            if (isM3u8 && !isProtected && !REFERER) {
                videoUrl = `/clean-playlist?url=${encodeURIComponent(M3U8_URL)}`;
            } else {
                videoUrl = `/proxy-video?url=${encodeURIComponent(M3U8_URL)}`;
                if (REFERER) videoUrl += `&referer=${encodeURIComponent(REFERER)}`;
            }

            if (isM3u8 && !isProtected) {
                if (Hls.isSupported()) {
                    hls = new Hls();
                    hls.loadSource(videoUrl);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, () => { video.play().catch(()=>{}); setLoading(false); });
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = videoUrl;
                    setLoading(false);
                }
            } else {
                video.src = videoUrl;
                video.addEventListener('loadedmetadata', () => setLoading(false), { once: true });
            }

            setupNativeSync(video);
        }
    }

    // ── Video Sync ────────────────────────────────────────────────────────────
    function setupNativeSync(video) {
        if (!roomChannel || !IS_ADMIN) return;
        const getPayload = () => ({ time: video.currentTime, paused: video.paused, rate: video.playbackRate });
        roomChannel.joining(() => roomChannel.whisper('sync', getPayload()));
        video.addEventListener('play',       () => roomChannel.whisper('sync', getPayload()));
        video.addEventListener('pause',      () => roomChannel.whisper('sync', getPayload()));
        video.addEventListener('seeked',     () => roomChannel.whisper('sync', getPayload()));
        video.addEventListener('ratechange', () => roomChannel.whisper('sync', getPayload()));
        setInterval(() => { if (!video.paused) roomChannel.whisper('sync', getPayload()); }, 2000);
    }

    // ── WebSocket ─────────────────────────────────────────────────────────────
    function initWebSocket() {
        // Join the main room channel (for viewer presence + video sync)
        roomChannel = Echo.join(`room.${ROOM_ID}`)
            .here(users  => { liveUsers = users; renderViewers(); })
            .joining(user => { liveUsers.push(user); renderViewers(); })
            .leaving(user => { 
                liveUsers = liveUsers.filter(u => u.id !== user.id);
                renderViewers();
                // Host left — silently redirect viewers home (no alert)
                if (!IS_ADMIN && user.name && user.name.includes('Host')) {
                    Echo.leave(`room.${ROOM_ID}`);
                    window.location.href = '/';
                }
            })
            .listenForWhisper('room-closed', () => {
                // Admin explicitly closed the room — redirect immediately
                if (!IS_ADMIN) {
                    Echo.leave(`room.${ROOM_ID}`);
                    window.location.href = '/';
                }
            })
            .listenForWhisper('kicked', (e) => {
                // Check if this viewer is the one being kicked
                if (!IS_ADMIN && CURRENT_USER_ID !== null && e.userId == CURRENT_USER_ID) {
                    Echo.leave(`room.${ROOM_ID}`);
                    window.location.href = '/';
                }
            })
            .listenForWhisper('sync', (e) => {
                if (IS_ADMIN) return;
                const video = document.getElementById('video');
                
                if (ytPlayer && typeof ytPlayer.getPlayerState === 'function' && document.getElementById('yt-player').style.display !== 'none') {
                    // Sync YouTube IFrame player
                    if (Math.abs(ytPlayer.getCurrentTime() - e.time) > 0.5) ytPlayer.seekTo(e.time, true);
                    if (e.rate !== undefined && ytPlayer.getPlaybackRate() !== e.rate) ytPlayer.setPlaybackRate(e.rate);
                    const state = ytPlayer.getPlayerState();
                    if (!e.paused && state !== YT.PlayerState.PLAYING) ytPlayer.playVideo();
                    else if (e.paused && state !== YT.PlayerState.PAUSED) ytPlayer.pauseVideo();
                } else if (video) {
                    // Sync Native video
                    if (e.rate !== undefined && video.playbackRate !== e.rate) video.playbackRate = e.rate;
                    if (Math.abs(video.currentTime - e.time) > 0.5) video.currentTime = e.time;
                    if (e.paused && !video.paused) video.pause();
                    else if (!e.paused && video.paused) video.play().catch(() => {});
                }
            })
            .listen('NewChatMessage', (e) => {
                appendChatMessage(e);
            });

        if (IS_ADMIN) {
            // Join lobby channel as admin to hear join requests
            lobbyChannel = Echo.join(`lobby.${ROOM_ID}`)
                .listenForWhisper('join-request', (e) => {
                    pendingReqs[e.tempId] = e;
                    renderRequests();
                });
        }
    }

    // ── Render Viewers ────────────────────────────────────────────────────────
    function renderViewers() {
        document.getElementById('viewer-count').textContent = liveUsers.length;
        const list = document.getElementById('viewer-list');
        if (!liveUsers.length) {
            list.innerHTML = `<div class="empty-state"><div class="empty-icon">🌐</div>No viewers yet</div>`;
            return;
        }
        list.innerHTML = liveUsers.map((u, i) => {
            const ac = avatarClass(u.name || '?');
            const init = initials(u.name || '?');
            const isHost = u.name && u.name.includes('Host');
            const kickBtn = (IS_ADMIN && !isHost)
                ? `<button class="btn-kick" onclick="kickViewer(${u.id})" title="Remove viewer">✕</button>`
                : '';
            return `
            <div class="viewer-card">
                <div class="avatar ${ac}">${init}</div>
                <div class="viewer-info">
                    <div class="viewer-name">${escHtml(u.name || 'Anon')}</div>
                    <div class="viewer-uid">ID: ${u.id}</div>
                </div>
                ${isHost ? '<span class="host-chip">HOST</span>' : ''}
                ${kickBtn}
            </div>`;
        }).join('');
    }

    // ── Render Requests ───────────────────────────────────────────────────────
    function renderRequests() {
        const reqs = Object.values(pendingReqs);
        document.getElementById('req-count').textContent = reqs.length;
        const dot = document.getElementById('req-dot');
        dot.classList.toggle('show', reqs.length > 0);
        // Numeric badge on the bell tab button
        const badge = document.getElementById('tab-req-badge');
        if (badge) {
            badge.textContent = reqs.length > 0 ? (reqs.length > 9 ? '9+' : reqs.length) : '';
            badge.style.display = reqs.length > 0 ? 'flex' : 'none';
        }

        const list = document.getElementById('req-list');
        if (!reqs.length) {
            list.innerHTML = `<div class="empty-state" id="req-empty"><div class="empty-icon">✅</div>No pending requests</div>`;
            return;
        }
        list.innerHTML = reqs.map(r => `
            <div class="req-card" id="req-${r.tempId}">
                <div class="req-avatar">👤</div>
                <div class="req-info">
                    <div class="req-name">${escHtml(r.name)}</div>
                    <div class="req-uid">${r.tempId}</div>
                </div>
                <div class="req-actions">
                    <button class="btn-approve" onclick="approveReq('${r.tempId}')">✓ Allow</button>
                    <button class="btn-reject"  onclick="rejectReq('${r.tempId}')">✕</button>
                </div>
            </div>`).join('');
    }

    function toggleFullScreen() {
        const wrap = document.getElementById('player-wrap');
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            if (wrap.requestFullscreen) wrap.requestFullscreen();
            else if (wrap.webkitRequestFullscreen) wrap.webkitRequestFullscreen();
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
        }
    }

    function toggleAudio() {
        const btn = document.getElementById('audio-btn');
        const video = document.getElementById('video');
        let isMuted = false;
        
        if (ytPlayer && typeof ytPlayer.isMuted === 'function' && document.getElementById('yt-player').style.display !== 'none') {
            if (ytPlayer.isMuted()) {
                ytPlayer.unMute();
                isMuted = false;
            } else {
                ytPlayer.mute();
                isMuted = true;
            }
        } else if (video) {
            video.muted = !video.muted;
            isMuted = video.muted;
        }
        
        btn.innerHTML = isMuted ? '🔇 Unmute' : '🔊 Mute';
    }

    function approveReq(tempId) {
        lobbyChannel.whisper('approved', { tempId: tempId, accessKey: ROOM_KEY });
        delete pendingReqs[tempId];
        renderRequests();
    }

    function rejectReq(tempId) {
        lobbyChannel.whisper('rejected', { tempId: tempId });
        delete pendingReqs[tempId];
        renderRequests();
    }

    // ── Chat Functionality ────────────────────────────────────────────────────
    
    function initChat() {
        const chatContainer = document.getElementById('chat-messages');
        chatContainer.innerHTML = `<div class="empty-state"><div class="spinner-lg" style="width:24px;height:24px;border-width:2px;margin: 0 auto 10px;"></div>Loading messages...</div>`;
        
        fetch(`/rooms/${ROOM_ID}/chat`)
            .then(res => res.json())
            .then(messages => {
                chatContainer.innerHTML = '';
                if (messages.length === 0) {
                    chatContainer.innerHTML = `<div class="empty-state"><div class="empty-icon">💭</div>Say hello to the room!</div>`;
                } else {
                    messages.forEach(msg => appendChatMessage(msg, false));
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            })
            .catch(err => {
                chatContainer.innerHTML = `<div class="empty-state">Failed to load chat.</div>`;
            });
    }

    function appendChatMessage(msg, scrollToBottom = true) {
        // Prevent duplicate appending
        if (renderedMessageIds.has(msg.id)) return;
        renderedMessageIds.add(msg.id);

        const chatContainer = document.getElementById('chat-messages');
        
        // Remove empty state if present
        const emptyState = chatContainer.querySelector('.empty-state');
        if (emptyState) emptyState.remove();

        const isMe = msg.user_name === CURRENT_USER_NAME;
        const isHost = msg.user_name.includes('Host');
        
        let authorClass = 'chat-author';
        if (isMe) authorClass += ' me';
        else if (isHost) authorClass += ' host';

        const timeStr = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

        const msgHtml = `
            <div class="chat-msg ${isMe ? 'me' : ''}">
                <div class="chat-header">
                    <span class="${authorClass}">${escHtml(msg.user_name)}</span>
                    <span class="chat-time">${timeStr}</span>
                </div>
                <div class="chat-bubble">${escHtml(msg.message)}</div>
            </div>
        `;
        
        chatContainer.insertAdjacentHTML('beforeend', msgHtml);
        
        if (scrollToBottom) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    async function sendChatMessage(e) {
        e.preventDefault();
        
        const input = document.getElementById('chat-input');
        const btn = document.getElementById('chat-send-btn');
        const text = input.value.trim();
        
        if (!text) return;
        
        input.disabled = true;
        btn.disabled = true;
        
        try {
            const res = await fetch(`/rooms/${ROOM_ID}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });

            if (res.ok) {
                // Backend will broadcast it back to us via Echo, so we might want to temporarily optimistic update here 
                // but since reverb is fast, listening to our own message via Echo is usually fine.
                // However, Reverb broadcast `toOthers()` means we won't receive our own message via websocket.
                // Thus we MUST append it manually.
                const newMsg = await res.json();
                appendChatMessage(newMsg);
                input.value = '';
            } else {
                console.error("Failed to send message", await res.text());
                alert("Failed to send message");
            }
        } catch (error) {
            console.error("Network error sending message", error);
        } finally {
            input.disabled = false;
            btn.disabled = false;
            input.focus();
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        initChat();
    });

    // ── Share / Leave ─────────────────────────────────────────────────────────
    function copyShareLink() {
        navigator.clipboard.writeText(JOIN_URL).then(() => {
            const btn = document.querySelector('.copy-btn');
            if (btn) { btn.textContent = '✓ Copied!'; setTimeout(() => btn.textContent = 'Copy', 2000); }
        });
    }

    function leaveRoom() {
        if (IS_ADMIN) {
            // Whisper room-closed so viewers redirect immediately, then leave
            roomChannel?.whisper('room-closed', {});
            setTimeout(() => {
                Echo.leave(`room.${ROOM_ID}`);
                Echo.leave(`lobby.${ROOM_ID}`);
                window.location.href = '/';
            }, 300);
        } else {
            Echo.leave(`room.${ROOM_ID}`);
            window.location.href = '/';
        }
    }

    function kickViewer(userId) {
        roomChannel?.whisper('kicked', { userId });
    }

    // ── Mobile + Desktop Panel Switching ─────────────────────────────────────
    function switchTab(name) {
        const panes = ['chat', 'viewers', 'requests'];
        panes.forEach(p => {
            const pane = document.getElementById('pane-' + p);
            const btn  = document.getElementById('tab-' + p);
            if (pane) pane.classList.toggle('active', p === name);
            if (btn)  btn.classList.toggle('active', p === name);
        });
        // Scroll chat to bottom when switching to it
        if (name === 'chat') {
            const msgs = document.getElementById('chat-messages');
            if (msgs) setTimeout(() => msgs.scrollTop = msgs.scrollHeight, 50);
        }
        // Sync the panel height to match the video on desktop
        syncPanelHeight();
    }

    // ── Sync sidebar panel height to video player height (desktop only) ───────
    function syncPanelHeight() {
        if (window.innerWidth < 768) return;
        const playerWrap = document.getElementById('player-wrap');
        if (!playerWrap) return;
        const h = playerWrap.offsetHeight;  // actual rendered height
        if (h <= 0) return;
        // Apply height to all panes (both active and hidden — so they're ready)
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.style.height = h + 'px';
        });
    }

    // Run on load (slight delay to let layout settle) and on every resize
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(syncPanelHeight, 120);
    });
    window.addEventListener('resize', syncPanelHeight);

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
</script>
</body>
</html>
