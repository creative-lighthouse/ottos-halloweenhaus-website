Neue Team-Bewerbung
<% with $Application %>
Name: $Title
Geburtstag: $Up.BirthdayFormatted
E-Mail: $Email

Hobbys, Talente & Interessen:
$Hobbies.XML

Warum ins Team?
$ReasonToJoin.XML

Interessensgebiete: <% if $Interests %><% loop $Interests %>$Title<% if not $IsLast %>, <% end_if %><% end_loop %><% else %>-<% end_if %>
<% end_with %>
Im Backend öffnen: $CMSLink
