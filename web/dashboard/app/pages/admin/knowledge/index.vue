<script setup lang="ts">
import type { ExtractionMethod, KnowledgeSource, SourceVersion } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'قاعدة المعرفة — DentLegal' })

const api = useApi()
const toast = useToast()
const confirm = useConfirm()

const sources = ref<KnowledgeSource[]>([])
const loading = ref(true)
const uploadOpen = ref(false)
const uploading = ref(false)
const form = reactive<{ sourceId: string | null; newSourceTitle: string; edition: string; effectiveFrom: Date | null; fileName: string; extractionMethod: ExtractionMethod }>({
  sourceId: null, newSourceTitle: '', edition: '', effectiveFrom: null, fileName: '', extractionMethod: 'auto',
})
let poll: ReturnType<typeof setInterval> | null = null

const extractionOptions = (Object.keys(EXTRACTION_LABELS) as ExtractionMethod[]).map((value) => ({ value, label: EXTRACTION_LABELS[value] }))
const sourceOptions = computed(() => [...sources.value.map((s) => ({ value: s.id, label: s.title })), { value: '__new', label: '+ مصدر جديد' }])

const active = computed(() => sources.value.flatMap((s) => s.versions.filter((v) => v.status === 'active')))
const kpis = computed(() => ({
  sources: active.value.length,
  chunks: active.value.reduce((sum, v) => sum + v.chunkCount, 0),
  archived: sources.value.flatMap((s) => s.versions).filter((v) => v.status === 'archived').length,
  pending: sources.value.flatMap((s) => s.versions).filter((v) => v.status === 'processing' || v.status === 'review').length,
}))

async function load() {
  try {
    sources.value = await api.admin.listSources()
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    loading.value = false
  }
}

// Poll while something is being indexed so the status updates without a reload.
watch(() => sources.value.some((s) => s.versions.some((v) => v.status === 'processing')), (processing) => {
  if (processing && !poll) poll = setInterval(load, 2500)
  if (!processing && poll) { clearInterval(poll); poll = null }
})
onMounted(load)
onBeforeUnmount(() => { if (poll) clearInterval(poll) })

function onFile(event: { files: File[] }) {
  form.fileName = event.files[0]?.name ?? ''
}

const canUpload = computed(() => !!form.fileName && !!form.edition.trim() && (form.sourceId !== '__new' ? !!form.sourceId : !!form.newSourceTitle.trim()))

async function upload() {
  uploading.value = true
  try {
    await api.admin.uploadVersion({
      sourceId: form.sourceId === '__new' ? null : form.sourceId, newSourceTitle: form.newSourceTitle.trim(), edition: form.edition.trim(),
      effectiveFrom: form.effectiveFrom ? form.effectiveFrom.toISOString().slice(0, 10) : null, fileName: form.fileName, extractionMethod: form.extractionMethod,
    })
    uploadOpen.value = false
    Object.assign(form, { sourceId: null, newSourceTitle: '', edition: '', effectiveFrom: null, fileName: '', extractionMethod: 'auto' })
    toast.add({ severity: 'info', summary: 'بدأت الفهرسة', detail: 'سيظهر الإصدار «بانتظار النشر» عند اكتمالها لتراجعه قبل النشر.', life: 4000 })
    await load()
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    uploading.value = false
  }
}

function publish(source: KnowledgeSource, version: SourceVersion) {
  confirm.require({
    header: 'نشر الإصدار',
    message: `سيصبح «${version.edition}» الإصدار الساري من «${source.title}»، ويُؤرشف الإصدار السابق. الردود القديمة تحتفظ بمصادرها.`,
    acceptProps: { label: 'نشر' },
    rejectProps: { label: 'إلغاء', severity: 'secondary', outlined: true },
    accept: async () => {
      try { await api.admin.publishVersion(version.id); await load(); toast.add({ severity: 'success', summary: 'نُشر الإصدار', life: 2500 }) } catch (e) { toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 }) }
    },
  })
}

async function reindex(version: SourceVersion) {
  try { await api.admin.reindexVersion(version.id); await load() } catch (e) { toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 }) }
}

async function archive(version: SourceVersion) {
  try { await api.admin.archiveVersion(version.id); await load() } catch (e) { toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 }) }
}
</script>

