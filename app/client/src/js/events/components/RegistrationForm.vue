<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    actionUrl: { type: String, required: true },
    securityId: { type: String, default: '' },
    eventId: { type: [Number, String], required: true },
    timeslotId: { type: [Number, String], required: true },
    groupSize: { type: [Number, String], required: true },
    couponCode: { type: String, default: '' },
    eventTitle: { type: String, default: '' },
});

const form = reactive({
    Title: '',
    Email: '',
    PLZ: '',
    DataPrivacy: false,
});

const errors = reactive({});
const submitted = ref(false);

const emailLooksProblematic = computed(() => /@(gmx|web)\.de\s*$/i.test(form.Email.trim()));

function validate() {
    for (const key of Object.keys(errors)) delete errors[key];

    if (!form.Title.trim()) {
        errors.Title = 'Bitte gib deinen Vor- & Nachnamen an.';
    }
    if (!form.Email.trim()) {
        errors.Email = 'Bitte gib deine E-Mail-Adresse an.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.Email.trim())) {
        errors.Email = 'Bitte gib eine gültige E-Mail-Adresse an.';
    }
    if (!form.DataPrivacy) {
        errors.DataPrivacy = 'Bitte stimme der Verarbeitung deiner Daten zu.';
    }

    return Object.keys(errors).length === 0;
}

function onSubmit(event) {
    submitted.value = true;
    if (!validate()) {
        event.preventDefault();
    }
    // Otherwise the browser performs a normal POST and follows the server redirect
    // to registrationsuccessful / registrationfull / couponinvalid.
}
</script>

<template>
    <form :action="actionUrl" method="post" @submit="onSubmit">
        <p>
            Bitte fülle das Formular vollständig aus, um Dich für
            <strong>{{ eventTitle }}</strong> anzumelden.
        </p>

        <input type="hidden" name="SecurityID" :value="securityId">
        <input type="hidden" name="EventID" :value="eventId">
        <input type="hidden" name="TimeSlotID" :value="timeslotId">
        <input type="hidden" name="GroupSize" :value="groupSize">
        <input type="hidden" name="Couponcode" :value="couponCode">

        <fieldset>
            <div class="field">
                <label for="RegistrationForm_Title">Vor- &amp; Nachname</label>
                <input
                    id="RegistrationForm_Title"
                    v-model="form.Title"
                    type="text"
                    name="Title"
                    autocomplete="name"
                >
                <p v-if="errors.Title" class="form_error">{{ errors.Title }}</p>
            </div>

            <div class="field">
                <label for="RegistrationForm_Email">E-Mail-Adresse</label>
                <input
                    id="RegistrationForm_Email"
                    v-model="form.Email"
                    type="email"
                    name="Email"
                    autocomplete="email"
                >
                <p v-if="errors.Email" class="form_error">{{ errors.Email }}</p>
                <p v-else-if="emailLooksProblematic" class="form_hint form_hint--warn">
                    Achtung: GMX- und Web.de-Adressen empfangen unsere Mails aktuell nicht zuverlässig. Schreib uns an events@ottos-halloweenhaus.de solltest du keine Mail erhalten.
                </p>
            </div>

            <div class="field">
                <label for="RegistrationForm_PLZ">Postleitzahl (optional)</label>
                <input
                    id="RegistrationForm_PLZ"
                    v-model="form.PLZ"
                    type="number"
                    name="PLZ"
                    autocomplete="postal-code"
                >
            </div>

            <div class="field field--checkbox">
                <label>
                    <input v-model="form.DataPrivacy" type="checkbox" name="DataPrivacy" value="1">
                    <span>
                        Ich habe die
                        <a href="impressum-und-datenschutz">Datenschutzerklärung</a>
                        gelesen und willige ein, dass meine Daten im Sinne der DSGVO verwendet werden.
                    </span>
                </label>
                <p v-if="errors.DataPrivacy" class="form_error">{{ errors.DataPrivacy }}</p>
            </div>
        </fieldset>

        <div class="btn-toolbar">
            <button type="submit" name="action_completeregistration" class="registration_submit">
                Absenden
            </button>
        </div>
    </form>
</template>

<style scoped>
.form_error {
    color: #ff6b6b;
    font-size: 13px;
    margin: 4px 0 0;
}

.form_hint {
    color: #c9c9c9;
    font-size: 12px;
    margin: 4px 0 0;
}

.form_hint--warn {
    color: #ffcf5c;
}

.field--checkbox label {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    justify-content: center;
    text-align: left;
    max-width: 480px;
    margin: 0 auto;
}

.registration_submit {
    background-color: var(--ColorPrimary);
    color: var(--ColorWhite);
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 700;
    transition: background-color 0.1s ease-in-out;
}

.registration_submit:hover {
    background-color: var(--ColorPrimaryDark);
}
</style>
