<section class="section--WikiPage showoverview--itemdetails">
    <% with $Show %>
        <div class="section_content">
            <div class="wiki-navigation">
                <% if $PrevShow %>
                    <a class="link--button button-prev" href="$PrevShow.Link"></a>
                <% else %>
                    <span class="link--button button-prev link--buttondisabled"></span>
                <% end_if %>
                <a class="link--button button-overview" href="$Top.Link">↑ Zur Übersicht</a>
                <% if $NextShow %>
                    <a class="link--button button-next" href="$NextShow.Link"></a>
                <% else %>
                    <span class="link--button button-next link--buttondisabled"></span>
                <% end_if %>
            </div>
            <div class="showsection showsection--details">
                <% if $ShowImage %>
                    <div class="overviewimage">
                        $ShowImage.FocusFill(1000,300)
                    </div>
                    <% if $PosterImage %>
                        <a class="posterimage" data-gallery="gallery" data-galleryid="main" href="$PosterImage.Url" style="view-transition-name: showposter-$ID;">
                            $PosterImage.FocusFill(300,450)
                        </a>
                    <% end_if %>
                <% else %>
                    <% if $PosterImage %>
                        <a class="posterimage posterimage--single" data-gallery="gallery" data-galleryid="main" href="$PosterImage.Url" style="view-transition-name: showposter-$ID;">
                            $PosterImage.FocusFill(300,450)
                        </a>
                    <% end_if %>
                    <br>
                <% end_if %>

                <h1 class="show_year" style="view-transition-name: showyear-$ID;">$Year</h1>
                <h2 class="show_title" style="view-transition-name: showtitle-$ID;">$Title</h2>
                <div class="show_storyline glossarizable">$Storyline</div>
            </div>
            <% if $PhotoGalleryImages.Count > 0 %>
                <div class="showsection showsection--gallery">
                    <h2>Galerie</h2>
                    <div class="imageswiper swiper--showsoverview">
                        <div class="swiper-wrapper">
                            <% loop $PhotoGalleryImages %>
                                <a href="$Image.Url" data-gallery="gallery" data-galleryid="locationgallery" <% if $Up.Images.Count <= 1 %>data-singleimage=true<% end_if %> <% if $Title %>data-description="$Title"<% end_if %> class="swiper-slide imagecard">
                                    $Image.FocusFill(500, 300)
                                </a>
                            <% end_loop %>
                        </div>
                    </div>
                </div>
            <% end_if %>
            <% if $GuestCount || $DaysOpen || $WalkingLength || $ShowLength || $TeamSize || $SceneCount || $StatisticsNote %>
                <div class="showsection showsection--numbers">
                    <h2>Daten & Fakten</h2>
                    <div class="section_text">
                        <% if $GuestCount %>
                            <p><b>Anzahl der Gäste:</b> $GuestCount</p>
                        <% end_if %>
                        <% if $DaysOpen %>
                            <p><b>Geöffnete Tage:</b> $DaysOpen</p>
                        <% end_if %>
                        <% if $WalkingLength %>
                            <p><b>Weglänge:</b> $WalkingLength Meter</p>
                        <% end_if %>
                        <% if $ShowLength %>
                            <p><b>Showlänge:</b> $ShowLength Minuten</p>
                        <% end_if %>
                        <% if $TeamSize %>
                            <p><b>Teamgröße:</b> $TeamSize Personen</p>
                        <% end_if %>
                        <% if $SceneCount %>
                            <p><b>Anzahl Szenen:</b> $SceneCount</p>
                        <% end_if %>
                        <% if $StatisticsNote %>
                            <p class="note">$StatisticsNote</p>
                        <% end_if %>
                    </div>
                </div>
            <% end_if %>
            <% if $GroupedCharacters.Count > 0 %>
                <div class="showsection showsection--characters">
                    <h2>Charaktere in der Show</h2>
                    <div class="charactersgrid">
                        $GroupedCharacters.GroupedBy('CharacterID').First.Title <!-- Not working -->
                        <% loop $GroupedCharacters.GroupedBy('CharacterID') %>
                            <% include Includes/Wiki/CharacterCard Character=$Children.First, Children=$Children %>
                        <% end_loop %>
                    </div>
                </div>
            <% end_if %>
            <% if $Locations.Count > 0 %>
                <div class="showsection showsection--locations">
                    <h2>Orte in der Show</h2>
                    <div class="locationsgrid">
                        <% loop $Locations %>
                            <a href="$Top.Link/location/$ID" class="locationcard" style="view-transition-name: locationcard-$ID;">
                                <div class="locationcard_image">
                                    $Image.FocusFill(300,300)
                                </div>
                                <div class="locationcard_content">
                                    <h3 class="locationcard_name">$Title</h3>
                                    <% if $ShortDescription %><p class="locationcard_shortdescription">$ShortDescription</p><% end_if %>
                                    <% if $Jointime %><p class="locationcard_jointime"><b>Erstmals genannt:</b> $Jointime</p><% end_if %>
                                </div>
                            </a>
                        <% end_loop %>
                    </div>
                </div>
            <% end_if %>
            <% if $Artefacts.Count > 0 %>
                <div class="showsection showsection--artefacts">
                    <h2>Wichtige Artefakte & Gegenstände in der Show</h2>
                    <div class="artefactgrid">
                        <% loop $Artefacts %>
                            <a href="$Top.Link/artefact/$ID" class="artefactcard" style="view-transition-name: artefactcard-$ID;">
                                <div class="artefactcard_image">
                                    $Image.FocusFill(200,200)
                                </div>
                                <div class="artefactcard_content">
                                    <h3 class="artefactcard_name">$Title</h3>
                                    <% if $ShortDescription %><p class="artefactcard_shortdescription">$ShortDescription</p><% end_if %>
                                    <% if $Jointime %><p class="artefactcard_jointime"><b>Erstmals genannt:</b> $Jointime</p><% end_if %>
                                </div>
                            </a>
                        <% end_loop %>
                    </div>
                </div>
            <% end_if %>
            <% if $Music.Count > 0 %>
                <div class="showsection showsection--music">
                    <h2>Musik und Leitmotive in der Show</h2>
                    <div class="musicgrid">
                        <% loop $Music %>
                            <% include Includes/Wiki/MusicCard %>
                        <% end_loop %>
                    </div>
                </div>
            <% end_if %>
        </div>
    <% end_with %>
</section>
