<% if $Character %>
    <% with $Character %>
        <div class="charactercard" style="view-transition-name: charactercard-$ID;">
            <a href="$Character.Link" class="charactercard__link" aria-label="$Character.Title"></a>
            <div class="charactercard_image" style="view-transition-name: characterimage-$ID;">
                <% if $RoleImage %>
                    $RoleImage.FocusFill(300,300)
                <% else_if $Character.Image %>
                    $Character.Image.FocusFill(300,300)
                <% else %>
                    <img src="_resources/app/client/images/placeholder-image.jpg" alt="Kein Bild verfügbar" style="width: 100%; height: 100%; object-fit: cover;">
                <% end_if %>
            </div>
            <div class="charactercard_content">
                <h3 class="charactercard_name">$Character.Title</h3>
                <% if $Character.ShortDescription %><p class="charactercard_shortdescription">$Character.ShortDescription</p><% end_if %>
                <% if $Character.Jointime %><p class="charactercard_jointime"><b>Erstauftritt:</b> $Character.Jointime</p><% end_if %>
                <% if $Character.Type == 'animatronic' %>
                    <p class="character_actorlist_title title--animatronic">Animatronic</p>
                    <% if $Top.Children.Count > 0 %>
                        <p class="character_actorlist_title">Umgesetzt von:</p>
                        <div class="character_actorlist">
                            <% loop $Top.Children %>
                                <% if $TeamMember.Title %>
                                    <a href="$TeamMember.Link" class="character_actor">
                                        $TeamMember.Image
                                        <p>$TeamMember.Title</p>
                                    </a>
                                <% else %>
                                    <p class="character_actor unknown_actor">Unbekannt</p>
                                <% end_if %>
                            <% end_loop %>
                        </div>
                    <% end_if %>
                <% else %>
                    <% if $Top.Children.Count > 0 %>
                        <p class="character_actorlist_title">Gespielt von:</p>
                        <div class="character_actorlist">
                            <% loop $Top.Children %>
                                <% if $TeamMember.Title %>
                                    <a href="$TeamMember.Link" class="character_actor">
                                        $TeamMember.Image
                                        <p>$TeamMember.Title</p>
                                    </a>
                                <% else %>
                                    <p class="character_actor unknown_actor">Unbekannt</p>
                                <% end_if %>
                            <% end_loop %>
                        </div>
                    <% end_if %>
                <% end_if %>
            </div>
        </div>
    <% end_with %>
<% else %>
    <div class="charactercard" style="view-transition-name: charactercard-$ID;">
        <a href="$Link" class="charactercard__link" aria-label="$Title"></a>
        <div class="charactercard_image" style="view-transition-name: characterimage-$ID;">
            <% if $Image %>
                $Image.FocusFill(300,300)
            <% else %>
                <img src="_resources/app/client/images/placeholder-image.jpg" alt="Kein Bild verfügbar" style="width: 100%; height: 100%; object-fit: cover;">
            <% end_if %>
        </div>
        <div class="charactercard_content">
            <h3 class="charactercard_name">$Title</h3>
            <% if $ShortDescription %><p class="charactercard_shortdescription">$ShortDescription</p><% end_if %>
            <% if $Jointime %><p class="charactercard_jointime"><b>Erstauftritt:</b> $Jointime</p><% end_if %>
            <% if $Top.Children.Count > 0 %>
                <% if $Type == 'animatronic' %>
                    <p class="character_actorlist_title">Animatronik</p>
                <% else %>
                    <p class="character_actorlist_title">Gespielt von:</p>
                    <div class="character_actorlist">
                        <% loop $Top.Children %>
                            <a href="$TeamMember.Link" class="character_actor">
                                $TeamMember.Image
                                <p>$TeamMember.Title</p>
                            </a>
                        <% end_loop %>
                    </div>
                <% end_if %>
            <% end_if %>
        </div>
    </div>
<% end_if %>
