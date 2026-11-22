<script setup lang="ts">
import type { CaseDocumentRef, ReviewDetail } from '~/types/api'

const props = defineProps<{ detail: ReviewDetail }>()
const emit = defineEmits<{ openDocument: [doc: CaseDocumentRef] }>()

const facts = computed(() => [
  { label: 'نوع القضية', value: CASE_TYPE_LABELS[props.detail.caseType] },
  { label: 'الجهة', value: props.detail.authority },
  { label: 'رقم الشكوى', value: props.detail.complaintNo },
  { label: 'الإجراء', value: props.detail.procedure },
  { label: 'رمز المريض', value: props.detail.patientCode },
])
</script>

<template>
  <section class="pane" aria-label="ملف القضية">
    <div class="pane-h"><span class="flex items-center gap-2"><AppIcon name="folder" />ملف القضية</span><RiskBadge :risk="detail.risk" /></div>
    <div class="pane-b flex flex-col gap-4">
      <div class="flex items-center gap-2.5">
        <span class="avatar sm">{{ detail.dentist.name.replace('د. ', '').slice(0, 2) }}</span>
        <div>
          <div class="text-[13px] font-bold">{{ detail.dentist.name }}</div>
          <div class="text-xs muted">{{ detail.dentist.specialty }} · {{ detail.dentist.city }}</div>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <StrengthGauge :value="detail.positionStrength" :size="86" />
        <div class="text-xs muted">تقييم الذكاء الاصطناعي لقوة الموقف — <b>للاسترشاد فقط</b></div>
      </div>
      <dl class="m-0 flex flex-col gap-1.5 text-[12.5px]">
        <div v-for="f in facts" :key="f.label"><dt class="inline muted">{{ f.label }}: </dt><dd class="inline m-0 font-semibold">{{ f.value }}</dd></div>
        <div><dt class="inline muted">تاريخ الإجراء: </dt><dd class="inline m-0 font-semibold"><DualDate :value="detail.procedureDate" inline /></dd></div>
      </dl>
      <div>
        <div class="mb-1 text-xs font-bold muted">الوقائع كما أدخلها الطبيب</div>
        <p class="m-0 text-[12.5px] leading-7"><MaskedText :text="detail.factsMasked" /></p>
      </div>
      <div>
        <div class="mb-2 text-xs font-bold muted">المستندات ({{ detail.documents.length }})</div>
        <div class="flex flex-col gap-2">
          <button
            v-for="doc in detail.documents" :key="doc.ref" type="button"
            class="flex items-center gap-2.5 rounded-xl border border-line p-2.5 text-start hover:border-[var(--teal-300)]"
            @click="emit('openDocument', doc)"
          >
            <span class="grid size-9 place-items-center rounded-lg" style="background: var(--red-50); color: var(--red-600)"><AppIcon name="file" :size="17" /></span>
            <span class="min-w-0 grow">
              <span class="block truncate text-[12.5px] font-semibold">{{ doc.name }}</span>
              <span class="block text-[11.5px] muted">{{ doc.ref }} · {{ doc.pages }} ص · {{ doc.sizeLabel }}</span>
            </span>
            <span v-if="doc.lowQuality" class="badge b-amber">جودة منخفضة</span>
            <AppIcon name="eye" :size="16" />
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
