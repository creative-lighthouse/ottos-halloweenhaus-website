<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    coupon: { type: Object, required: true },
    checkUrlBase: { type: String, required: true },
});

const emit = defineEmits(['valid', 'reset']);

function checkUrl(code) {
    const base = props.checkUrlBase.endsWith('/') ? props.checkUrlBase : `${props.checkUrlBase}/`;
    return `${base}checkCoupon/${encodeURIComponent(code)}`;
}

async function checkCoupon() {
    const code = props.coupon.code.trim();
    if (code === '') {
        props.coupon.message = 'Bitte geben Sie einen Coupon ein!';
        return;
    }

    props.coupon.checking = true;
    try {
        const response = await fetch(checkUrl(code), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ coupon: code }),
        });
        const result = await response.json();

        if (result.Valid) {
            props.coupon.valid = true;
            props.coupon.code = code;
            props.coupon.type = result.Type ?? null;
            props.coupon.message = `Der Code ${result.Code} (${result.Title}) ist gültig!`;
            props.coupon.description = result.Description ?? '';
            emit('valid');
        } else {
            props.coupon.valid = false;
            props.coupon.type = null;
            props.coupon.message = result.Message ?? 'Ungültiger Code';
            props.coupon.description = '';
        }
    } catch (error) {
        console.error('Coupon-Prüfung fehlgeschlagen', error);
        props.coupon.message = 'Die Coupon-Prüfung ist fehlgeschlagen. Bitte versuche es erneut.';
    } finally {
        props.coupon.checking = false;
    }
}

function resetCoupon() {
    props.coupon.valid = false;
    props.coupon.code = '';
    props.coupon.message = 'Bitte gib deinen Couponcode ein';
    props.coupon.description = '';
    props.coupon.type = null;
    emit('reset');
}

onMounted(() => {
    const fromUrl = new URL(window.location.href).searchParams.get('coupon');
    if (fromUrl) {
        props.coupon.code = fromUrl;
        checkCoupon();
    }
});
</script>

<template>
    <div class="events_navigator_step coupon" :class="{ loading: coupon.checking }">
        <h3>Du hast einen Coupon von uns erhalten?</h3>

        <div v-if="!coupon.valid" class="section_couponform">
            <input
                v-model="coupon.code"
                type="text"
                class="coupon_input"
                placeholder="Couponcode eingeben"
                @keyup.enter="checkCoupon"
            >
            <button class="coupon_button" :disabled="coupon.checking" @click="checkCoupon">
                {{ coupon.checking ? 'Prüfe…' : 'Absenden' }}
            </button>
        </div>

        <p
            class="coupon_message"
            :class="{ valid: coupon.valid, invalid: !coupon.valid && coupon.message }"
        >
            {{ coupon.message }}
        </p>

        <a v-if="coupon.valid" class="coupon_reset link--button button--secondary" @click="resetCoupon">X Coupon entfernen</a>
        <p v-if="coupon.description" class="coupon_description">{{ coupon.description }}</p>
    </div>
</template>
