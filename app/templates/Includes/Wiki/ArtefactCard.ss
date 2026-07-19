<a href="$Link" class="artefactcard" style="view-transition-name: artefactcard-$ID;">
    <div class="artefactcard_image" style="view-transition-name: artefactimage-$ID;">
        <% if $Image %>
            $Image.FocusFill(300,300)
        <% else %>
            <img src="_resources/app/client/images/placeholder-image.jpg" alt="Kein Bild verfügbar" style="width: 100%; height: 100%; object-fit: cover;">
        <% end_if %>
    </div>
    <div class="artefactcard_content">
        <h3 class="artefactcard_name">$Title</h3>
        <% if $ShortDescription %><p class="artefactcard_shortdescription">$ShortDescription</p><% end_if %>
        <% if $Jointime %><p class="artefactcard_jointime"><b>Erstmals genannt:</b> $Jointime</p><% end_if %>
    </div>
</a>
