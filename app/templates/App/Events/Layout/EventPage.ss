<div class="section section--events">
    <div class="section_content">
        <h1>$Title</h1>

        <% if $Events %>
            <div class="events_navigator" data-behaviour="eventsNavigator">

                <% if $UsesCoupon %>
                    <div class="events_navigator_step coupon" data-eventstep="coupon">
                        <h3>Du hast einen Coupon von uns erhalten?</h3>
                        <div class="section_couponform" data-behaviour="coupon_form">
                            <input type="text" class="coupon_input" data-behaviour="coupon_input" placeholder="Couponcode eingeben">
                            <button class="coupon_button" data-behaviour="coupon_button">Absenden</button>
                        </div>
                        <p class="coupon_message" data-behaviour="coupon_message">Bitte gib deinen Couponcode ein</p>
                        <a class="coupon_reset" data-behaviour="coupon_reset">X Coupon entfernen</a>
                        <p class="coupon_description" data-behaviour="coupon_description"></p>
                    </div>
                <% end_if %>

                <div class="events_navigator_step dates hidden" data-eventstep="1">
                    <h2>1. Datum wählen</h2>
                    <div class="section_selectablelist">
                        <% loop $GroupedEvents.GroupedBy('EventDate') %>
                            <div class="date_card" data-behaviour="date" data-date="$Children.First.EventDate">
                                <p class="date_card_weekday">$Children.First.DateWeekday</p>
                                <p class="date_card_day">$Children.First.DateDay</p>
                                <p class="date_card_month">$Children.First.DateMonthTitle</p>
                            </div>
                        <% end_loop %>
                    </div>
                </div>

                <div class="events_navigator_step events hidden" data-eventstep="2">
                    <h2>2. Veranstaltung wählen</h2>
                    <div class="section_selectablelist">
                        <% loop $GroupedEvents.GroupedBy('EventDate') %>
                            <% loop $Children %>
                                <div class="event_card" data-behaviour="event" data-date="$EventDate" data-eventID="$ID">
                                    <div class="event_card_image">
                                        <% if $Image %><img src="$Image.FocusFill(500, 200).URL" alt="$Image.Title"><% end_if %>
                                    </div>
                                    <div class="event_card_text">
                                        <p class="event_card_title">$Title</p>
                                        <p class="event_card_duration">Dauer: ca. $SlotDuration Minuten</p>
                                    </div>
                                </div>
                            <% end_loop %>
                        <% end_loop %>
                    </div>
                </div>

                <div class="events_navigator_step timeslots hidden" data-eventstep="3">
                    <h2>3. Startzeit wählen</h2>
                    <div class="section_selectablelist">
                        <% loop $GroupedEvents.GroupedBy('EventDate') %>
                            <% loop $Children %>
                                <% if $FreeTimeSlotsInFuture.Count > 0 %>
                                    <% loop $FreeTimeSlotsInFuture %>
                                        <div class="timeslot_card" data-behaviour="timeslot" data-slotId="$ID" data-slotsize="$getFreeSlotCount" data-eventID="$Parent.ID">
                                            <p class="timeslot_card_time">$SlotTimeFormatted</p>
                                            <p class="timeslot_card_capacity">$AttendeesFormatted</p>
                                        </div>
                                    <% end_loop %>
                                    <% loop $FullTimeSlots %>
                                        <div class="timeslot_card timeslot_card--full" data-behaviour="timeslot" data-slotId="$ID" data-slotsize="0" data-eventID="$Parent.ID">
                                            <p class="timeslot_card_time">$SlotTimeFormatted</p>
                                            <p class="timeslot_card_capacity">Ausgebucht</p>
                                        </div>
                                    <% end_loop %>
                                <% else %>
                                    <p class="timeslot_card timeslot_card--text" data-behaviour="timeslot" data-slotsize="0" data-eventID="$ID">Aktuell sind leider keine freien Zeitslots mehr verfügbar. Besuche uns dennoch gerne über die reguläre Warteschlange.</p>
                                    <% loop $FullTimeSlots %>
                                        <div class="timeslot_card timeslot_card--full" data-behaviour="timeslot" data-slotId="$ID" data-slotsize="0" data-eventID="$Parent.ID">
                                            <p class="timeslot_card_time">$SlotTimeFormatted</p>
                                            <p class="timeslot_card_capacity">Ausgebucht</p>
                                        </div>
                                    <% end_loop %>
                                <% end_if %>

                                <% if $FreeCouponTimeSlotsInFuture.Count > 0 %>
                                    <% loop $FreeCouponTimeSlotsInFuture %>
                                        <div class="timeslot_card" data-behaviour="coupontimeslot" data-slotId="$ID" data-slotsize="$getFreeCouponSlotCount" data-eventID="$Parent.ID">
                                            <p class="timeslot_card_time">$SlotTimeFormatted</p>
                                            <p class="timeslot_card_capacity">$CouponAttendeesFormatted</p>
                                        </div>
                                    <% end_loop %>
                                    <% loop $FullCouponTimeSlots %>
                                        <div class="timeslot_card timeslot_card--full" data-behaviour="coupontimeslot" data-slotId="$ID" data-slotsize="0" data-eventID="$Parent.ID">
                                            <p class="timeslot_card_time">$SlotTimeFormatted</p>
                                            <p class="timeslot_card_capacity">Ausgebucht</p>
                                        </div>
                                    <% end_loop %>
                                <% else %>
                                    <p class="timeslot_card timeslot_card--text" data-behaviour="coupontimeslot" data-slotsize="0" data-eventID="$ID">Aktuell sind leider keine freien Zeitslots mehr verfügbar. Besuche uns dennoch gerne über die reguläre Warteschlange.</p>
                                <% end_if %>
                            <% end_loop %>
                        <% end_loop %>
                    </div>
                    <p class="timeslot_info_text">Weitere Zeitslots werden regelmäßig freigeschaltet. Unsere reguläre Warteschlange vor Ort hat zusätzlich geöffnet und benötigt keine Buchung.</p>
                    <button class="timeslot_dialog_button" onclick="timeslot_dialog.showModal()">Weitere Informationen</button>
                    <dialog class="timeslot_dialog" id="timeslot_dialog">
                        <h1 class="text-center">Hinweis zu den Zeitslots der Halloween Shows</h1>
                        <p class="text-center">Sollte kein passender Zeitslot verfügbar sein, <strong>versuche es bitte später erneut oder komm einfach vorbei</strong> und stelle dich in die <strong>reguläre Warteschlange.</strong></p>
                        <p class="text-center">Die <strong>digitalen Zeitslots für die Halloween-Shows</strong> sind aufgrund der hohen Nachfrage limitiert. Regelmäßig werden neue Plätze freigeschaltet.</p>
                        <p class="text-center">Sollte deine Gruppe <strong>größer als $SiteConfig.MaxGroupSize Personen sein</strong>, buche gerne zwei aufeinander folgende Zeitslots und gib am Eingang Bescheid. Ihr könnt dann auch gemeinsam die Show genießen.</p>
                        <p class="text-center">Die <strong>Behind the Scenes Touren</strong> haben keine reguläre Warteschlange und sind auf die angegebenen Plätze limitiert.</p>
                        <p class="text-center">Weitere Informationen zu den Zeitslots, unserer virtuellen Warteschlange, dem Einlass und der Show findest Du auch in unseren <a href="/faq">FAQs</a>.</p>
                        <button class="timeslot_dialog_button" onclick="timeslot_dialog.close()">Schließen</button>
                    </dialog>
                </div>

                <div class="events_navigator_step groupsize hidden" data-eventstep="4">
                    <h2>4. Gruppengröße wählen</h2>
                    <div class="section_selectablelist">
                        <% loop $SiteConfig.MaxGroupSizeAsArraySize %>
                            <a class="groupsize_button" data-behaviour="groupsize-button" data-groupsize="$Pos"><% if $Pos == 1 %>1 Person<% else %>$Pos Personen<% end_if %></a>
                        <% end_loop %>
                    </div>
                </div>

                <div class="events_navigator_step form hidden" data-eventstep="5">
                    <h2>5. Anmelden</h2>
                    $RegistrationForm
                </div>
            </div>
        <% else %>
            <p>Aktuell sind keine Veranstaltungen verfügbar. Schau später wieder vorbei!</p>
        <% end_if %>
    </div>
</div>
