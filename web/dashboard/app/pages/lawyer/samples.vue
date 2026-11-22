<script setup lang="ts">
import type { CitationSample } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'عينة الاستشهادات — DentLegal' })

const api = useApi()
const toast = useToast()
const samples = ref<CitationSample[]>([])
const loading = ref(true)
const notes = reactive<Record<string, string>>({})

const ROUTE_LABELS: Record<CitationSample['route'], string> = { consult: 'إجابة المستشار', analysis: 'تحليل قضية', draft: 'مسودة رد' }

const reviewed = computed(() => samples.value.filter((s) => s.verdict))
const accuracy = computed(() => (reviewed.value.length ? reviewed.value.filter((s) => s.verdict === 'correct').length / reviewed.value.length : null))

onMounted(async () => {
  try {
    samples.value = await api.lawyer.listCitationSamples()
    for (const s of samples.value) notes[s.id] = s.note
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    loading.value = false
  }
})

async function judge(sample: CitationSample, verdict: 'correct' | 'incorrect') {
  try {
    const updated = await api.lawyer.setVerdict(sample.id, verdict, notes[sample.id] ?? '')
    Object.assign(sample, updated)
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}
</script>

<template>
  <div>
    <PanelTopbar title="مراجعة عينة الاستشهادات" crumb="بوابة المحامي"><template #actions><MockDataBadge /></template></PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="grid gap-3.5 md:grid-cols-3">
        <KpiCard icon="listChecks" :value="`${reviewed.length} / ${samples.length}`" label="تمت مراجعتها" />
        <KpiCard icon="badge" :value="accuracy === null ? '—' : percent(accuracy)" label="دقة الاستشهادات في العينة" detail="الهدف للتجربة: 95% فأكثر" />
        <KpiCard icon="info" value="عشوائية" label="عينة من إجابات وتحليلات ومسودات التجربة" />
      </div>

      <div class="alert teal">
        <AppIcon name="info" />
        <div>لكل استشهاد: هل يدعم نص المادة الادعاء فعلًا؟ نتيجتك تدخل في مؤشر «دقة الاستشهادات» في تقرير التجربة.</div>
      </div>

      <div v-if="loading" class="flex justify-center py-10"><ProgressSpinner style="width: 36px; height: 36px" /></div>
      <article v-for="(s, i) in samples" :key="s.id" class="card flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2 text-[12.5px]">
            <b>#{{ i + 1 }}</b><span class="badge b-gray">{{ ROUTE_LABELS[s.route] }}</span><span class="mono muted">{{ s.caseCode }}</span>
          </div>
          <span v-if="s.verdict" class="badge" :class="s.verdict === 'correct' ? 'b-green' : 'b-red'">{{ s.verdict === 'correct' ? 'صحيح' : 'غير صحيح' }}</span>
        </div>
        <div class="grid gap-3 lg:grid-cols-2">
          <div>
            <div class="mb-1 text-xs font-bold muted">الادعاء</div>
            <p class="m-0 font-semibold">{{ s.claim }}</p>
            <div class="mt-2 text-xs font-bold muted">الاقتباس المستشهد به</div>
            <p class="m-0 text-[13px]">«{{ s.quote }}»</p>
          </div>
          <div class="rounded-xl p-3 text-[13px] leading-7" style="background: var(--bg)">
            <div class="mb-1 text-xs font-bold" style="color: var(--teal-700)">{{ s.sourceLabel }}</div>
            {{ s.sourceText }}
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <InputText v-model="notes[s.id]" placeholder="ملاحظة (اختياري)" class="min-w-0 grow text-[13px]" />
          <Button size="small" :outlined="s.verdict !== 'correct'" severity="success" @click="judge(s, 'correct')"><AppIcon name="check" :size="15" /> صحيح</Button>
          <Button size="small" :outlined="s.verdict !== 'incorrect'" severity="danger" @click="judge(s, 'incorrect')"><AppIcon name="x" :size="15" /> غير صحيح</Button>
        </div>
      </article>
    </div>
  </div>
</template>
