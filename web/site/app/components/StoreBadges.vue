<script setup lang="ts">
// Shown only once the store URLs are set at build time (§7.3: badges appear when the apps are live).
const { appStoreUrl, playStoreUrl } = useSite()
</script>

<template>
  <div v-if="appStoreUrl || playStoreUrl" class="flex flex-wrap gap-3">
    <a v-if="appStoreUrl" :href="appStoreUrl" class="btn btn-ghost" rel="noopener">حمّله من App Store</a>
    <a v-if="playStoreUrl" :href="playStoreUrl" class="btn btn-ghost" rel="noopener">احصل عليه من Google Play</a>
  </div>
</template>
