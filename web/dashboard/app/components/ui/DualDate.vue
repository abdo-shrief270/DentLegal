<script setup lang="ts">
const props = withDefaults(defineProps<{ value: string; withTime?: boolean; inline?: boolean }>(), { withTime: false, inline: false })
const gregorian = computed(() => formatGregorian(props.value, true) + (props.withTime ? ` · ${formatTime(props.value)}` : ''))
const hijri = computed(() => formatHijri(props.value))
</script>

<template>
  <span v-if="inline">{{ gregorian }} <span class="faint">({{ hijri }})</span></span>
  <span v-else class="inline-flex flex-col leading-tight">
    <span>{{ gregorian }}</span>
    <span class="text-[11.5px] faint">{{ hijri }}</span>
  </span>
</template>
