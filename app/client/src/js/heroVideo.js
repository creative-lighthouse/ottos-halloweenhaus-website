/**
 * Hero-Video: spielt eine HLS-Playlist (z.B. von Bunny) als Loop ab.
 * Abgespielt wird über hls.js (nachgeladen); nur ohne MediaSource-Unterstützung nativ.
 */
export function initHeroVideos() {
    const videos = document.querySelectorAll('video[data-hls-src]');
    if (!videos.length) {
        return;
    }

    // Bei reduzierter Bewegung nur das Vorschaubild zeigen
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        videos.forEach((video) => video.removeAttribute('autoplay'));
        return;
    }

    videos.forEach(async (video) => {
        const src = video.dataset.hlsSrc;

        // hls.js bevorzugen (auch wenn der Browser HLS nativ kann, z.B. neuere Chrome-Versionen),
        // weil nur so die Startqualität gesteuert werden kann
        const { default: Hls } = await import('hls.js/light');
        if (Hls.isSupported()) {
            const hls = new Hls({
                capLevelToPlayerSize: true,
                // Erst laden, wenn die Startqualität feststeht
                autoStartLoad: false,
                // Optimistischere Bandbreiten-Schätzung, damit nicht mit der kleinsten Stufe gestartet wird
                abrEwmaDefaultEstimate: 5_000_000,
            });
            hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
                hls.startLevel = getStartLevel(data.levels, video);
                hls.startLoad();
                video.play().catch(() => {});
            });
            hls.loadSource(src);
            hls.attachMedia(video);
        } else {
            // Kein MediaSource (z.B. ältere iPhones): nativ abspielen
            video.src = src;
            video.play().catch(() => {});
        }
    });
}

/**
 * Kleinste Qualitätsstufe, die die Videobreite (inkl. Pixeldichte) abdeckt,
 * damit das Video direkt scharf startet.
 */
function getStartLevel(levels, video) {
    const targetWidth = video.clientWidth * (window.devicePixelRatio || 1);
    const sorted = levels
        .map((level, index) => ({ index, width: level.width || 0 }))
        .sort((a, b) => a.width - b.width);
    const match = sorted.find((level) => level.width >= targetWidth);
    return (match || sorted[sorted.length - 1]).index;
}
