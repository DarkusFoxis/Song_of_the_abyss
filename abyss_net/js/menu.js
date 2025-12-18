const toggle = document.getElementById('toggleNav');
const panel = document.getElementById('navPanel');
toggle.addEventListener('click', () => {
    panel.style.display = panel.style.display === 'flex' ? 'none' : 'flex';
});