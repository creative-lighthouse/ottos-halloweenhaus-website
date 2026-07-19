<section class="section--WikiPage showoverview--itemdetails">
    <% with $WikiMusic %>
        <div class="section_content">
            <div class="wiki-navigation">
                <% if $PrevMusic %>
                    <a class="link--button button-prev" href="$PrevMusic.Link"></a>
                <% else %>
                    <span class="link--button button-prev link--buttondisabled"></span>
                <% end_if %>
                <a class="link--button button-overview" href="$Top.Link">↑ Zur Übersicht</a>
                <% if $NextMusic %>
                    <a class="link--button button-next" href="$NextMusic.Link"></a>
                <% else %>
                    <span class="link--button button-next link--buttondisabled"></span>
                <% end_if %>
            </div>
            <div class="showsection showsection--details" style="view-transition-name: mediacard-$ID;">
                <h1 class="media_title" style="view-transition-name: mediatitle-$ID;">$Title</h1>
                <p class="media_publicationdate">Veröffentlicht am $RenderPublicationDate</p>
                <% if $Composer %>
                    <p class="media_composer">Komponiert von $Composer</p>
                <% end_if %>
                <% if $SoundFile %>
                    <div class="wikimusic_audioplayer">
                        <% include Includes/AudioPlayer SoundFile=$SoundFile, Preload=true %>
                    </div>
                <% else_if $MusicVideoLink %>
                    <div class="wikimusic_audioplayer">
                        <% include Includes/YoutubeAudioPlayer MusicVideoLink=$MusicVideoLink %>
                    </div>
                <% end_if %>
                <div class="media_description glossarizable">
                    $Description
                </div>
            </div>
            <% if $PhotoGalleryImages.Count > 0 %>
                <div class="showsection showsection--gallery">
                    <h2>Galerie</h2>
                    <div class="imageswiper swiper--showsoverview">
                        <div class="swiper-wrapper">
                            <% loop $PhotoGalleryImages %>
                                <a href="$Image.Url" data-gallery="gallery" data-galleryid="mediagallery" <% if $Up.Images.Count <= 1 %>data-singleimage=true<% end_if %> <% if $Title %>data-description="$Title"<% end_if %> class="swiper-slide imagecard">
                                    $Image.FocusFill(500, 300)
                                </a>
                            <% end_loop %>
                        </div>
                    </div>
                </div>
            <% end_if %>
            <% if $TeamMembers.Count > 0 %>
                <div class="showsection showsection--teammembers">
                    <h2>Beteiligte Teammitglieder</h2>
                    <div class="teammembersgrid">
                        <% loop $TeamMembers %>
                            <a href="$Link" class="teammembercard" style="view-transition-name: teammembercard-$ID;">
                                <div class="teammembercard_image" style="view-transition-name: teammemberimage-$ID;">
                                    $Image.FocusFill(200,200)
                                </div>
                                <h3 class="teammembercard_title">$Title</h3>
                            </a>
                        <% end_loop %>
                    </div>
                </div>
            <% end_if %>
            <% if $Links.Count > 0 %>
                <div class="showsection showsection--links">
                    <h2>Links</h2>
                    <ul class="wikilinks">
                        <% loop $Links %>
                            <li>
                                <a href="$Link" target="_blank">
                                    <% if $SocialPlatform.exists %>
                                        <span class="wikilink__icon" aria-hidden="true">$SocialPlatform.IconSVG</span>
                                    <% else %>
                                        <span class="wikilink__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l82.7 0L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3l0 82.7c0 17.7 14.3 32 32 32s32-14.3 32-32l0-160c0-17.7-14.3-32-32-32L320 0zM80 32C35.8 32 0 67.8 0 112L0 432c0 44.2 35.8 80 80 80l320 0c44.2 0 80-35.8 80-80l0-112c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 112c0 8.8-7.2 16-16 16L80 448c-8.8 0-16-7.2-16-16l0-320c0-8.8 7.2-16 16-16l112 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 32z"/></svg></span>
                                    <% end_if %>
                                    $Title
                                </a>
                            </li>
                        <% end_loop %>
                    </ul>
                </div>
            <% end_if %>
        </div>
    <% end_with %>
</section>
