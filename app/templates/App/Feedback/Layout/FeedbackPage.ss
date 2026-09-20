<div class="section section--feedback">
    <div class="section_content">
        <h1>Wir freuen uns auf Dein Feedback</h1>

        <form class="feedback_form" method="post" action="$Link('sendfeedback')">
            <div class="form-group">
                <p>An welchem Tag warst/bist Du da?</p>
                <div class="switch">
                    <% loop $EventDates.GroupedBy('EventDate') %>
                        <input type="radio" name="day" id="day-$Children.First.ID" value="$Children.First.EventDate" required>
                        <label for="day-$Children.First.ID">
                            <p class="weekday">$Children.First.DateWeekday</p>
                            <p>$Children.First.DateFormatted</p>
                        </label>
                    <% end_loop %>
                </div>
            </div>

            <div class="container">
                <div class="feedback">
                    <p>Wie hat Dir die Show gefallen?</p>
                    <div class="rating">
                        <input type="radio" name="rating" id="rating-5" value="5" required>
                        <label for="rating-5"></label>
                        <input type="radio" name="rating" id="rating-4" value="4">
                        <label for="rating-4"></label>
                        <input type="radio" name="rating" id="rating-3" value="3">
                        <label for="rating-3"></label>
                        <input type="radio" name="rating" id="rating-2" value="2">
                        <label for="rating-2"></label>
                        <input type="radio" name="rating" id="rating-1" value="1">
                        <label for="rating-1"></label>
                        <div class="emoji-wrapper">
                            <div class="emoji">
                                <img class="rating-0" src="../_resources/app/client/images/hw40a_emoji_neutral.svg" alt="Logo des Halloweenhauses">
                                <img class="rating-1" src="../_resources/app/client/images/hw40a_emoji_reallysad.svg" alt="Logo des Halloweenhauses">
                                <img class="rating-2" src="../_resources/app/client/images/hw40a_emoji_sad.svg" alt="Logo des Halloweenhauses">
                                <img class="rating-3" src="../_resources/app/client/images/hw40a_emoji_neutral.svg" alt="Logo des Halloweenhauses">
                                <img class="rating-4" src="../_resources/app/client/images/hw40a_emoji_happy.svg" alt="Logo des Halloweenhauses">
                                <img class="rating-5" src="../_resources/app/client/images/hw40a_emoji_reallyhappy.svg" alt="Logo des Halloweenhauses">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="comment">Was hat Dir gut gefallen oder was könnten wir nächstes Jahr verbessern?</label>
                <textarea rows="3" cols="33" class="form-control" id="comment" name="comment"></textarea>
            </div>

            <div class="form-group">
                <label for="PLZ">Wie lautet Deine Postleitzahl (optional)?</label>
                <input type="text" class="form-control" id="plz" name="plz" maxlength="5" pattern="[0-9]*" inputmode="numeric">
            </div>

            <div class="form-group">
                <input type="submit" class="link--button button--primary" name="submit" value="Feedback senden">
            </div>
        </form>
    </div>
</div>
