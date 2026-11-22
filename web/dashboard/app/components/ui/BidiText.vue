<script setup lang="ts">
// Renders Arabic text with Latin/number codes (e.g. 21-RD-0045871, DL-2026-0152) isolated left-to-right,
// so they are not scrambled by the bidi algorithm. Plain text only, no HTML.
const props = defineProps<{ text: string }>()
const CODE = /([A-Za-z0-9]+(?:[-/][A-Za-z0-9]+)+)/g
const parts = computed(() => props.text.split(CODE).filter(Boolean).map((part) => ({ part, code: /^[A-Za-z0-9]+(?:[-/][A-Za-z0-9]+)+$/.test(part) && /[A-Za-z]|-\d/.test(part) })))
</script>

<template>
  <span><template v-for="(p, i) in parts" :key="i"><bdi v-if="p.code" dir="ltr" class="whitespace-nowrap">{{ p.part }}</bdi><template v-else>{{ p.part }}</template></template></span>
</template>