<template>
  <div>
    <PanelTopbar title="قاعدة المعرفة" crumb="لوحة الإدارة / المحتوى">
      <template #actions>
        <MockDataBadge />
        <Button size="small" @click="uploadOpen = true"><AppIcon name="upload" :size="16" /> رفع إصدار جديد</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard icon="book" :value="kpis.sources" label="مصادر سارية" />
        <KpiCard icon="layers" :value="kpis.chunks" label="مقطعًا مفهرسًا (حسب المادة والفقرة)" />
        <KpiCard icon="history" :value="kpis.archived" label="إصدار مؤرشف محفوظ" />
        <KpiCard icon="refresh" :value="kpis.pending" label="بانتظار الفهرسة أو النشر" />
      </div>

      <div v-if="loading" class="flex justify-center py-10"><ProgressSpinner style="width: 36px; height: 36px" /></div>
      <section v-for="source in sources" :key="source.id" class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
          <AppIcon name="book" /><h3 class="m-0 text-[15px] font-bold">{{ source.title }}</h3>
          <span class="mono muted">{{ source.code }}</span>
        </div>
        <DataTable :value="source.versions" data-key="id">
          <Column header="الإصدار"><template #body="{ data }"><bdi class="font-bold">{{ data.versionLabel }}</bdi> · <bdi>{{ data.edition }}</bdi></template></Column>
          <Column header="الحالة"><template #body="{ data }"><span class="badge" :class="VERSION_STATUS[data.status as SourceVersion['status']].cls">{{ VERSION_STATUS[data.status as SourceVersion['status']].label }}</span></template></Column>
          <Column header="المقاطع"><template #body="{ data }">{{ data.chunkCount || '—' }}</template></Column>
          <Column header="طريقة الاستخراج"><template #body="{ data }">{{ EXTRACTION_LABELS[data.extractionMethod as ExtractionMethod] }}</template></Column>
          <Column header="الملف"><template #body="{ data }"><bdi class="text-xs muted">{{ data.fileName }}</bdi></template></Column>
          <Column header="آخر تحديث"><template #body="{ data }"><DualDate :value="data.updatedAt" /></template></Column>
          <Column header="" class="w-px whitespace-nowrap">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button size="small" text label="معاينة المقاطع" :disabled="data.status === 'processing' || !data.chunkCount" @click="navigateTo(`/admin/knowledge/${data.id}`)" />
                <Button v-if="data.status === 'review'" size="small" label="نشر الإصدار" @click="publish(source, data)" />
                <Button v-if="data.status === 'active' || data.status === 'review'" size="small" text severity="secondary" label="إعادة الفهرسة" @click="reindex(data)" />
                <Button v-if="data.status === 'review'" size="small" text severity="danger" label="أرشفة" @click="archive(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </section>

      <div class="alert teal"><AppIcon name="info" /><div>يستخدم المحرك الإصدار الساري فقط. كل إصدار جديد يُفهرس ثم ينتظر مراجعتك قبل النشر، وتبقى الإصدارات السابقة محفوظة للمرجعية.</div></div>
    </div>

    <Dialog v-model:visible="uploadOpen" modal header="رفع إصدار جديد" :style="{ width: '520px' }">
      <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1.5"><label class="text-[13px] font-semibold" for="k-src">المصدر</label>
          <Select v-model="form.sourceId" input-id="k-src" :options="sourceOptions" option-label="label" option-value="value" placeholder="اختر المصدر" /></div>
        <div v-if="form.sourceId === '__new'" class="flex flex-col gap-1.5"><label class="text-[13px] font-semibold" for="k-new">اسم المصدر الجديد</label><InputText id="k-new" v-model="form.newSourceTitle" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5"><label class="text-[13px] font-semibold" for="k-ed">الإصدار</label><InputText id="k-ed" v-model="form.edition" placeholder="مثال: الإصدار الرابع 1447هـ" /></div>
          <div class="flex flex-col gap-1.5"><label class="text-[13px] font-semibold" for="k-date">تاريخ السريان</label><DatePicker v-model="form.effectiveFrom" input-id="k-date" date-format="yy-mm-dd" show-icon /></div>
        </div>
        <div class="flex flex-col gap-1.5"><label class="text-[13px] font-semibold" for="k-method">طريقة الاستخراج</label>
          <Select v-model="form.extractionMethod" input-id="k-method" :options="extractionOptions" option-label="label" option-value="value" />
          <small class="muted">«تلقائي» يفحص طبقة النص ويحوّل للتعرّف الضوئي إن كانت تالفة.</small></div>
        <div class="flex flex-col gap-1.5"><span class="text-[13px] font-semibold">الملف (PDF أو DOCX)</span>
          <FileUpload mode="basic" accept=".pdf,.docx,.txt" :max-file-size="50_000_000" choose-label="اختيار ملف" :auto="false" custom-upload @select="onFile" />
          <small v-if="form.fileName" class="muted">{{ form.fileName }}</small></div>
      </div>
      <template #footer>
        <Button severity="secondary" text label="إلغاء" @click="uploadOpen = false" />
        <Button label="رفع وبدء الفهرسة" :loading="uploading" :disabled="!canUpload" @click="upload" />
      </template>
    </Dialog>
  </div>
</template>
