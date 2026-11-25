<script setup lang="ts">
import type { AiRunRow, AiRunSummary } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'سجل تشغيل الذكاء الاصطناعي — DentLegal' })

// Read-only view of ai_runs (S7.4): one row per Claude call, metadata only.
const api = useApi()
const toast = useToast()
const rows = ref<AiRunRow[]>([])
const summary = ref<AiRunSummary | null>(null)
const loading = ref(true)
const runType = ref<string>('all')
const problems = ref(false)
const detail = ref<AiRunRow | null>(null)

const RUN_TYPES: Record<string, string> = { consult: 'استشارة', case_analysis: 'تحليل قضية', case_draft: 'مسودة رد' }
const STATUS: Record<string, { label: string; cls: string }> = {
  answered: { label: 'أُجيب', cls: 'b-green' },
  ok: { label: 'تم', cls: 'b-green' },
  needs_attention: { label: 'يحتاج انتباهًا', cls: 'b-amber' },
  refused: { label: 'اعتذار', cls: 'b-amber' },
  failed: { label: 'فشل', cls: 'b-red' },
  superseded: { label: 'استُبدل', cls: 'b-gray' },
}
const typeLabel = (t: string) => RUN_TYPES[t] ?? t
const statusOf = (s: string) => STATUS[s] ?? { label: s, cls: 'b-gray' }
const typeOptions = computed(() => [{ label: 'الكل', value: 'all' }, ...(summary.value?.runTypes ?? []).map((t) => ({ label: typeLabel(t), value: t }))])
const seconds = (ms: number | null) => (ms === null ? '—' : `${(ms / 1000).toFixed(1)} ث`)
const thousands = (n: number) => n.toLocaleString('en-US')
const pct = (v: number | null, digits = 0) => (v === null ? '—' : `${(v * 100).toFixed(digits)}%`)

async function load() {
  loading.value = true
  try {
    const res = await api.admin.listAiRuns({ runType: runType.value === 'all' ? undefined : runType.value, problems: problems.value })
    rows.value = res.rows
    summary.value = res.summary
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    loading.value = false
  }
}
onMounted(load)
watch([runType, problems], load)

function closeDetail(visible: boolean) {
  if (!visible) detail.value = null
}

function exportCsv() {
  downloadCsv('dentlegal-ai-runs.csv',
    ['التاريخ', 'النوع', 'الحالة', 'القضية', 'النموذج', 'بديل', 'رموز الإدخال', 'رموز الإخراج', 'التكلفة $', 'الزمن ms', 'صحة الاستشهادات', 'الخطأ', 'معرّف الطلب'],
    rows.value.map((r) => [r.createdAt, typeLabel(r.runType), statusOf(r.status).label, r.caseCode ?? '', r.modelServed ?? '', r.fallbackUsed ? 'نعم' : '',
      r.inputTokens, r.outputTokens, r.costUsd, r.latencyMs ?? '', r.citationValidity ?? '', r.errorCode ?? r.refusalCategory ?? '', r.requestId ?? '']))
}
</script>

