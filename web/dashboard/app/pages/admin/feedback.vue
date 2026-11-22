<script setup lang="ts">
import type { FeedbackRow, FeedbackStatus } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'الملاحظات — DentLegal' })

const api = useApi()
const toast = useToast()
const rows = ref<FeedbackRow[]>([])
const loading = ref(true)
const status = ref<FeedbackStatus | 'all'>('all')

const statusOptions = [{ label: 'الكل', value: 'all' }, ...(Object.keys(FEEDBACK_STATUS) as FeedbackStatus[]).map((v) => ({ label: FEEDBACK_STATUS[v].label, value: v }))]
const editOptions = (Object.keys(FEEDBACK_STATUS) as FeedbackStatus[]).map((v) => ({ label: FEEDBACK_STATUS[v].label, value: v }))
const filtered = computed(() => rows.value.filter((r) => status.value === 'all' || r.status === status.value))

onMounted(async () => {
  try { rows.value = await api.admin.listFeedback() } catch (e) { toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 }) } finally { loading.value = false }
})

async function setStatus(row: FeedbackRow, value: FeedbackStatus) {
  const previous = row.status
  row.status = value
  try { await api.admin.setFeedbackStatus(row.id, value) } catch (e) { row.status = previous; toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 }) }
}

function exportCsv() {
  downloadCsv('dentlegal-feedback.csv', ['التاريخ', 'الطبيب', 'النوع', 'الملاحظة', 'الشاشة', 'الإصدار', 'المنصة', 'الحالة'],
    filtered.value.map((r) => [r.createdAt, r.dentistName, FEEDBACK_CATEGORY[r.category].label, r.message, r.screen, r.appVersion, r.platform, FEEDBACK_STATUS[r.status].label]))
}
</script>

<template>
  <div>
    <PanelTopbar title="ملاحظات الأطباء" crumb="لوحة الإدارة / الجودة">
      <template #actions>
        <MockDataBadge />
        <Button size="small" severity="secondary" outlined :disabled="!filtered.length" @click="exportCsv"><AppIcon name="download" :size="16" /> تصدير CSV</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-line p-3">
          <SelectButton v-model="status" :options="statusOptions" option-label="label" option-value="value" :allow-empty="false" size="small" />
        </div>
        <DataTable :value="filtered" :loading="loading" data-key="id" paginator :rows="10">
          <template #empty><div class="py-10 text-center muted">لا توجد ملاحظات.</div></template>
          <Column header="التاريخ"><template #body="{ data }"><DualDate :value="data.createdAt" with-time /></template></Column>
          <Column field="dentistName" header="الطبيب" />
          <Column header="النوع"><template #body="{ data }"><span class="badge" :class="FEEDBACK_CATEGORY[data.category as FeedbackRow['category']].cls">{{ FEEDBACK_CATEGORY[data.category as FeedbackRow['category']].label }}</span></template></Column>
          <Column header="الملاحظة" class="min-w-64"><template #body="{ data }">{{ data.message }}</template></Column>
          <Column header="الشاشة / الإصدار"><template #body="{ data }"><div>{{ data.screen }}</div><div class="text-xs muted"><span class="mono">{{ data.appVersion }}</span> · {{ data.platform === 'ios' ? 'iOS' : 'Android' }}</div></template></Column>
          <Column header="الحالة" class="w-44">
            <template #body="{ data }">
              <Select :model-value="data.status" :options="editOptions" option-label="label" option-value="value" size="small" class="w-full"
                :aria-label="`حالة ملاحظة ${data.dentistName}`" @update:model-value="(v: FeedbackStatus) => setStatus(data, v)" />
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>
