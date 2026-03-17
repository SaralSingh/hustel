<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hustel — Admin Login</title>
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
            --red:       #ef4444;
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
            overflow-x: hidden;
            overflow-y: auto;
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

        .page {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1000px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 60px;
        }

        @media (max-width: 768px) {
            .page {
                flex-direction: column;
                justify-content: center;
            }
        }

        /* ── Logo ── */
        .logo {
            text-align: left;
            flex: 1;
        }

        @media (max-width: 768px) {
            .logo {
                text-align: center;
                margin-bottom: 32px;
            }
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

        /* ── Card ── */
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 36px 40px;
            backdrop-filter: blur(20px);
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
        }

        /* ── Form ── */
        .field { margin-bottom: 20px; }
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

        .error-message {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            margin-top: 10px;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 28px rgba(124,58,237,0.5);
        }
        .btn-primary:active { transform: translateY(0); }
    </style>
</head>
<body>
<div class="page">
    <div class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24"><path d="M4 8L12 3L20 8V16L12 21L4 16V8Z"/><path d="M12 3V21M4 8L20 16M20 8L4 16" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
        <h1>Hustel</h1>
    </div>

    <div class="card">
        <div class="card-title">Admin Login</div>

        @if($errors->any())
            <div class="error-message">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Enter your username" required autofocus />
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required />
            </div>

            <button type="submit" class="btn btn-primary">
                Login to Dashboard
            </button>
        </form>
    </div>
</div>
</body>
</html>
