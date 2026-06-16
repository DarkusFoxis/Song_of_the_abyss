(function () {
const STORAGE_VOLUME = "dfplayer_volume";
const STORAGE_MUTED = "dfplayer_muted";
let activePlayer = null;
let hoveredPlayer = null;

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function formatTime(seconds) {
    if (!isFinite(seconds)) return "0:00";
    seconds = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hours > 0) return `${hours}:${String(minutes).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
    return `${minutes}:${String(secs).padStart(2, "0")}`;
}

function safeStorageGet(key) {
    try { return window.localStorage ? window.localStorage.getItem(key) : null; } catch(e) { return null; }
}
function safeStorageSet(key, value) {
    try { if (window.localStorage) window.localStorage.setItem(key, value); } catch(e) {}
}
function getStoredVolume() { return clamp(parseFloat(safeStorageGet(STORAGE_VOLUME) || "1"), 0, 1); }
function getStoredMuted() { return safeStorageGet(STORAGE_MUTED) === "1"; }

function icon(path) {
    return `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${path}"></path></svg>`;
}

function makeButton(label, path, className) {
    const button = document.createElement("button");
    button.type = "button";
    button.className = className;
    button.setAttribute("aria-label", label);
    button.title = label;
    button.innerHTML = icon(path);
    return button;
}

function isEditable(target) {
    if (!(target instanceof HTMLElement)) return false;
    const tagName = target.tagName;
    return tagName === "INPUT" || tagName === "TEXTAREA" || tagName === "SELECT" || target.isContentEditable;
}

function setActivePlayer(player) { activePlayer = player; }
function getTargetPlayer() {
    if (document.fullscreenElement && document.fullscreenElement.classList?.contains("df-player")) return document.fullscreenElement;
    return activePlayer || hoveredPlayer;
}

function pauseOtherPlayers(currentVideo) {
    document.querySelectorAll(".df-player video").forEach(video => {
        if (video !== currentVideo && !video.paused) video.pause();
    });
}

function syncVolumeEverywhere(volume, muted) {
    safeStorageSet(STORAGE_VOLUME, String(volume));
    safeStorageSet(STORAGE_MUTED, muted ? "1" : "0");
    document.querySelectorAll(".df-player").forEach(player => {
        const state = player.__dfState;
        if (!state) return;
        state.video.volume = volume;
        state.video.muted = muted;
        state.volumeRange.value = String(volume);
        const muteIcon = muted || volume === 0
            ? "M4 9h4l5-4v14l-5-4H4z M16 9l4 6 M20 9l-4 6"
            : "M4 9h4l5-4v14l-5-4H4z M16 9c1.4 1.2 2 2.6 2 4s-.6 2.8-2 4 M18.5 6.5C21 8.4 22 10.9 22 13s-1 4.6-3.5 6.5";
        state.muteButton.innerHTML = icon(muteIcon);
    });
}

function showToast(state, message) {
    state.toast.textContent = message;
    state.toast.classList.add("is-visible");
    clearTimeout(state.toastTimer);
    state.toastTimer = window.setTimeout(() => state.toast.classList.remove("is-visible"), 1800);
}

function createPlayer(video) {
    if (video.dataset.dfInit === "1" || video.closest(".df-player")) return;

    video.dataset.dfInit = "1";
    video.removeAttribute("controls");
    video.setAttribute("playsinline", "");
    video.preload = video.getAttribute("preload") || "metadata";

    const wrapper = document.createElement("div");
    wrapper.className = "df-player";
    wrapper.tabIndex = 0;
    if (video.dataset.dfCompact === "1") wrapper.classList.add("df-player-compact");

    const parent = video.parentNode;
    if (!parent) return;
    parent.insertBefore(wrapper, video);
    wrapper.appendChild(video);

    const overlay = document.createElement("div");
    overlay.className = "df-overlay";
    const overlayButton = makeButton("Воспроизвести", "M8 5v14l11-7z", "df-overlay-btn");
    overlay.appendChild(overlayButton);

    const controls = document.createElement("div");
    controls.className = "df-controls";

    const progress = document.createElement("div");
    progress.className = "df-progress";
    progress.setAttribute("role", "slider");
    progress.setAttribute("aria-label", "Позиция воспроизведения");
    progress.tabIndex = 0;
    const bufferedBar = document.createElement("div");
    bufferedBar.className = "df-buffered";
    const playedBar = document.createElement("div");
    playedBar.className = "df-played";
    progress.append(bufferedBar, playedBar);

    const row = document.createElement("div");
    row.className = "df-row";
    const left = document.createElement("div");
    left.className = "df-cluster";
    const right = document.createElement("div");
    right.className = "df-cluster";

    const playButton = makeButton("Воспроизвести", "M8 5v14l11-7z", "df-btn");
    const muteButton = makeButton("Отключить звук", "M4 9h4l5-4v14l-5-4H4z M16 9c1.4 1.2 2 2.6 2 4s-.6 2.8-2 4 M18.5 6.5C21 8.4 22 10.9 22 13s-1 4.6-3.5 6.5", "df-btn");
    const fullscreenButton = makeButton("Полный экран", "M4 4h6v2H6v4H4zm10 0h6v6h-2V6h-4zM4 14h2v4h4v2H4zm14 0h2v6h-6v-2h4z", "df-btn");

    const extraButton = makeButton("Настройки", "M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8zm0-14a6 6 0 1 0 6 6 6 6 0 0 0-6-6zm0 10a4 4 0 1 1 4-4 4 4 0 0 1-4 4z", "df-extra-button");

    const time = document.createElement("div");
    time.className = "df-time";
    const current = document.createElement("span");
    current.textContent = "0:00";
    const separator = document.createTextNode(" / ");
    const total = document.createElement("span");
    total.textContent = "0:00";
    time.append(current, separator, total);

    const volumeWrap = document.createElement("div");
    volumeWrap.className = "df-volume";
    const volumeRange = document.createElement("input");
    volumeRange.type = "range";
    volumeRange.min = "0";
    volumeRange.max = "1";
    volumeRange.step = "0.01";
    volumeRange.value = String(getStoredVolume());
    volumeWrap.append(muteButton, volumeRange);

    left.append(playButton, time);
    right.append(volumeWrap, extraButton, fullscreenButton);
    row.append(left, right);
    controls.append(progress, row);

    const toast = document.createElement("div");
    toast.className = "df-toast";
    wrapper.append(overlay, controls, toast);

    const extraMenu = document.createElement("div");
    extraMenu.className = "df-extra-menu";

    const speedSection = document.createElement("div");
    speedSection.className = "df-menu-section";
    const speedTitle = document.createElement("div");
    speedTitle.className = "df-menu-title";
    speedTitle.textContent = "Скорость";
    const speedOptions = document.createElement("div");
    speedOptions.className = "df-speed-options";
    const speeds = [0.5, 0.75, 1, 1.25, 1.5, 2];
    const speedBtns = [];
    speeds.forEach(sp => {
        const btn = document.createElement("button");
        btn.className = "df-speed-btn";
        if (sp === 1) btn.classList.add("active-speed");
        btn.textContent = `${sp}x`;
        btn.dataset.speed = sp;
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            video.playbackRate = sp;
            speedBtns.forEach(b => b.classList.remove("active-speed"));
            btn.classList.add("active-speed");
            showToast(state, `Скорость ${sp}x`);
            hideMenu();
        });
        speedOptions.appendChild(btn);
        speedBtns.push(btn);
    });
    speedSection.append(speedTitle, speedOptions);

    const screenshotSection = document.createElement("div");
    screenshotSection.className = "df-menu-section";
    const screenshotMenuItem = document.createElement("button");
    screenshotMenuItem.className = "df-menu-screenshot";
    screenshotMenuItem.innerHTML = `<svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none"><path d="M4 7h4l2-2h4l2 2h4v10H4z M12 10a3 3 0 1 0 0 6a3 3 0 0 0 0-6z"/></svg> <span>Снимок кадра</span>`;
    screenshotMenuItem.addEventListener("click", async (e) => {
        e.stopPropagation();
        await takeScreenshotInternal(video, state);
        hideMenu();
    });
    screenshotSection.appendChild(screenshotMenuItem);

    extraMenu.append(speedSection, screenshotSection);
    wrapper.appendChild(extraMenu);

    async function takeScreenshotInternal(videoEl, st) {
        try {
            const canvas = document.createElement("canvas");
            canvas.width = videoEl.videoWidth;
            canvas.height = videoEl.videoHeight;
            const ctx = canvas.getContext("2d");
            if (!ctx || canvas.width === 0 || canvas.height === 0) {
                showToast(st, "Кадр пока недоступен");
                return;
            }
            ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(async (blob) => {
                if (!blob) { showToast(st, "Не удалось сохранить кадр"); return; }
                if (navigator.clipboard && window.ClipboardItem) {
                    try {
                        await navigator.clipboard.write([new ClipboardItem({ [blob.type]: blob })]);
                        showToast(st, "Кадр скопирован");
                        return;
                    } catch(e) {}
                }
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.download = `frame_${Date.now()}.png`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
                showToast(st, "Кадр скачан");
            }, "image/png");
        } catch(e) { showToast(st, "Снимок недоступен"); }
    }

    let isMenuOpen = false;
    let outsideClickListener = null;
    let repositionHandler = null;

    function positionMenu() {
        const btnRect = extraButton.getBoundingClientRect();
        const wrapperRect = wrapper.getBoundingClientRect();
        let top = btnRect.bottom - wrapperRect.top + 8;
        let left = btnRect.right - wrapperRect.left - extraMenu.offsetWidth;
        if (top + extraMenu.offsetHeight > wrapperRect.height) {
            top = btnRect.top - wrapperRect.top - extraMenu.offsetHeight - 8;
        }
        if (left < 8) left = 8;
        if (left + extraMenu.offsetWidth > wrapperRect.width - 8) left = wrapperRect.width - extraMenu.offsetWidth - 8;
        extraMenu.style.top = `${top}px`;
        extraMenu.style.left = `${left}px`;
    }

    function hideMenu() {
        if (!isMenuOpen) return;
        isMenuOpen = false;
        extraMenu.classList.remove("show");
        if (outsideClickListener) {
            document.removeEventListener("click", outsideClickListener);
            outsideClickListener = null;
        }
        if (repositionHandler) {
            window.removeEventListener("resize", repositionHandler);
            window.removeEventListener("scroll", repositionHandler, true);
            repositionHandler = null;
        }
        if (!video.paused && !state.isScrubbing) scheduleHide();
    }

    function showMenu() {
        if (isMenuOpen) {
            hideMenu();
            return;
        }
        isMenuOpen = true;
        positionMenu();
        extraMenu.classList.add("show");
        outsideClickListener = (e) => {
            if (!extraMenu.contains(e.target) && e.target !== extraButton && !extraButton.contains(e.target)) {
                hideMenu();
            }
        };
        document.addEventListener("click", outsideClickListener);
        repositionHandler = () => {
            if (isMenuOpen) positionMenu();
        };
        window.addEventListener("resize", repositionHandler);
        window.addEventListener("scroll", repositionHandler, true);
        clearTimeout(state.hideTimer);
        controls.classList.remove("is-hidden");
    }

    extraButton.addEventListener("click", (e) => {
        e.stopPropagation();
        setActivePlayer(wrapper);
        showMenu();
    });

    const state = {
        wrapper, video, overlayButton, controls, playButton, muteButton, volumeRange,
        progress, bufferedBar, playedBar, current, total, toast, toastTimer: 0,
        hideTimer: 0, isScrubbing: false, extraButton, extraMenu, handleShortcut: null
    };
    wrapper.__dfState = state;

    function syncButtons() {
        const isPaused = video.paused;
        const playPath = isPaused ? "M8 5v14l11-7z" : "M6 4h4v16H6zm8 0h4v16h-4z";
        playButton.innerHTML = icon(playPath);
        overlayButton.classList.toggle("is-hidden", !isPaused);
        wrapper.classList.toggle("df-is-playing", !isPaused);
    }

    function syncTimeline() {
        const duration = video.duration;
        current.textContent = formatTime(video.currentTime);
        total.textContent = formatTime(duration);
        if (isFinite(duration) && duration > 0) playedBar.style.width = `${(video.currentTime / duration) * 100}%`;
        else playedBar.style.width = "0%";
        if (video.buffered && video.buffered.length > 0 && isFinite(duration) && duration > 0) {
            const bufferedEnd = video.buffered.end(video.buffered.length - 1);
            bufferedBar.style.width = `${(bufferedEnd / duration) * 100}%`;
        } else bufferedBar.style.width = "0%";
    }

    function scheduleHide() {
        clearTimeout(state.hideTimer);
        controls.classList.remove("is-hidden");
        if (!video.paused && !state.isScrubbing && !isMenuOpen && !wrapper.matches(":focus-within")) {
            state.hideTimer = window.setTimeout(() => {
                controls.classList.add("is-hidden");
            }, 5000);
        }
    }

    function togglePlay() {
        if (video.paused) { video.play().catch(()=>{}); } else { video.pause(); }
    }

    function seekTo(clientX) {
        const duration = video.duration;
        if (!isFinite(duration) || duration <= 0) return;
        const rect = progress.getBoundingClientRect();
        const ratio = clamp((clientX - rect.left) / rect.width, 0, 1);
        video.currentTime = duration * ratio;
        syncTimeline();
    }

    function adjustVolume(delta) {
        const currentValue = video.muted ? 0 : video.volume;
        const nextVolume = clamp(currentValue + delta, 0, 1);
        syncVolumeEverywhere(nextVolume, nextVolume === 0);
        showToast(state, `Громкость ${Math.round(nextVolume * 100)}%`);
    }

    function toggleMute() {
        const willMute = !(video.muted || video.volume === 0);
        syncVolumeEverywhere(video.volume, willMute);
        showToast(state, willMute ? "Звук выключен" : "Звук включен");
    }

    function toggleFullscreen() {
        if (document.fullscreenElement === wrapper) document.exitFullscreen?.();
        else wrapper.requestFullscreen?.().catch(()=>{});
    }

    function handleShortcut(event) {
        if (isEditable(event.target)) return false;
        const key = event.key.toLowerCase();
        if (key === " " || key === "k") { event.preventDefault(); togglePlay(); return true; }
        if (key === "arrowright" || key === "l") { event.preventDefault(); video.currentTime = Math.min(video.duration || 0, video.currentTime + (key === "l" ? 10 : 5)); showToast(state, `Позиция ${formatTime(video.currentTime)}`); return true; }
        if (key === "arrowleft" || key === "j") { event.preventDefault(); video.currentTime = Math.max(0, video.currentTime - (key === "j" ? 10 : 5)); showToast(state, `Позиция ${formatTime(video.currentTime)}`); return true; }
        if (key === "arrowup") { event.preventDefault(); adjustVolume(0.05); return true; }
        if (key === "arrowdown") { event.preventDefault(); adjustVolume(-0.05); return true; }
        if (key === "m") { event.preventDefault(); toggleMute(); return true; }
        if (key === "f") { event.preventDefault(); toggleFullscreen(); return true; }
        if (/^[0-9]$/.test(key) && isFinite(video.duration) && video.duration > 0) { event.preventDefault(); video.currentTime = (video.duration * Number(key)) / 10; showToast(state, `Позиция ${key}0%`); return true; }
        return false;
    }
    state.handleShortcut = handleShortcut;

    overlayButton.addEventListener("click", togglePlay);
    playButton.addEventListener("click", togglePlay);
    muteButton.addEventListener("click", toggleMute);
    fullscreenButton.addEventListener("click", toggleFullscreen);
    volumeRange.addEventListener("input", () => {
        const value = parseFloat(volumeRange.value);
        syncVolumeEverywhere(value, value === 0);
    });
    video.addEventListener("click", (e) => { setActivePlayer(wrapper); if (controls.classList.contains("is-hidden")) scheduleHide(); else togglePlay(); });
    video.addEventListener("dblclick", () => { toggleFullscreen(); });
    video.addEventListener("play", () => { pauseOtherPlayers(video); syncButtons(); scheduleHide(); });
    video.addEventListener("pause", () => { syncButtons(); controls.classList.remove("is-hidden"); clearTimeout(state.hideTimer); });
    video.addEventListener("timeupdate", syncTimeline);
    video.addEventListener("progress", syncTimeline);
    video.addEventListener("loadedmetadata", syncTimeline);
    video.addEventListener("ended", syncButtons);
    video.addEventListener("error", () => showToast(state, "Ошибка воспроизведения"));
    progress.addEventListener("click", (e) => { setActivePlayer(wrapper); seekTo(e.clientX); });
    progress.addEventListener("pointerdown", (e) => {
        e.preventDefault(); state.isScrubbing = true; seekTo(e.clientX); scheduleHide();
        const move = (me) => seekTo(me.clientX);
        const up = () => { state.isScrubbing = false; document.removeEventListener("pointermove", move); document.removeEventListener("pointerup", up); scheduleHide(); };
        document.addEventListener("pointermove", move); document.addEventListener("pointerup", up);
    });
    wrapper.addEventListener("mouseenter", () => { hoveredPlayer = wrapper; setActivePlayer(wrapper); scheduleHide(); });
    wrapper.addEventListener("mouseleave", () => { if (hoveredPlayer === wrapper) hoveredPlayer = null; clearTimeout(state.hideTimer); if (!video.paused && !isMenuOpen) controls.classList.add("is-hidden"); });
    wrapper.addEventListener("mousemove", scheduleHide);
    wrapper.addEventListener("focusin", () => { setActivePlayer(wrapper); controls.classList.remove("is-hidden"); });
    wrapper.addEventListener("keydown", (e) => { if (handleShortcut(e)) scheduleHide(); });
    wrapper.addEventListener("touchstart", (e) => { setActivePlayer(wrapper); if (controls.classList.contains("is-hidden")) { scheduleHide(); e.preventDefault(); } });
    controls.addEventListener("touchstart", () => scheduleHide());
    video.addEventListener("touchstart", () => scheduleHide());

    document.addEventListener("fullscreenchange", () => {
        wrapper.classList.toggle("df-is-fullscreen", document.fullscreenElement === wrapper);
        scheduleHide();
        if (isMenuOpen) positionMenu();
    });
    
    syncVolumeEverywhere(getStoredVolume(), getStoredMuted());
    syncButtons(); syncTimeline();
}

function findEnhanceableVideos(container) {
    return Array.from((container || document).querySelectorAll("video")).filter(v => !v.dataset.dfInit && !v.closest(".df-player") && !v.hasAttribute("data-df-native"));
}
function init(container) { findEnhanceableVideos(container).forEach(createPlayer); }
window.DFPlayerInit = init;
document.addEventListener("keydown", (event) => {
    const player = getTargetPlayer();
    if (!player) return;
    const st = player.__dfState;
    if (!st || isEditable(event.target)) return;
    if (player.contains(event.target) && document.fullscreenElement !== player) return;
    if (st.handleShortcut && st.handleShortcut(event)) st.controls.classList.remove("is-hidden");
});
if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => init(document));
else init(document);
})();