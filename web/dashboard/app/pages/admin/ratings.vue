<script setup lang="ts">
import type { RatingRow, RatingTarget } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'التقييمات — DentLegal' })

const api = useApi()
const toast = useToast()
const rows = ref<RatingRow[]>([])
const loading = ref(true)
const target = ref<RatingTarget | 'all'>('all')
const useful = ref<'all' | 'yes' | 'no'>('all')
const opened = ref<RatingRow | null>(null)

const targetOptions = [{ label: 'الكل', value: 'all' }, ...(Object.keys(RATING_TARGET_LABELS) as RatingTarget[]).map((v) => ({ label: RATING_TARGET_LABELS[v], value: v }))]
const usefulOptions = [{ label: 'الكل', value: 'all' }, { label: 'مفيدة', value: 'yes' }, { label: 'غير مفيدة', value: 'no' }]

const filtered = computed(() => rows.value.filter((r) => (target.value === 'all' || r.targetType === target.value) && (useful.value === 'all' || r.useful === (useful.value === 'yes'))))
const rate = (list: RatingRow[]) => (list.length ? list.filter((r) => r.useful).length / list.length : 0)
const summary = computed(() => ({
  overall: rate(rows.value),
  byType: (Object.keys(RATING_TARGET_LABELS) as RatingTarget[]).map((t) => ({ t, list: rows.value.filter((r) => r.targetType === t) })),
}))

onMounted(async () => {
  try { rows.value = await api.admin.listRatings() } catch (e) { toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 }) } finally { loading.value = false }
})

function exportCsv() {
  downloadCsv('dentlegal-ratings.csv', ['التاريخ', 'الطبيب', 'النوع', 'العنصر', 'التقييم', 'التعليق'],
    filtered.value.map((r) => [r.createdAt, r.dentistName, RATING_TARGET_LABELS[r.targetType], r.targetLabel, r.useful ? 'مفيدة' : 'غير مفيدة', r.comment]))
}
</script>

<template>
  <div>
    <PanelTopbar title="التقييمات" crumb="لوحة الإدارة / الجودة">
      <template #actions>
        <MockDataBadge />
        <Button size="small" severity="secondary" outlined :disabled="!filtered.length" @click="exportCsv"><AppIcon name="download" :size="16" /> تصدير CSV</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard icon="checkCircle" :value="percent(summary.overall)" label="«مفيدة» إجمالًا" :detail="`${rows.length} تقييمًا · الهدف 80%`" />
        <KpiCard v-for="s in summary.byType" :key="s.t" icon="chart" :value="s.list.length ? percent(rate(s.list)) : '—'" :label="RATING_TARGET_LABELS[s.t]" :detail="`${s.list.length} تقييمات`" />
      </div>

      <div class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-line p-3">
          <SelectButton v-model="target" :options="targetOptions" option-label="label" option-value="value" :allow-empty="false" size="small" />
          <SelectButton v-model="useful" :options="usefulOptions" option-label="label" option-value="value" :allow-empty="false" size="small" />
        </div>
        <DataTable :value="filtered" :loading="loading" data-key="id" paginator :rows="10" row-hover @row-click="(e) => (opened = e.data)">
          <template #empty><div class="py-10 text-center muted">لا توجد تقييمات مطابقة.</div></template>
          <Column header="التاريخ"><template #body="{ data }"><DualDate :value="data.createdAt" with-time /></template></Column>
          <Column field="dentistName" header="الطبيب" />
          <Column header="النوع"><template #body="{ data }">{{ RATING_TARGET_LABELS[data.targetType as RatingTarget] }}</template></Column>
          <Column field="targetLabel" header="العنصر" />
          <Column header="التقييم"><template #body="{ data }"><span class="badge" :class="data.useful ? 'b-green' : 'b-red'">{{ data.useful ? 'مفيدة' : 'غير مفيدة' }}</span></template></Column>
          <Column header="التعليق"><template #body="{ data }"><span v-if="data.comment">{{ data.comment }}</span><span v-else class="faint">—</span></template></Column>
        </DataTable>
      </div>
    </div>

    <Dialog :visible="!!opened" modal :header="opened?.targetLabel ?? ''" :style="{ width: '520px' }" @update:visible="(v: boolean) => !v && (opened = null)">
      <div v-if="opened" class="flex flex-col gap-3 text-[13.5px]">
        <div class="flex flex-wrap gap-2"><span class="badge b-gray">{{ RATING_TARGET_LABELS[opened.targetType] }}</span><span class="badge" :class="opened.useful ? 'b-green' : 'b-red'">{{ opened.useful ? 'مفيدة' : 'غير مفيدة' }}</span></div>
        <div class="rounded-xl p-3 leading-8" style="background: var(--bg)">{{ opened.targetExcerpt }}</div>
        <div v-if="opened.comment"><b>تعليق الطبيب:</b> {{ opened.comment }}</div>
        <div class="text-xs muted">{{ opened.dentistName }} · <DualDate :value="opened.createdAt" with-time inline /></div>
      </div>
    </Dialog>
  </div>
</template>
