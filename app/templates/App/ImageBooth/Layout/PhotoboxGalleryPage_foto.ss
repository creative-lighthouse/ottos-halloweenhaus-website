<% with $BoothImage %>
    <div class="section section--PhotoboxImage">
        <div class="photobox_headline">
            <h1>Foto $HashID</h1>
            <h3>$FormattedCreationDate</h3>
        </div>
        <div class="photobox_image<% if $IsLandscape %> photobox_image--landscape<% end_if %>">
            $PreviewImage
        </div>
        <a class="photobox_downloadbutton" download href="$Image.Url" target="_blank" class="photobox_download">Download</a>
    </div>
<% end_with %>
