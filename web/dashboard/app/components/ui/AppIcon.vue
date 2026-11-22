<script setup lang="ts">
import { ICONS, type IconName } from '~/utils/icons'

const props = withDefaults(defineProps<{ name: IconName; size?: number }>(), { size: 18 })
// Icon markup is a fixed constant copied from the prototype, never user input.
const markup = computed(() => ICONS[props.name])
</script>

<template>
  <svg
    :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0" v-html="markup"
  />
</template>
