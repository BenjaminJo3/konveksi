<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animasi Kain 3D - Konveksi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #1a1a2e; overflow: hidden; font-family: 'Segoe UI', sans-serif; }

        #animation-canvas {
            width: 100vw;
            height: 100vh;
            display: block;
        }

        .ui-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .brand {
            position: absolute;
            top: 28px; left: 32px;
            color: white;
        }
        .brand h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-shadow: 0 2px 12px rgba(0,0,0,0.6);
        }
        .brand p {
            font-size: 0.78rem;
            opacity: 0.5;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .hint {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.35);
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-align: center;
        }

        .badge {
            position: absolute;
            top: 28px; right: 32px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 6px 16px;
            color: rgba(255,255,255,0.6);
            font-size: 0.72rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }
    </style>
</head>
<body>
    <div id="animation-canvas"></div>

    <div class="ui-overlay">
        <div class="brand">
            <h1>KONVEKSI</h1>
            <p>Simulasi Kain 3D</p>
        </div>
        <div class="badge">Three.js &bull; Real-time Physics</div>
        <div class="hint">Drag untuk memutar &nbsp;&bull;&nbsp; Scroll untuk zoom</div>
    </div>

    <script type="module">
        import { initClothAnimation } from '{{ asset('') }}../resources/js/cloth-animation.js';
        initClothAnimation('animation-canvas');
    </script>
</body>
</html>
