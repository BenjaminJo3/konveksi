import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import gsap from 'gsap';

export function initAnimation3D(containerId = 'animation-canvas') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Scene
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0a1a);

    // Camera
    const camera = new THREE.PerspectiveCamera(
        75,
        container.clientWidth / container.clientHeight,
        0.1,
        1000
    );
    camera.position.set(0, 2, 8);

    // Renderer
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.shadowMap.enabled = true;
    container.appendChild(renderer.domElement);

    // Controls
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;

    // Lights
    const ambientLight = new THREE.AmbientLight(0x404040, 1);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 2);
    directionalLight.position.set(5, 10, 5);
    directionalLight.castShadow = true;
    scene.add(directionalLight);

    const pointLight = new THREE.PointLight(0x4488ff, 3, 20);
    pointLight.position.set(-3, 3, 3);
    scene.add(pointLight);

    // Main object - rotating fabric/cloth-like shape
    const geometry = new THREE.TorusKnotGeometry(1.5, 0.4, 128, 32);
    const material = new THREE.MeshStandardMaterial({
        color: 0x2255cc,
        metalness: 0.3,
        roughness: 0.4,
        wireframe: false,
    });
    const mesh = new THREE.Mesh(geometry, material);
    mesh.castShadow = true;
    scene.add(mesh);

    // Particle system
    const particleCount = 500;
    const positions = new Float32Array(particleCount * 3);
    for (let i = 0; i < particleCount * 3; i++) {
        positions[i] = (Math.random() - 0.5) * 20;
    }
    const particleGeom = new THREE.BufferGeometry();
    particleGeom.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const particleMat = new THREE.PointsMaterial({ color: 0x88aaff, size: 0.05 });
    const particles = new THREE.Points(particleGeom, particleMat);
    scene.add(particles);

    // GSAP intro animation
    gsap.from(mesh.scale, {
        x: 0, y: 0, z: 0,
        duration: 1.5,
        ease: 'elastic.out(1, 0.5)',
    });
    gsap.to(mesh.rotation, {
        y: Math.PI * 2,
        duration: 8,
        ease: 'none',
        repeat: -1,
    });
    gsap.to(pointLight.position, {
        x: 3, y: -3,
        duration: 3,
        ease: 'sine.inOut',
        yoyo: true,
        repeat: -1,
    });

    // Resize handler
    function onResize() {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }
    window.addEventListener('resize', onResize);

    // Animation loop
    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        particles.rotation.y += 0.0005;
        renderer.render(scene, camera);
    }
    animate();

    return { scene, camera, renderer, mesh };
}
