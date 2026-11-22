<script setup lang="ts">
import type { CitationRef, DraftParagraph } from '~/types/api'

const emit = defineEmits<{ openRef: [ref: string] }>()
const store = useReviewStore()

const detail = computed(() => store.detail!)
const versionOptions = computed(() => detail.value.versions.map((v) => ({
  value: v.no,
  label: `الإصدار ${v.no} · ${v.authorType === 'ai' ? 'مسودة آلية' : v.authorName}`,
})))

const sourceByRef = computed(() => new Map(detail.value.sources.map((s) => [s.ref, s])))
const docByRef = computed(() => new Map(detail.value.documents.map((d) => [d.ref, d])))

function refLabel(ref: string): string {
  return ref.startsWith('S') ? (sourceByRef.value.get(ref)?.label ?? ref) : (docByRef.value.get(ref)?.name ?? ref)
}

function citeClass(c: CitationRef) {
  return !c.verified ? 'bad' : c.ref.startsWith('D') ? 'doc' : ''
}

function addOptions(paragraph: DraftParagraph) {
  const used = new Set(paragraph.citations.map((c) => c.ref))
  return [
    ...detail.value.sources.filter((s) => !used.has(s.ref)).map((s) => ({ value: s.ref, label: `${s.ref} · ${s.label} — ${s.title}` })),
    ...detail.value.documents.filter((d) => !used.has(d.ref)).map((d) => ({ value: d.ref, label: `${d.ref} · ${d.name}` })),
  ]
}

function onAdd(paragraph: DraftParagraph, ref: string | null) {
  if (ref) store.addCitation(paragraph, ref)
}
</script>

