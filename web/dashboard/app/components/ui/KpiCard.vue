<script setup lang="ts">
import type { IconName } from '~/utils/icons'

defineProps<{ icon: IconName; value: string | number; label: string; detail?: string }>()
</script>

<template>
  <div class="kpi">
    <span class="ic"><AppIcon :name="icon" :size="22" /></span>
    <div class="min-w-0">
      <div class="v">{{ value }}</div>
      <div class="l">{{ label }}</div>
      <div v-if="detail" class="d">{{ detail }}</div>
    </div>
  </div>
</template>
