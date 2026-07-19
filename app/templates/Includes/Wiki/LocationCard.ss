<a href="$Link" class="locationcard" style="view-transition-name: locationcard-$ID;">
    <div class="locationcard_image">
        <% if $Image %>
            $Image.FocusFill(300,300)
        <% else %>
            <img src="_resources/app/client/images/placeholder-image.jpg" alt="Kein Bild verfügbar" style="width: 100%; height: 100%; object-fit: cover;">
        <% end_if %>
    </div>
    <div class="locationcard_content">
        <h3 class="locationcard_name" style="view-transition-name: locationtitle-$ID;">$Title</h3>
        <% if $ShortDescription %><p class="locationcard_shortdescription">$ShortDescription</p><% end_if %>
        <% if $Jointime %><p class="locationcard_jointime"><b>Erstmals genannt:</b> $Jointime</p><% end_if %>
    </div>
</a>
