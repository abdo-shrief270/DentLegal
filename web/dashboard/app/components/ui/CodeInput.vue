<script setup lang="ts">
// Single 6-digit code field (TOTP). One input instead of six boxes: works with RTL pages,
// paste, and password-manager / OS "one-time-code" autofill.
const model = defineModel<string>({ required: true })
defineProps<{ id?: string; label?: string }>()

function onInput(event: Event) {
  model.value = (event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 6)
}
</script>

<template>
  <InputText
    :id="id" :model-value="model" :aria-label="label ?? 'رمز التحقق'" inputmode="numeric" autocomplete="one-time-code"
    maxlength="6" dir="ltr" placeholder="••••••" class="mono mx-auto w-56 text-center !text-2xl tracking-[0.5em]" @input="onInput"
  />
</template>
