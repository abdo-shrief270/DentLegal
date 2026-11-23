<script setup lang="ts">
const base = useRuntimeConfig().app.baseURL
</script>

<template>
  <NuxtLink to="/" class="flex items-center gap-2 no-underline" aria-label="DentLegal — الصفحة الرئيسية">
    <img :src="`${base}favicon.svg`" width="32" height="32" alt="" />
    <span class="text-lg font-bold text-white" dir="ltr">DentLegal</span>
  </NuxtLink>
</template>
