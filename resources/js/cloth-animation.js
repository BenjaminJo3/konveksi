import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import gsap from 'gsap';

// Cloth simulation constants
const CLOTH_W = 30;
const CLOTH_H = 20;
const SEG_SIZE = 0.18;
const GRAVITY = -0.004;
const DAMPING = 0.03;
const STIFFNESS = 1.0;

class Particle {
    constructor(x, y, z) {
        this.pos = new THREE.Vector3(x, y, z);
        this.prev = new THREE.Vector3(x, y, z);
        this.acc = new THREE.Vector3(0, 0, 0);
        this.pinned = false;
    }

    addForce(f) {
        this.acc.add(f);
    }

    integrate(dt) {
        if (this.pinned) return;
        const vel = this.pos.clone().sub(this.prev).multiplyScalar(1 - DAMPING);
        this.prev.copy(this.pos);
        this.pos.add(vel).add(this.acc.multiplyScalar(dt * dt));
        this.acc.set(0, 0, 0);
    }
}

class Constraint {
    constructor(p1, p2) {
        this.p1 = p1;
        this.p2 = p2;
        this.rest = p1.pos.distanceTo(p2.pos);
    }

    satisfy() {
        const delta = this.p2.pos.clone().sub(this.p1.pos);
        const dist = delta.length();
        if (dist === 0) return;
        const diff = (dist - this.rest) / dist * STIFFNESS;
        const offset = delta.multiplyScalar(diff * 0.5);
        if (!this.p1.pinned) this.p1.pos.add(offset);
        if (!this.p2.pinned) this.p2.pos.sub(offset);
    }
}

