<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html style="margin: 0; padding: 0; width: 100%;">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Neue Team-Bewerbung</title>
    </head>

    <body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5;">
        <h2>Neue Team-Bewerbung</h2>
        <% with $Application %>
        <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse;">
            <tr><td valign="top"><strong>Name</strong></td><td>$Title</td></tr>
            <tr><td valign="top"><strong>Geburtstag</strong></td><td>$Up.BirthdayFormatted</td></tr>
            <tr><td valign="top"><strong>E-Mail</strong></td><td><a href="mailto:$Email">$Email</a></td></tr>
            <tr><td valign="top"><strong>Hobbys, Talente &amp; Interessen</strong></td><td>$Hobbies</td></tr>
            <tr><td valign="top"><strong>Warum ins Team?</strong></td><td>$ReasonToJoin</td></tr>
            <tr><td valign="top"><strong>Interessensgebiete</strong></td><td><% if $Interests %><% loop $Interests %>$Title<% if not $IsLast %>, <% end_if %><% end_loop %><% else %>–<% end_if %></td></tr>
        </table>
        <% end_with %>
        <p><a href="$CMSLink">Bewerbung im Backend öffnen</a></p>
    </body>
</html>
