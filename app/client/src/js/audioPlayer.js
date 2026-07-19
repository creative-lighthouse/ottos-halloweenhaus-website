function formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Wird von initYoutubeAudioPlayers gesetzt damit reguläre Player das Modal schließen können
let _closeActiveYtModal = null;

const VOLUME_STORAGE_KEY = 'audioplayer-volume';

function getStoredVolume() {
    const stored = parseFloat(localStorage.getItem(VOLUME_STORAGE_KEY));
    return isNaN(stored) ? 1 : Math.min(1, Math.max(0, stored));
}

export function initAudioPlayers() {
    document.querySelectorAll('.audioplayer:not(.yt-audioplayer)').forEach(player => {
        const soundfileUrl = player.dataset.soundfile;
        if (!soundfileUrl) return;

        const button = player.querySelector('.audioplayer-button');
        const rewindBtn = player.querySelector('.audioplayer-rewind');
        const positionDisplay = player.querySelector('.audioplayer-position');
        const volumeSlider = player.querySelector('.audioplayer-volume-slider');

        let audio = null;
        let currentVolume = getStoredVolume();
        volumeSlider.value = currentVolume;

        rewindBtn.classList.add('audioplayer-rewind--inactive');

        // Ermöglicht anderen Playern, diesen von außen zu pausieren
        player._pauseAudio = () => {
            if (audio && !audio.paused) {
                audio.pause();
                setPlaying(false);
            }
        };

        function updatePosition() {
            const current = formatTime(audio.currentTime);
            const total = isNaN(audio.duration) ? '0:00' : formatTime(audio.duration);
            positionDisplay.textContent = `${current} / ${total}`;
        }

        function setPlaying(playing) {
            button.classList.toggle('audioplayer-button--playing', playing);
            rewindBtn.classList.toggle('audioplayer-rewind--inactive', !playing);
            player.classList.toggle('audioplayer--playing', playing);
        }

        function createAudio() {
            const a = new Audio();
            a.preload = 'metadata';
            a.volume = currentVolume;
            a.src = soundfileUrl;
            a.addEventListener('timeupdate', updatePosition);
            a.addEventListener('loadedmetadata', updatePosition);
            a.addEventListener('ended', () => {
                setPlaying(false);
                updatePosition();
            });
            return a;
        }

        // Auf Detailseiten wird die Spielzeit direkt beim Laden angezeigt,
        // auf Übersichtsseiten erst beim Abspielen (viele Player gleichzeitig).
        if (player.dataset.preload === 'true') {
            audio = createAudio();
        }

        button.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();

            if (!audio) {
                audio = createAudio();
            }

            if (audio.paused) {
                _closeActiveYtModal?.();
                document.querySelectorAll('.audioplayer').forEach(other => {
                    if (other !== player) other._pauseAudio?.();
                });
                audio.play().catch(() => setPlaying(false));
                setPlaying(true);
            } else {
                audio.pause();
                setPlaying(false);
            }
        });

        rewindBtn.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            if (!audio || rewindBtn.classList.contains('audioplayer-rewind--inactive')) return;
            audio.currentTime = 0;
            updatePosition();
        });

        volumeSlider.addEventListener('click', e => e.stopPropagation());
        volumeSlider.addEventListener('input', e => {
            e.stopPropagation();
            currentVolume = parseFloat(e.target.value);
            localStorage.setItem(VOLUME_STORAGE_KEY, currentVolume);
            if (audio) audio.volume = currentVolume;
        });
    });
}

// --- YouTube Audio Player ---

function extractYoutubeId(url) {
    if (!url) return null;
    const patterns = [
        /[?&]v=([a-zA-Z0-9_-]{11})/,
        /youtu\.be\/([a-zA-Z0-9_-]{11})/,
        /embed\/([a-zA-Z0-9_-]{11})/,
    ];
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match) return match[1];
    }
    if (/^[a-zA-Z0-9_-]{11}$/.test(url.trim())) return url.trim();
    return null;
}

let ytModal = null;
let activeYtPlayer = null;

function getOrCreateModal() {
    if (ytModal) return ytModal;

    ytModal = document.createElement('div');
    ytModal.className = 'yt-audio-modal';
    ytModal.innerHTML = `
        <div class="yt-audio-modal__backdrop">
            <div class="yt-audio-modal__box">
                <button class="yt-audio-modal__close" aria-label="Schließen">&#x2715;</button>
                <div class="yt-audio-modal__content"></div>
            </div>
        </div>
    `;

    ytModal.querySelector('.yt-audio-modal__close').addEventListener('click', closeYtModal);
    ytModal.querySelector('.yt-audio-modal__backdrop').addEventListener('click', e => {
        if (e.target === ytModal.querySelector('.yt-audio-modal__backdrop')) closeYtModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeYtModal();
    });

    document.body.appendChild(ytModal);
    return ytModal;
}

function openYtModal(player, videoId) {
    const modal = getOrCreateModal();

    // Alle regulären Player pausieren
    document.querySelectorAll('.audioplayer').forEach(p => p._pauseAudio?.());

    activeYtPlayer = player;

    const content = modal.querySelector('.yt-audio-modal__content');
    content.innerHTML = '';

    const consent = document.createElement('div');
    consent.className = 'yt-consent';
    consent.dataset.videoid = videoId;
    consent.style.backgroundImage = `url('https://img.youtube.com/vi/${videoId}/hqdefault.jpg')`;
    consent.innerHTML = `
        <div class="yt-consent__inner">
            <div class="yt-consent__play">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
            </div>
            <p class="yt-consent__notice">
                Dieses Video wird von YouTube bereitgestellt.<br>
                Beim Abspielen werden Daten an YouTube übertragen und Cookies von Google gesetzt.<br>
                <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Mehr Informationen</a>
            </p>
        </div>
    `;

    consent.addEventListener('click', () => activateYtInModal(consent, videoId), { once: true });
    content.appendChild(consent);

    modal.classList.add('yt-audio-modal--visible');
    _closeActiveYtModal = closeYtModal;
}

function activateYtInModal(wrapper, videoId) {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1`;
    iframe.title = 'YouTube video player';
    iframe.setAttribute('frameborder', '0');
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
    iframe.setAttribute('allowfullscreen', '');

    wrapper.innerHTML = '';
    wrapper.classList.add('yt-consent--active');
    wrapper.appendChild(iframe);
}

function closeYtModal() {
    if (!ytModal || !activeYtPlayer) return;

    ytModal.classList.remove('yt-audio-modal--visible');
    ytModal.querySelector('.yt-audio-modal__content').innerHTML = '';

    activeYtPlayer.querySelector('.audioplayer-button').classList.remove('audioplayer-button--playing');
    activeYtPlayer = null;
    _closeActiveYtModal = null;
}

export function initYoutubeAudioPlayers() {
    document.querySelectorAll('.yt-audioplayer').forEach(player => {
        const videoId = extractYoutubeId(player.dataset.videolink);
        if (!videoId) return;

        const button = player.querySelector('.audioplayer-button');

        button.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();

            if (activeYtPlayer === player) {
                closeYtModal();
                return;
            }

            if (activeYtPlayer) closeYtModal();

            openYtModal(player, videoId);
            button.classList.add('audioplayer-button--playing');
        });
    });
}
