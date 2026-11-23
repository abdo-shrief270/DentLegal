<script setup lang="ts">
// Until the client's lawyer approves the texts (S9.2), the legal pages say so plainly.
const { legalApproved } = useSite()
</script>

<template>
  <p v-if="!legalApproved" class="card !p-4 bg-gold-100 border-gold-500 text-ink-2 text-sm" role="note">
    هذا النص قيد المراجعة القانونية النهائية، وقد يُحدَّث قبل الإطلاق. ستُبلَّغ بأي تغيير جوهري داخل التطبيق.
  </p>
</template>
