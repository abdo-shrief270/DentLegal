<script setup lang="ts">
import type { CaseDocumentRef, RetrievedSource } from '~/types/api'

definePageMeta({ layout: 'panel' })

const route = useRoute()
const store = useReviewStore()
const api = useApi()
const toast = useToast()

const loadError = ref('')
const saveOpen = ref(false)
const saveNote = ref('')
const approveOpen = ref(false)
const approveConfirmed = ref(false)
const sourceOpen = ref<RetrievedSource | null>(null)
const documentOpen = ref<CaseDocumentRef | null>(null)

const detail = computed(() => store.detail)
useHead(() => ({ title: detail.value ? `${detail.value.caseCode} — مراجعة` : 'مراجعة — DentLegal' }))

async function load() {
  loadError.value = ''
  try {
    await store.load(String(route.params.id))
  } catch (e) {
    loadError.value = errorMessage(e)
  }
}
onMounted(load)

function onOpenDocument(doc: CaseDocumentRef) {
  documentOpen.value = doc
}

function onOpenSource(source: RetrievedSource) {
  sourceOpen.value = source
}

function openRef(ref: string) {
  if (ref.startsWith('S')) sourceOpen.value = detail.value?.sources.find((s) => s.ref === ref) ?? null
  else documentOpen.value = detail.value?.documents.find((d) => d.ref === ref) ?? null
}

