<!doctype html>
<html lang="de">
    <head>
        <% base_tag %>
        $MetaTags(false)
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta charset="utf-8">
        <title>$Title - $SiteConfig.Title</title>
        $ViteClient.RAW
        <link rel="stylesheet" href="$Vite('app/client/src/scss/main.scss')">

        <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png">
        <link rel="mask-icon" href="../mask_icon.svg" color="#ffffff">

        <meta property="og:title" content="$Title - $SiteConfig.Title" />
        <meta property="og:site_name" content="$Title" />
        <meta property="og:type" content="article" />
        <meta property="og:description" content="$Description">
        <meta property="og:url" content="$Link" />
        <% if $Image %>
            <meta property="og:image" content="$Image.Link" />
        <% else %>
            <meta property="og:image" content="../_resources/app/client/images/socialmedia.png" />
            <meta property="og:image:alt" content="Otto Woodmann vor einem dunklem Wald" />
        <% end_if %>
        <meta property="og:image:type" content="image/jpeg" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:locale" content="de_DE" />
        <meta name="twitter:card" content="summary_large_image">

        <meta name="msapplication-TileColor" content="#151515">
        <meta name="theme-color" content="#151515">

        $ViteClient.RAW
        <link rel="stylesheet" href="$Vite('app/client/src/scss/main.scss')">
        <link rel="manifest" href="site.webmanifest" />
    </head>
    <body class="body--PhotoboxGalleryNewest body--PhotoboxGalleryOnride">
        <canvas class="photobox_particles" aria-hidden="true"></canvas>

        <div class="section section--PhotoboxGalleryNewest section--PhotoboxGalleryOnride">
            <h1>Speichere Dir hier dein "OnRide"-Foto:</h1>
            <div class="photobox_gallery_wrapper">
                <div class="photobox_gallery">
                    <% loop $BoothImages.Limit(4) %>
                        <div class="photobox_image">
                            <p class="photobox_image_meta">$FormattedCreationDate</p>
                            <div class="photobox_image_content">$Image.Fill(1280, 720)</div>
                            <div class="photobox_qrcode">
                                <img src="$QRCode" alt="QR Code for Foto $HashID" />
                            </div>
                        </div>
                    <% end_loop %>
                </div>
            </div>
        </div>
        <script type="module" src="$Vite('app/client/src/js/main.js')"></script>
        <script>
        // Galerie alle 30 Sekunden aktualisieren
        function reloadGallery() {
            fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newGallery = doc.querySelector('.photobox_gallery');
                    const gallery = document.querySelector('.photobox_gallery');
                    if (newGallery && gallery) {
                        gallery.innerHTML = newGallery.innerHTML;
                    }
                });
        }
        setInterval(reloadGallery, 30000);

        // Langsam aufsteigende Partikel im Hintergrund
        (function () {
            const canvas = document.querySelector('.photobox_particles');
            if (!canvas || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const ctx = canvas.getContext('2d');
            const colors = ['255, 140, 40', '255, 190, 110', '200, 170, 255'];
            let particles = [];

            function createParticle(randomY) {
                return {
                    x: Math.random() * canvas.width,
                    y: randomY ? Math.random() * canvas.height : canvas.height + 10,
                    radius: Math.random() * 3 + 1.5,
                    speed: Math.random() * 0.25 + 0.05,
                    drift: Math.random() * Math.PI * 2,
                    alpha: Math.random() * 0.5 + 0.15,
                    color: colors[Math.floor(Math.random() * colors.length)],
                };
            }

            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                const count = Math.round(canvas.width * canvas.height / 20000);
                particles = Array.from({ length: count }, () => createParticle(true));
            }

            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.y -= p.speed;
                    p.drift += 0.005;
                    p.x += Math.sin(p.drift) * 0.15;
                    if (p.y < -10) particles[i] = createParticle(false);

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(${p.color}, ${p.alpha})`;
                    ctx.shadowColor = `rgba(${p.color}, 0.8)`;
                    ctx.shadowBlur = p.radius * 4;
                    ctx.fill();
                }
                requestAnimationFrame(draw);
            }

            window.addEventListener('resize', resize);
            resize();
            draw();
        })();
        </script>
    </body>
</html>
