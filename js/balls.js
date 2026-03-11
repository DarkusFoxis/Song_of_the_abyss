const c = document.getElementById("vnh");
const p = c.getContext("2d");
c.width = window.innerWidth;
c.height = window.innerHeight;
let random = Math.floor(Math.random() * (400 - 50 + 1)) + 50; 
let m = [];
for (let i = 0; i < random; i++) {
    m.push({
        x: Math.random() * c.width,
        y: Math.random() * c.height,
        r: Math.random() * 10 + 5,
        dx: (Math.random() - 0.5) * 2,
        dy: (Math.random() - 0.5) * 2,
        color: `rgba(${Math.floor(Math.random() * 255)}, 
                      ${Math.floor(Math.random() * 255)}, 
                      ${Math.floor(Math.random() * 255)}, 
                      ${Math.random() + 0.4})`,
    });
}
function d() {
    p.clearRect(0, 0, c.width, c.height);
    m.forEach((b, i) => {
        p.beginPath();
        p.arc(b.x, b.y, b.r, 0, Math.PI * 2);
        p.fillStyle = b.color;
        p.fill();
        b.x += b.dx;
        b.y += b.dy;
        if (b.x < 0 || b.x > c.width) b.dx = -b.dx;
        if (b.y < 0 || b.y > c.height) b.dy = -b.dy;
        for (let j = i + 1; j < m.length; j++) {
            let o = m[j];
            let dist = Math.sqrt((b.x - o.x) ** 2 + (b.y - o.y) ** 2);
            if (dist < 100) {
                p.beginPath();
                p.moveTo(b.x, b.y);
                p.lineTo(o.x, o.y);
                p.strokeStyle = b.color;
                p.lineWidth = 0.65;
                p.stroke();
            }
        }
    });
    requestAnimationFrame(d);
}
window.addEventListener("resize", () => {
    c.width = window.innerWidth;
    c.height = window.innerHeight;
});
d();