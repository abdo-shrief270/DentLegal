<script setup lang="ts">
import type { RiskLevel } from '~/types/api'

const props = defineProps<{ risk: RiskLevel }>()
const meta = computed(() => RISK_LABELS[props.risk])
</script>

<template>
  <span class="badge" :class="meta.cls">مخاطر {{ meta.label }}</span>
</template>
