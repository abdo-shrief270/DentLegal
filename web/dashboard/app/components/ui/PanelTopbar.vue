<script setup lang="ts">
defineProps<{ title: string; crumb?: string }>()
</script>

<template>
  <header class="sticky top-0 z-10 flex min-h-16 flex-wrap items-center gap-3 border-b border-line bg-white px-6 py-3">
    <div class="min-w-0 grow">
      <div v-if="crumb" class="text-xs text-muted">{{ crumb }}</div>
      <h1 class="m-0 truncate text-[17px] font-bold">{{ title }}</h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </header>
</template>