<template>
  <div>
    <PanelTopbar title="سجل تشغيل الذكاء الاصطناعي" crumb="لوحة الإدارة / الجودة">
      <template #actions>
        <MockDataBadge />
        <Button size="small" severity="secondary" outlined :disabled="!rows.length" @click="exportCsv"><AppIcon name="download" :size="16" /> تصدير CSV</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div v-if="summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard icon="sparkles" :value="summary.runs" label="عمليات تشغيل" :detail="`${summary.errors} أخطاء · ${summary.fallbacks} نموذج بديل`" />
        <KpiCard icon="zap" :value="`$${summary.costUsd.toFixed(2)}`" label="التكلفة" :detail="`${thousands(summary.inputTokens)} إدخال · ${thousands(summary.outputTokens)} إخراج`" />
        <KpiCard icon="clock" :value="seconds(summary.latencyP90Ms)" label="زمن الاستجابة (p90)" :detail="`الوسيط ${seconds(summary.latencyP50Ms)}`" />
        <KpiCard icon="badge" :value="pct(summary.citationValidity, 1)" label="صحة الاستشهادات (تحقق آلي)" detail="الهدف 98% فأكثر" />
      </div>

      <div class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-line p-3">
          <SelectButton v-model="runType" :options="typeOptions" option-label="label" option-value="value" :allow-empty="false" size="small" aria-label="نوع التشغيل" />
          <label class="flex items-center gap-2 text-sm"><Checkbox v-model="problems" binary input-id="problems" /> المشكلات فقط</label>
          <span v-if="summary && summary.shown < summary.runs" class="text-xs muted">يُعرض أحدث {{ summary.shown }} من {{ summary.runs }}</span>
        </div>
        <DataTable v-model:selection="detail" :value="rows" :loading="loading" data-key="id" paginator :rows="15" selection-mode="single" :meta-key-selection="false">
          <template #empty><div class="py-10 text-center muted">لا توجد عمليات تشغيل.</div></template>
          <Column header="التاريخ"><template #body="{ data }"><DualDate :value="data.createdAt" with-time /></template></Column>
          <Column header="النوع"><template #body="{ data }">{{ typeLabel(data.runType) }}</template></Column>
          <Column header="الحالة"><template #body="{ data }"><span class="badge" :class="statusOf(data.status).cls">{{ statusOf(data.status).label }}</span></template></Column>
          <Column header="القضية"><template #body="{ data }"><span v-if="data.caseCode" class="mono">{{ data.caseCode }}</span><span v-else class="muted">—</span></template></Column>
          <Column header="النموذج">
            <template #body="{ data }"><span class="mono text-xs">{{ data.modelServed ?? '—' }}</span><span v-if="data.fallbackUsed" class="badge b-amber ms-2">بديل</span></template>
          </Column>
          <Column header="الرموز"><template #body="{ data }"><span class="mono text-xs">{{ thousands(data.inputTokens) }} / {{ thousands(data.outputTokens) }}</span></template></Column>
          <Column header="التكلفة"><template #body="{ data }"><span class="mono">${{ data.costUsd.toFixed(4) }}</span></template></Column>
          <Column header="الزمن"><template #body="{ data }">{{ seconds(data.latencyMs) }}</template></Column>
          <Column header="الاستشهادات"><template #body="{ data }">{{ pct(data.citationValidity) }}</template></Column>
        </DataTable>
      </div>
      <p class="text-xs muted">لا يُحفظ نص السؤال أو الإجابة أو المستندات في هذا السجل؛ البيانات الوصفية فقط، وتُحذف بعد 12 شهرًا.</p>
    </div>

    <Drawer :visible="!!detail" position="left" header="تفاصيل التشغيل" class="!w-full sm:!w-[28rem]" @update:visible="closeDetail">
      <dl v-if="detail" class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm [&_dd]:text-right">
        <dt class="muted">المعرّف</dt><dd class="mono">{{ detail.id }}</dd>
        <dt class="muted">النوع / الحالة</dt><dd>{{ typeLabel(detail.runType) }} · {{ statusOf(detail.status).label }}</dd>
        <dt class="muted">إصدار التعليمات</dt><dd class="mono">{{ detail.promptVersion ?? '—' }}</dd>
        <dt class="muted">النموذج المطلوب / المستخدم</dt><dd class="mono">{{ detail.modelRequested ?? '—' }} / {{ detail.modelServed ?? '—' }}</dd>
        <dt class="muted">الجهد / الموقع</dt><dd class="mono">{{ detail.effort ?? '—' }} · {{ detail.inferenceGeo ?? '—' }}</dd>
        <dt class="muted">سبب التوقف</dt><dd class="mono">{{ detail.stopReason ?? '—' }}</dd>
        <dt class="muted">الاعتذار / الخطأ</dt><dd class="mono">{{ detail.refusalCategory ?? '—' }} / {{ detail.errorCode ?? '—' }}</dd>
        <dt class="muted">رموز من الذاكرة المؤقتة</dt><dd class="mono">{{ thousands(detail.cacheReadTokens) }}</dd>
        <dt class="muted">المصادر المسترجعة</dt><dd>{{ detail.sources }}</dd>
        <dt class="muted">إصلاح الاستشهادات</dt><dd>{{ detail.repaired ? 'نعم' : 'لا' }}</dd>
        <dt class="muted">معرّف الطلب</dt><dd class="mono break-all">{{ detail.requestId ?? '—' }}</dd>
      </dl>
    </Drawer>
  </div>
</template>
