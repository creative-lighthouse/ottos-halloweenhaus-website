<section class="section--WikiPage showoverview--itemdetails">
    <% with $Artefact %>
        <div class="section_content">
            <div class="wiki-navigation">
                <% if $PrevArtefact %>
                    <a class="link--button button-prev" href="$PrevArtefact.Link"></a>
                <% else %>
                    <span class="link--button button-prev link--buttondisabled"></span>
                <% end_if %>
                <a class="link--button button-overview" href="$Top.Link">↑ Zur Übersicht</a>
                <% if $NextArtefact %>
                    <a class="link--button button-next" href="$NextArtefact.Link"></a>
                <% else %>
                    <span class="link--button button-next link--buttondisabled"></span>
                <% end_if %>
            </div>
            <div class="showsection showsection--details" style="view-transition-name: locationcard-$ID;">
                <% if $Image %>
                    <a href="$Image.Url" data-gallery="gallery" data-galleryid="mainimage" class="artefact_image" style="view-transition-name: artefactimage-$ID;">
                        $Image.FocusFill(300,300)
                    </a>
                <% else %>
                    <br>
                <% end_if %>
                <h1 class="character_title" style="view-transition-name: locationtitle-$ID;">$Title</h1>
                <div class="character_description glossarizable">
                    $Description
                </div>
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
            <% if $ArtefactShows.Count > 0 %>
                <div class="showsection showsection--shows">
                    <h2>Vorkomnisse in Shows</h2>
                    <div class="showswiper swiper--showsoverview">
                        <div class="swiper-wrapper">
                            <% loop $ArtefactShows %>
                                <a href="$Link" class="swiper-slide showcard" style="view-transition-name: showcard-$ID;">
                                    <div class="showcard_image" style="view-transition-name: showposter-$ID;">
                                        $PosterImage.Fill(420,600)
                                    </div>
                                    <div class="showcard_content">
                                        <h2 class="showcard_dates" style="view-transition-name: showyear-$ID;">$Year</h2>
                                        <h3 class="showcard_title" style="view-transition-name: showtitle-$ID;">$Title</h3>
                                    </div>
                                </a>
                            <% end_loop %>
                        </div>
                    </div>
                </div>
            <% end_if %>
            <% if $Owners.Count > 0 %>
                <div class="showsection showsection--characters">
                    <h2>Bekannte Besitzer</h2>
                    <div class="charactersgrid">
                        <% loop $Owners %>
                            <a href="$Link" class="charactercard" style="view-transition-name: charactercard-$Character.ID;">
                                <div class="charactercard_image" style="view-transition-name: characterimage-$Character.ID;">
                                    $Character.Image.FocusFill(300,300)
                                </div>
                                <div class="charactercard_content">
                                    <h3 class="charactercard_name">$Character.Title</h3>
                                    <% if $ShortDescription %><p class="charactercard_shortdescription">$Character.ShortDescription</p><% end_if %>
                                    <p>Von $StartTime<% if $EndTime %> bis $EndTime('d.m.Y')<% end_if %></p>
                                </div>
                            </a>
                        <% end_loop %>
                    </div>
                </div>
            <% end_if %>
            <% if $Music.Count > 0 %>
                <div class="showsection showsection--music">
                    <h2>Musik und Leitmotive</h2>
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
