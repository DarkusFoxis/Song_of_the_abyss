const canvas = document.getElementById("vnh");
const ctx = canvas.getContext("2d");

const CONFIG = {
    particleCount: 275,
    maxDistance: 150,
    maxRadius: 6,
    minRadius: 1,
    speed: 0.5
};

let particles = [];

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    initParticles(); 
}

function initParticles() {
    particles = [];
    for (let i = 0; i < CONFIG.particleCount; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            r: Math.random() * (CONFIG.maxRadius - CONFIG.minRadius) + CONFIG.minRadius,
            dx: (Math.random() - 0.5) * CONFIG.speed * 2,
            dy: (Math.random() - 0.5) * CONFIG.speed * 2,
            color: `rgba(${Math.floor(Math.random() * 255)}, 
                      ${Math.floor(Math.random() * 255)}, 
                      ${Math.floor(Math.random() * 255)}, 
                      0.8)`
        });
    }
}

function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach((p, i) => {
        p.x += p.dx;
        p.y += p.dy;

        if (p.x < 0 || p.x > canvas.width) p.dx = -p.dx;
        if (p.y < 0 || p.y > canvas.height) p.dy = -p.dy;

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();

        for (let j = i + 1; j < particles.length; j++) {
            const p2 = particles[j];
            const distSq = (p.x - p2.x) ** 2 + (p.y - p2.y) ** 2;
            const maxDistSq = CONFIG.maxDistance ** 2;

            if (distSq < maxDistSq) {
                const opacity = 1 - (Math.sqrt(distSq) / CONFIG.maxDistance);
                
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.strokeStyle = `rgba(255, 255, 255, ${opacity * 0.5})`; 
                ctx.lineWidth = 0.5;
                ctx.stroke();
            }
        }
    });

    requestAnimationFrame(animate);
}

window.addEventListener("resize", resizeCanvas);

resizeCanvas();
animate();