<script setup lang="ts">
// Shows de-identified text with the masking tokens ([PATIENT], [ID_1], …) highlighted. No HTML injection: plain text spans only.
const props = defineProps<{ text: string }>()
const TOKEN = /(\[[A-Z_0-9]+\]|\{\{[A-Z_]+\}\})/g
const parts = computed(() => props.text.split(TOKEN).filter(Boolean).map((part) => ({ part, token: /^(\[[A-Z_0-9]+\]|\{\{[A-Z_]+\}\})$/.test(part) })))
</script>

<template>
  <span class="whitespace-pre-line">
    <template v-for="(p, i) in parts" :key="i">
      <span v-if="p.token" class="mono rounded px-1 text-[11.5px]" style="background: var(--violet-50); color: var(--violet-600)" title="بيانات مخفية لحماية الخصوصية">{{ p.part }}</span>
      <BidiText v-else :text="p.part" />
    </template>
  </span>
</template>
