<script setup lang="ts">
const config = useRuntimeConfig()
</script>

<template>
  <span v-if="config.public.mockApi" class="badge b-amber" title="البيانات المعروضة تجريبية وغير حقيقية">بيانات تجريبية</span>
</template>
