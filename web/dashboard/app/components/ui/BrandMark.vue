<script setup lang="ts">
withDefaults(defineProps<{ size?: number }>(), { size: 38 })
</script>

<template>
  <span class="grid place-items-center rounded-xl text-white" :style="{ width: `${size}px`, height: `${size}px`, background: 'var(--teal-600)' }">
    <AppIcon name="logoMark" :size="Math.round(size * 0.58)" />
  </span>
</template>
