<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Animation - Konveksi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { margin: 0; background: #0a0a1a; overflow: hidden; }
        #animation-canvas {
            width: 100vw;
            height: 100vh;
            display: block;
        }
        .overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-family: sans-serif;
        }
        .overlay h1 { font-size: 1.5rem; margin: 0 0 4px; }
        .overlay p { font-size: 0.85rem; opacity: 0.6; margin: 0; }
    </style>
</head>
<body>
    <div id="animation-canvas"></div>
    <div class="overlay">
        <h1>Konveksi 3D</h1>
        <p>Drag untuk memutar &bull; Scroll untuk zoom</p>
    </div>

    <script type="module">
        import { initAnimation3D } from '/resources/js/animation3d.js';
        initAnimation3D('animation-canvas');
    </script>
</body>
</html>
