<script setup lang="ts">
import type { ReviewListItem, ReviewTab } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'قائمة المراجعة — DentLegal' })

const api = useApi()
const toast = useToast()
const confirm = useConfirm()

const tab = ref<ReviewTab>('mine')
const search = ref('')
const rows = ref<ReviewListItem[]>([])
const counts = ref<Record<ReviewTab, number>>({ unclaimed: 0, mine: 0, approved: 0 })
const loading = ref(false)

const tabs: { value: ReviewTab; label: string }[] = [
  { value: 'unclaimed', label: 'بانتظار الاستلام' },
  { value: 'mine', label: 'قيد مراجعتي' },
  { value: 'approved', label: 'معتمدة' },
]

const filtered = computed(() => {
  const q = search.value.trim()
  return q ? rows.value.filter((r) => r.caseCode.includes(q) || r.title.includes(q) || r.dentistName.includes(q)) : rows.value
})

async function refresh() {
  loading.value = true
  try {
    ;[rows.value, counts.value] = await Promise.all([api.lawyer.listReviews(tab.value), api.lawyer.counts()])
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    loading.value = false
  }
}

function claim(row: ReviewListItem) {
  confirm.require({
    header: 'استلام القضية',
    message: `ستُسند القضية ${row.caseCode} إليك ولن تظهر لبقية المحامين. هل تريد المتابعة؟`,
    acceptProps: { label: 'استلام' },
    rejectProps: { label: 'إلغاء', severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await api.lawyer.claim(row.draftId)
        toast.add({ severity: 'success', summary: 'تم استلام القضية', life: 2500 })
        await navigateTo(`/lawyer/reviews/${row.draftId}`)
      } catch (e) {
        toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
        await refresh()
      }
    },
  })
}

watch(tab, refresh, { immediate: true })
</script>

<template>
  <div>
    <PanelTopbar title="قائمة المراجعة" crumb="بوابة المحامي">
      <template #actions><MockDataBadge /></template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 pt-3">
          <div class="flex gap-1" role="tablist">
            <button
              v-for="t in tabs" :key="t.value" type="button" role="tab" :aria-selected="tab === t.value"
              class="-mb-px flex items-center gap-2 border-b-2 px-3 pb-3 text-[13.5px] font-semibold transition-colors"
              :class="tab === t.value ? 'border-[var(--teal-600)] text-[var(--teal-700)]' : 'border-transparent text-muted hover:text-[var(--ink)]'"
              @click="tab = t.value"
            >
              {{ t.label }}
              <span class="badge" :class="tab === t.value ? 'b-teal' : 'b-gray'">{{ counts[t.value] }}</span>
            </button>
          </div>
          <IconField class="mb-3 w-full sm:w-72">
            <InputIcon><AppIcon name="search" :size="16" /></InputIcon>
            <InputText v-model="search" placeholder="بحث برقم القضية أو الطبيب" class="w-full" />
          </IconField>
        </div>

        <DataTable :value="filtered" :loading="loading" data-key="draftId" row-hover class="text-[13px]"
          @row-click="(e) => tab !== 'unclaimed' && navigateTo(`/lawyer/reviews/${e.data.draftId}`)">
          <template #empty>
            <div class="py-10 text-center muted">
              {{ tab === 'unclaimed' ? 'لا توجد قضايا بانتظار الاستلام.' : tab === 'mine' ? 'لا توجد قضايا قيد مراجعتك.' : 'لا توجد ردود معتمدة بعد.' }}
            </div>
          </template>
          <Column header="رقم القضية"><template #body="{ data }"><span class="mono">{{ data.caseCode }}</span></template></Column>
          <Column header="الموضوع">
            <template #body="{ data }">
              <div class="font-semibold">{{ data.title }}</div>
              <div class="text-xs muted">{{ CASE_TYPE_LABELS[data.caseType as keyof typeof CASE_TYPE_LABELS] }}</div>
            </template>
          </Column>
          <Column field="dentistName" header="الطبيب" />
          <Column header="المخاطر"><template #body="{ data }"><RiskBadge :risk="data.risk" /></template></Column>
          <Column header="قوة الموقف"><template #body="{ data }"><b>{{ data.positionStrength }}%</b></template></Column>
          <Column :header="tab === 'approved' ? 'تاريخ الاعتماد' : 'أُرسلت للمراجعة'">
            <template #body="{ data }"><DualDate :value="tab === 'approved' ? data.approvedAt : data.submittedAt" with-time /></template>
          </Column>
          <Column header="الفحوصات">
            <template #body="{ data }">
              <span v-if="data.flagsCount" class="badge b-amber"><AppIcon name="alert" :size="13" />{{ data.flagsCount }} ملاحظات</span>
              <span v-else class="badge b-green"><AppIcon name="checkCircle" :size="13" />سليمة</span>
            </template>
          </Column>
          <Column header="" class="w-px whitespace-nowrap">
            <template #body="{ data }">
              <Button v-if="tab === 'unclaimed'" size="small" label="استلام" @click.stop="claim(data)" />
              <Button v-else size="small" severity="secondary" outlined :label="tab === 'approved' ? 'عرض' : 'فتح المراجعة'"
                @click.stop="navigateTo(`/lawyer/reviews/${data.draftId}`)" />
            </template>
          </Column>
        </DataTable>
      </div>

      <div class="alert blue">
        <AppIcon name="info" />
        <div>قائمة مشتركة: أي محامٍ مشارك يمكنه استلام القضية، وتصبح بعد الاستلام ظاهرة له وحده. يصل الرد للطبيب فور اعتماده.</div>
      </div>
    </div>
  </div>
</template>
