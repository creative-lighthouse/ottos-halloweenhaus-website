<div class="section section--events">
    <div class="section_content">
        <h1>$Title</h1>

        <% if $Events %>
            <div
                class="events-navigator-app"
                data-capacity-url="{$Link}eventscapacity"
                <% if $PreselectEventID %>data-preselect-event="$PreselectEventID"<% end_if %>
            ></div>
            <script type="application/json" id="events-initial-data">$EventsDataJSON.RAW</script>
        <% else %>
            <p>Aktuell sind keine Veranstaltungen verfügbar. Schau später wieder vorbei!</p>
        <% end_if %>
    </div>
</div>
