<script setup lang="ts">
const props = withDefaults(defineProps<{ value: number; size?: number }>(), { size: 92 })
const radius = 40
const circumference = 2 * Math.PI * radius
const dash = computed(() => `${(Math.min(100, Math.max(0, props.value)) / 100) * circumference} ${circumference}`)
const color = computed(() => (props.value >= 75 ? 'var(--teal-600)' : props.value >= 50 ? 'var(--gold-500)' : 'var(--red-600)'))
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 100 100" role="img" :aria-label="`قوة الموقف ${value}%`">
    <circle cx="50" cy="50" :r="radius" fill="none" stroke="var(--bg-2)" stroke-width="10" />
    <circle cx="50" cy="50" :r="radius" fill="none" :stroke="color" stroke-width="10" stroke-linecap="round"
      :stroke-dasharray="dash" transform="rotate(-90 50 50)" />
    <text x="50" y="50" text-anchor="middle" font-size="22" font-weight="700" fill="var(--ink)">{{ value }}%</text>
    <text x="50" y="66" text-anchor="middle" font-size="9" fill="var(--muted)">قوة الموقف</text>
  </svg>
</template>
