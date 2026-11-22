<script setup lang="ts">
import type { StatsOverview } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'نظرة عامة — DentLegal' })

const api = useApi()
const toast = useToast()
const range = ref<'7d' | '30d'>('30d')
const stats = ref<StatsOverview | null>(null)
const rangeOptions = [{ label: '7 أيام', value: '7d' }, { label: '30 يومًا', value: '30d' }]

async function load() {
  try {
    stats.value = await api.admin.stats(range.value)
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}
watch(range, load, { immediate: true })

const activityChart = computed(() => {
  const s = stats.value
  if (!s) return null
  return {
    labels: s.daily.map((d) => formatGregorian(d.day, true).replace(/ \d{4}$/, '')),
    datasets: [
      { type: 'bar', label: 'أسئلة المستشار', data: s.daily.map((d) => d.questions), backgroundColor: '#16a08a', borderRadius: 6, maxBarThickness: 18 },
      { type: 'bar', label: 'قضايا جديدة', data: s.daily.map((d) => d.cases), backgroundColor: '#b8924f', borderRadius: 6, maxBarThickness: 18 },
    ],
  }
})

const usefulChart = computed(() => {
  const s = stats.value
  if (!s) return null
  return {
    labels: ['إجابات المستشار', 'تحليلات القضايا', 'مسودات الردود'],
    datasets: [{ label: 'مفيدة', data: [s.usefulRate.consult, s.usefulRate.analysis, s.usefulRate.draft].map((v) => Math.round(v * 100)), backgroundColor: ['#12806f', '#16a08a', '#7fd1c2'], borderRadius: 8, maxBarThickness: 46 }],
  }
})

const font = { family: 'IBM Plex Sans Arabic', size: 12 }
const activityOptions = {
  maintainAspectRatio: false,
  locale: 'ar',
  plugins: { legend: { position: 'bottom', rtl: true, labels: { font } }, tooltip: { rtl: true } },
  scales: { x: { reverse: true, grid: { display: false }, ticks: { font, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } }, y: { beginAtZero: true, position: 'right', ticks: { font, precision: 0 } } },
}
const usefulOptions = {
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: { legend: { display: false }, tooltip: { rtl: true, callbacks: { label: (c: { raw: unknown }) => `${String(c.raw)}% مفيدة` } } },
  scales: { x: { min: 0, max: 100, reverse: true, ticks: { font, callback: (v: number | string) => `${v}%` } }, y: { position: 'right', ticks: { font } } },
}
</script>

<template>
  <div>
    <PanelTopbar title="نظرة عامة على التجربة" crumb="لوحة الإدارة">
      <template #actions>
        <MockDataBadge />
        <SelectButton v-model="range" :options="rangeOptions" option-label="label" option-value="value" :allow-empty="false" size="small" aria-label="الفترة" />
      </template>
    </PanelTopbar>

    <div v-if="!stats" class="flex justify-center py-16"><ProgressSpinner style="width: 40px; height: 40px" /></div>
    <div v-else class="flex flex-col gap-4 p-6">
      <div class="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard icon="users" :value="stats.activeDentists" label="أطباء نشطون" :detail="`من ${stats.invitedDentists} مدعوين للتجربة`" />
        <KpiCard icon="chats" :value="stats.questionsAnswered" label="أسئلة أُجيب عنها" :detail="`نسبة الاعتذار ${percent(stats.refusalRate)}`" />
        <KpiCard icon="briefcase" :value="stats.casesCreated" label="قضايا أُنشئت" :detail="`${stats.draftsApproved} ردود معتمدة`" />
        <KpiCard icon="checkCircle" :value="percent(stats.usefulRate.overall)" label="تقييم «مفيدة»" detail="الهدف 80% فأكثر" />
        <KpiCard icon="clock" :value="`${stats.uploadToDraftMinutes.p90} د`" label="من الرفع حتى أول مسودة (p90)" :detail="`الوسيط ${stats.uploadToDraftMinutes.p50} د · الهدف أقل من 10 د`" />
        <KpiCard icon="badge" :value="percent(stats.citationValidity, 1)" label="صحة الاستشهادات (تحقق آلي)" detail="دقة المحامي تُقاس بالعينة" />
        <KpiCard icon="file" :value="stats.draftsGenerated" label="مسودات أُنشئت" />
        <KpiCard icon="zap" :value="`$${stats.aiCostUsd.toFixed(1)}`" label="تكلفة الذكاء الاصطناعي" :detail="range === '7d' ? 'آخر 7 أيام' : 'آخر 30 يومًا'" />
      </div>

      <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <section class="card">
          <div class="card-head"><h3>النشاط اليومي</h3></div>
          <div class="h-72"><Chart v-if="activityChart" type="bar" :data="activityChart" :options="activityOptions" class="h-full" /></div>
        </section>
        <section class="card">
          <div class="card-head"><h3>نسبة «مفيدة» حسب النوع</h3></div>
          <div class="h-72"><Chart v-if="usefulChart" type="bar" :data="usefulChart" :options="usefulOptions" class="h-full" /></div>
        </section>
      </div>
    </div>
  </div>
</template>
