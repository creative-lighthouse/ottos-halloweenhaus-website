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

        <link rel="manifest" href="site.webmanifest" />

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-QW9HQK22TZ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-QW9HQK22TZ');
        </script>
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "LocalBusiness",
                "name": "Ottos Halloweenhaus",
                "image": "https://ottos-halloweenhaus.de/_resources/app/client/images/ohh_logo2026_profile_white.png",
                "description": "Ottos Halloweenhaus ist ein jährliches Event von Künstlerinnen und Künstlern rund um Stormarn. Hier fließen Dekorationen, Musik, Lichtdesign, Informatik, Schauspiel, Robotik, Film und viele weitere Fähigkeiten zusammen in eine etwa zwanzigminütige Show, welche das Highlight auf jeder Halloween Route ist.",
                "url": "https://ottos-halloweenhaus.de/",
                "email": "kontakt@ottos-halloweenhaus.de",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "Deepenstegen 25A",
                    "addressLocality": "Lütjensee",
                    "addressRegion": "Schleswig-Holstein",
                    "postalCode": "22952",
                    "addressCountry": "Deutschland"
                },
                "geo": {
                    "@type": "GeoCoordinates",
                    "latitude": "53.636641691530905",
                    "longitude": "10.383277797990278"
                },
                "priceRange": "Kostenlos",
                "paymentAccepted": "Spenden",
                "sameAs": [
                    "https://www.youtube.com/@OttosHalloweenhaus",
                    "https://www.instagram.com/ottos.halloweenhaus/"
                ],
                "publisher": {
                    "@type": "Organization",
                    "name": "Creative Lighthouse e.V.",
                    "url": "https://creative-lighthouse.com/"
                }
            }
        </script>
    </head>
    <body>
        <div class="area_header">
            <% include Header %>
        </div>
        <main class="area_content main">
            $Layout
        </main>
        <div class="area_footer">
            <% include Footer %>
        </div>
        <script type="module" src="$Vite('app/client/src/js/main.js')"></script>
    </body>
</html>
