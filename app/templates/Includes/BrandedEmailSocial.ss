<table cellspacing="0" cellpadding="0" border="0" role="presentation">
    <tr>
        <% if $InstagramLink %>
            <td style="padding: 5px 5px 5px 0;"><a href="$InstagramLink" target="_blank"><img src="$InstagramIconURL" width="32" height="32" alt="Instagram" border="0" style="display: block; width: 32px;"></a></td>
        <% end_if %>
        <% if $YoutubeLink %>
            <td style="padding: 5px 5px 5px 0;"><a href="$YoutubeLink" target="_blank"><img src="$YoutubeIconURL" width="32" height="32" alt="YouTube" border="0" style="display: block; width: 32px;"></a></td>
        <% end_if %>
        <td style="padding: 5px 0;"><a href="$WebsiteLink" target="_blank"><img src="$WebsiteIconURL" width="32" height="32" alt="Website" border="0" style="display: block; width: 32px;"></a></td>
    </tr>
</table>