async function save() {
  try {
    await store.saveVersion(saveNote.value.trim())
    saveOpen.value = false
    saveNote.value = ''
    toast.add({ severity: 'success', summary: 'حُفظ إصدار جديد', detail: `الإصدار ${store.latest?.no}`, life: 3000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}

async function approve() {
  try {
    await store.approve()
    approveOpen.value = false
    approveConfirmed.value = false
    toast.add({ severity: 'success', summary: 'تم اعتماد الرد', detail: 'أُرسل إشعار «رد معتمد جاهز» إلى الطبيب.', life: 4000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}

async function addNote(text: string) {
  try {
    await store.addNote(text)
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}

async function viewOriginal(doc: CaseDocumentRef) {
  try {
    const link = await api.lawyer.documentViewUrl(doc.documentId)
    toast.add({ severity: 'info', summary: 'رابط مؤقت للمستند الأصلي', detail: `صالح حتى ${formatTime(link.expiresAt)} · يُسجَّل الاطلاع في سجل التدقيق`, life: 4000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}

// Unsaved edits: ask before leaving the page or closing the tab.
onBeforeRouteLeave(() => (store.dirty ? window.confirm('لديك تعديلات غير محفوظة. هل تريد المغادرة دون حفظ؟') : true))
function beforeUnload(event: BeforeUnloadEvent) {
  if (store.dirty) event.preventDefault()
}
onMounted(() => window.addEventListener('beforeunload', beforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))
</script>

<template>
  <div>
    <PanelTopbar :title="detail?.title ?? 'مراجعة'" :crumb="detail ? `قائمة المراجعة / ${detail.caseCode}` : 'قائمة المراجعة'">
      <template #actions>
        <MockDataBadge />
        <span v-if="detail" class="badge" :class="detail.status === 'approved' ? 'b-gold' : 'b-blue'">
          {{ detail.status === 'approved' ? 'معتمد' : 'قيد المراجعة' }}
        </span>
        <Button size="small" severity="secondary" outlined @click="navigateTo('/lawyer')"><AppIcon name="chevR" :size="15" /> القائمة</Button>
      </template>
    </PanelTopbar>

    <div class="p-6">
      <Message v-if="loadError" severity="error">{{ loadError }}</Message>
      <div v-else-if="!detail" class="flex justify-center py-16"><ProgressSpinner style="width: 40px; height: 40px" /></div>

      <template v-else>
        <div class="grid items-start gap-4 xl:grid-cols-[290px_minmax(0,1fr)_320px]">
          <div class="xl:sticky xl:top-20"><CasePane :detail="detail" @open-document="onOpenDocument" /></div>
          <DraftEditor @open-ref="openRef" />
          <div class="xl:sticky xl:top-20">
            <SourcesPane :detail="detail" :readonly="store.isApproved" @open-source="onOpenSource" @add-note="addNote" />
          </div>
        </div>

        <!-- action bar -->
        <div class="sticky bottom-0 z-10 -mx-6 mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line bg-white px-6 py-3">
          <span v-if="store.isApproved" class="flex items-center gap-2 text-[13px] muted">
            <AppIcon name="lock" :size="15" /> الإصدار المعتمد مقفل. أي تعديل لاحق يتطلب اعتمادًا جديدًا (المشروع الثاني).
          </span>
          <span v-else-if="store.editing" class="text-[13px] muted">{{ store.dirty ? 'توجد تعديلات غير محفوظة.' : 'لم تُجرَ تعديلات بعد.' }}</span>
          <span v-else class="text-[13px] muted">راجع المسودة ومصادرها، ثم احفظ تعديلاتك كإصدار أو اعتمد الرد.</span>
          <div v-if="!store.isApproved" class="flex gap-2">
            <Button v-if="store.editing" severity="secondary" :disabled="!store.dirty" @click="saveOpen = true">
              <AppIcon name="copy" :size="16" /> حفظ كإصدار
            </Button>
            <Button :disabled="store.editing" style="background: var(--gold-500); border-color: var(--gold-500)" @click="approveOpen = true">
              <AppIcon name="stamp" :size="16" /> اعتماد الرد
            </Button>
          </div>
        </div>
      </template>
    </div>

    <!-- save version -->
    <Dialog v-model:visible="saveOpen" modal header="حفظ كإصدار جديد" :style="{ width: '440px' }">
      <div class="flex flex-col gap-2">
        <p class="m-0 text-[13px] muted">يُحفظ إصدار جديد ولا يُعدَّل أي إصدار سابق. يمكنك إضافة ملاحظة قصيرة عن التعديل.</p>
        <InputText v-model="saveNote" placeholder="مثال: إضافة ملاحظات المتابعة إلى الوقائع" />
      </div>
      <template #footer>
        <Button severity="secondary" text label="إلغاء" @click="saveOpen = false" />
        <Button label="حفظ" :loading="store.saving" @click="save" />
      </template>
    </Dialog>

    <!-- approve -->
    <Dialog v-model:visible="approveOpen" modal header="اعتماد الرد" :style="{ width: '480px' }">
      <div class="flex flex-col gap-3 text-[13.5px]">
        <p class="m-0">سيُقفل <b>الإصدار {{ store.latest?.no }}</b> ويصل الرد المعتمد إلى الطبيب مع إشعار «رد معتمد جاهز».</p>
        <div v-if="store.flaggedCount" class="alert amber">
          <AppIcon name="alert" /><div>توجد {{ unsupportedParagraphs(store.flaggedCount) }}. تأكد من معالجتها أو من صحتها قبل الاعتماد.</div>
        </div>
        <label class="flex items-start gap-2">
          <Checkbox v-model="approveConfirmed" binary input-id="confirm-approve" />
          <span>راجعت المسودة ومصادرها وأعتمدها.</span>
        </label>
      </div>
      <template #footer>
        <Button severity="secondary" text label="إلغاء" @click="approveOpen = false" />
        <Button label="اعتماد وإرسال للطبيب" :disabled="!approveConfirmed" :loading="store.saving"
          style="background: var(--gold-500); border-color: var(--gold-500)" @click="approve" />
      </template>
    </Dialog>

    <!-- source text -->
    <Dialog :visible="!!sourceOpen" modal :header="sourceOpen ? `${sourceOpen.label} — ${sourceOpen.title}` : ''" :style="{ width: '560px' }"
      @update:visible="(v: boolean) => !v && (sourceOpen = null)">
      <div v-if="sourceOpen" class="flex flex-col gap-3">
        <div class="flex flex-wrap gap-2"><span class="badge b-teal">{{ sourceOpen.law }}</span><span class="badge b-gray">صلة {{ Math.round(sourceOpen.score * 100) }}%</span></div>
        <p class="m-0 leading-8">{{ sourceOpen.text }}</p>
      </div>
    </Dialog>

    <!-- masked document text -->
    <Dialog :visible="!!documentOpen" modal :header="documentOpen?.name ?? ''" :style="{ width: '600px' }"
      @update:visible="(v: boolean) => !v && (documentOpen = null)">
      <div v-if="documentOpen" class="flex flex-col gap-3">
        <div class="alert blue"><AppIcon name="shield" /><div>نص مستخرج آليًا (OCR) بعد إخفاء البيانات التعريفية للمريض.</div></div>
        <div v-if="documentOpen.lowQuality" class="alert amber"><AppIcon name="alert" /><div>جودة المسح منخفضة — راجع الأصل عند الحاجة.</div></div>
        <p class="m-0 rounded-xl p-3 text-[13px] leading-8" style="background: var(--bg)"><MaskedText :text="documentOpen.maskedText" /></p>
      </div>
      <template #footer>
        <Button v-if="documentOpen" severity="secondary" outlined @click="viewOriginal(documentOpen)"><AppIcon name="eye" :size="15" /> عرض الأصل</Button>
      </template>
    </Dialog>
  </div>
</template>