export function initClothAnimation(containerId = 'animation-canvas') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // --- Renderer ---
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.appendChild(renderer.domElement);

    // --- Scene ---
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x1a1a2e);
    scene.fog = new THREE.Fog(0x1a1a2e, 15, 40);

    // --- Camera ---
    const camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 1, 7);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.target.set(0, 0, 0);

    // --- Lights ---
    const ambient = new THREE.AmbientLight(0xffffff, 0.4);
    scene.add(ambient);

    const sun = new THREE.DirectionalLight(0xfff5e0, 2.5);
    sun.position.set(5, 10, 5);
    sun.castShadow = true;
    sun.shadow.mapSize.set(1024, 1024);
    scene.add(sun);

    const fill = new THREE.PointLight(0x4466ff, 2, 20);
    fill.position.set(-5, 2, 3);
    scene.add(fill);

    const rim = new THREE.PointLight(0xff6644, 1.5, 20);
    rim.position.set(5, -2, -3);
    scene.add(rim);

    // --- Floor ---
    const floorGeo = new THREE.PlaneGeometry(30, 30);
    const floorMat = new THREE.MeshStandardMaterial({ color: 0x0d0d1f, roughness: 0.9 });
    const floor = new THREE.Mesh(floorGeo, floorMat);
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -3;
    floor.receiveShadow = true;
    scene.add(floor);

    // --- Build cloth particles ---
    const particles = [];
    for (let y = 0; y <= CLOTH_H; y++) {
        const row = [];
        for (let x = 0; x <= CLOTH_W; x++) {
            const px = (x - CLOTH_W / 2) * SEG_SIZE;
            const py = (CLOTH_H - y) * SEG_SIZE;
            const p = new Particle(px, py, 0);
            // Pin top row
            if (y === 0) p.pinned = true;
            row.push(p);
        }
        particles.push(row);
    }

    // --- Constraints ---
    const constraints = [];
    for (let y = 0; y <= CLOTH_H; y++) {
        for (let x = 0; x <= CLOTH_W; x++) {
            if (x < CLOTH_W) constraints.push(new Constraint(particles[y][x], particles[y][x + 1]));
            if (y < CLOTH_H) constraints.push(new Constraint(particles[y][x], particles[y + 1][x]));
            // Shear
            if (x < CLOTH_W && y < CLOTH_H) {
                constraints.push(new Constraint(particles[y][x], particles[y + 1][x + 1]));
                constraints.push(new Constraint(particles[y + 1][x], particles[y][x + 1]));
            }
        }
    }

    // --- Cloth geometry ---
    const geo = new THREE.BufferGeometry();
    const verts = new Float32Array((CLOTH_W + 1) * (CLOTH_H + 1) * 3);
    const uvs = new Float32Array((CLOTH_W + 1) * (CLOTH_H + 1) * 2);
    const indices = [];

    for (let y = 0; y <= CLOTH_H; y++) {
        for (let x = 0; x <= CLOTH_W; x++) {
            const i = y * (CLOTH_W + 1) + x;
            uvs[i * 2] = x / CLOTH_W;
            uvs[i * 2 + 1] = 1 - y / CLOTH_H;
        }
    }
    for (let y = 0; y < CLOTH_H; y++) {
        for (let x = 0; x < CLOTH_W; x++) {
            const a = y * (CLOTH_W + 1) + x;
            const b = a + 1;
            const c = a + (CLOTH_W + 1);
            const d = c + 1;
            indices.push(a, b, d, a, d, c);
        }
    }

    geo.setIndex(indices);
    geo.setAttribute('position', new THREE.BufferAttribute(verts, 3));
    geo.setAttribute('uv', new THREE.BufferAttribute(uvs, 2));
    geo.computeVertexNormals();

    // Fabric-like texture using canvas
    const texCanvas = document.createElement('canvas');
    texCanvas.width = 512; texCanvas.height = 512;
    const ctx = texCanvas.getContext('2d');

    // Base color - navy blue batik-like
    ctx.fillStyle = '#1a3a7a';
    ctx.fillRect(0, 0, 512, 512);

    // Woven grid pattern
    ctx.strokeStyle = 'rgba(255,255,255,0.08)';
    ctx.lineWidth = 1;
    for (let i = 0; i < 512; i += 8) {
        ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, 512); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(512, i); ctx.stroke();
    }

    // Batik motif dots
    ctx.fillStyle = 'rgba(255,215,0,0.4)';
    for (let r = 0; r < 8; r++) {
        for (let c = 0; c < 8; c++) {
            ctx.beginPath();
            ctx.arc(32 + c * 64, 32 + r * 64, 6, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.arc(64 + c * 64, 64 + r * 64, 3, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    const texture = new THREE.CanvasTexture(texCanvas);
    texture.wrapS = texture.wrapT = THREE.RepeatWrapping;

    const mat = new THREE.MeshStandardMaterial({
        map: texture,
        side: THREE.DoubleSide,
        roughness: 0.7,
        metalness: 0.05,
    });

    const clothMesh = new THREE.Mesh(geo, mat);
    clothMesh.castShadow = true;
    clothMesh.receiveShadow = true;
    scene.add(clothMesh);

    // --- Wind animation state ---
    let time = 0;
    let windStrength = 0;

    // GSAP: fade in wind
    gsap.to({ v: 0 }, {
        v: 1,
        duration: 2,
        ease: 'power2.inOut',
        onUpdate: function () { windStrength = this.targets()[0].v; },
    });

    // Animate fill light color cycling
    gsap.to(fill.position, { x: 5, duration: 4, yoyo: true, repeat: -1, ease: 'sine.inOut' });
    gsap.to(rim.position, { z: 3, duration: 6, yoyo: true, repeat: -1, ease: 'sine.inOut' });

    // --- Gravity vector ---
    const gravity = new THREE.Vector3(0, GRAVITY, 0);

    function updateCloth() {
        time += 0.016;

        // Wind: sinusoidal gusts
        const windX = Math.sin(time * 1.3) * 0.003 * windStrength;
        const windZ = Math.cos(time * 0.8) * 0.004 * windStrength + 0.002 * windStrength;
        const wind = new THREE.Vector3(windX, 0, windZ);

        // Apply forces
        for (let y = 0; y <= CLOTH_H; y++) {
            for (let x = 0; x <= CLOTH_W; x++) {
                const p = particles[y][x];
                p.addForce(gravity);
                p.addForce(wind);
                // Small turbulence
                p.addForce(new THREE.Vector3(
                    (Math.random() - 0.5) * 0.0003,
                    0,
                    (Math.random() - 0.5) * 0.0003
                ));
            }
        }

        // Integrate
        for (let y = 0; y <= CLOTH_H; y++)
            for (let x = 0; x <= CLOTH_W; x++)
                particles[y][x].integrate(1);

        // Satisfy constraints (iterations)
        for (let iter = 0; iter < 10; iter++)
            for (const c of constraints) c.satisfy();

        // Update geometry
        const pos = geo.attributes.position.array;
        for (let y = 0; y <= CLOTH_H; y++) {
            for (let x = 0; x <= CLOTH_W; x++) {
                const i = (y * (CLOTH_W + 1) + x) * 3;
                const p = particles[y][x];
                pos[i] = p.pos.x;
                pos[i + 1] = p.pos.y;
                pos[i + 2] = p.pos.z;
            }
        }
        geo.attributes.position.needsUpdate = true;
        geo.computeVertexNormals();
    }

    // --- Resize ---
    function onResize() {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }
    window.addEventListener('resize', onResize);

    // --- Loop ---
    function animate() {
        requestAnimationFrame(animate);
        updateCloth();
        controls.update();
        renderer.render(scene, camera);
    }
    animate();

    return { scene, renderer };
}