<template>
  <section class="pane flex flex-col" aria-label="مسودة الرد">
    <div class="pane-h flex-wrap">
      <Select
        :model-value="store.viewedVersionNo" :options="versionOptions" option-label="label" option-value="value"
        :disabled="store.editing" size="small" aria-label="الإصدار" @update:model-value="(v: number) => (store.viewedVersionNo = v)"
      />
      <div class="flex flex-wrap items-center gap-2">
        <span v-if="store.flaggedCount" class="badge b-amber"><AppIcon name="alert" :size="13" />{{ store.flaggedCount }} غير مسند</span>
        <template v-if="!store.isApproved">
          <Button v-if="!store.editing" size="small" severity="secondary" outlined @click="store.startEditing()">
            <AppIcon name="pen" :size="15" /> تحرير المسودة
          </Button>
          <Button v-else size="small" severity="secondary" text label="إلغاء التعديلات" @click="store.cancelEditing()" />
        </template>
      </div>
    </div>

    <div class="flex flex-wrap gap-3 border-b border-line px-4 py-2 text-[11.5px] muted">
      <span class="flex items-center gap-1.5"><span class="cite !m-0 !px-1.5">م 27</span>مادة نظامية</span>
      <span class="flex items-center gap-1.5"><span class="cite doc !m-0 !px-1.5">مستند</span>مستند من القضية</span>
      <span class="flex items-center gap-1.5"><span class="cite bad !m-0 !px-1.5">غير مطابق</span>اقتباس لم يتحقق</span>
      <span class="flex items-center gap-1.5"><span class="paper-flag inline-block h-3 w-6 rounded" style="background:#fff6dc;border-bottom:2px dashed #d39b14" />فقرة غير مسندة</span>
    </div>

    <div class="pane-b grow" style="background: var(--bg)">
      <div v-if="store.isApproved" class="seal mb-3">
        <span class="ring"><AppIcon name="stamp" :size="22" /></span>
        <div>
          <div class="font-bold">تم اعتماد الرد</div>
          <div class="text-xs">{{ detail.approvedBy }} · <DualDate v-if="detail.approvedAt" :value="detail.approvedAt" with-time inline /></div>
        </div>
      </div>

      <article v-if="store.content" class="paper">
        <div class="meta">
          {{ store.content.title }} · الإصدار {{ store.editing ? `${(store.latest?.no ?? 0) + 1} (قيد التحرير)` : store.viewed?.no }}
          · {{ store.viewed?.authorType === 'ai' && !store.editing ? 'أنشأها الذكاء الاصطناعي — غير معتمدة' : 'تعديل المحامي' }}
        </div>
        <p class="m-0 font-bold">{{ store.content.addressee }}</p>
        <p class="m-0">{{ store.content.salutation }}</p>

        <!-- intro -->
        <div class="my-2" :class="{ flagged: store.isUnsupported(store.content.intro) }">
          <Textarea v-if="store.editing" v-model="store.content.intro.text" auto-resize rows="2" class="w-full text-[14px]" aria-label="المقدمة" />
          <p v-else class="m-0"><BidiText :text="store.content.intro.text" /></p>
        </div>

        <section v-for="(section, si) in store.content.sections" :key="section.kind">
          <h4>{{ section.heading }}</h4>
          <ol class="m-0 flex list-decimal flex-col gap-2 ps-5">
            <li v-for="(para, pi) in section.paragraphs" :key="para.id" :class="{ flagged: store.isUnsupported(para) }" class="ps-1">
              <!-- read mode -->
              <template v-if="!store.editing">
                <BidiText :text="para.text" />
                <button v-for="(c, ci) in para.citations" :key="ci" type="button" class="cite" :class="citeClass(c)"
                  :title="c.quote || refLabel(c.ref)" @click="emit('openRef', c.ref)">
                  <AppIcon :name="c.ref.startsWith('S') ? 'book' : 'clip'" :size="12" />{{ refLabel(c.ref) }}
                </button>
                <span v-if="store.isUnsupported(para)" class="badge b-amber ms-1">غير مسند</span>
              </template>

              <!-- edit mode -->
              <div v-else class="flex flex-col gap-2 rounded-xl border border-line bg-white p-2.5">
                <Textarea v-model="para.text" auto-resize rows="2" class="w-full text-[14px]" :aria-label="`${section.heading} — فقرة ${pi + 1}`" />
                <div class="flex flex-wrap items-center gap-1.5">
                  <span v-for="(c, ci) in para.citations" :key="ci" class="cite" :class="citeClass(c)">
                    {{ refLabel(c.ref) }}
                    <button type="button" class="leading-none" :aria-label="`حذف الاستشهاد ${refLabel(c.ref)}`" @click="store.removeCitation(para, ci)"><AppIcon name="x" :size="12" /></button>
                  </span>
                  <Select :options="addOptions(para)" option-label="label" option-value="value" placeholder="+ استشهاد" size="small"
                    class="w-40" aria-label="إضافة استشهاد" @update:model-value="(v: string | null) => onAdd(para, v)" />
                  <label class="ms-auto flex items-center gap-1.5 text-xs muted">
                    <Checkbox v-model="para.legalClaim" binary /> ادعاء قانوني
                  </label>
                </div>
                <div class="flex gap-1">
                  <Button size="small" text severity="secondary" :disabled="pi === 0" aria-label="نقل لأعلى" @click="store.moveParagraph(si, pi, -1)"><AppIcon name="chevD" :size="15" class="rotate-180" /></Button>
                  <Button size="small" text severity="secondary" :disabled="pi === section.paragraphs.length - 1" aria-label="نقل لأسفل" @click="store.moveParagraph(si, pi, 1)"><AppIcon name="chevD" :size="15" /></Button>
                  <Button size="small" text severity="danger" aria-label="حذف الفقرة" @click="store.removeParagraph(si, pi)"><AppIcon name="trash" :size="15" /></Button>
                </div>
              </div>
            </li>
          </ol>
          <Button v-if="store.editing" size="small" text class="mt-1" @click="store.addParagraph(si)"><AppIcon name="plus" :size="15" /> إضافة فقرة</Button>
        </section>

        <p class="mt-4 mb-1">{{ store.content.closing }}</p>
        <p class="m-0 text-[13px]"><b>المرفقات:</b> {{ store.content.attachments.join('، ') }}</p>
      </article>
    </div>
  </section>
</template>
