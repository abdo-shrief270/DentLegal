<script setup lang="ts">
import type { KnowledgeChunk, KnowledgeSource, SourceVersion } from '~/types/api'

definePageMeta({ layout: 'panel' })

const route = useRoute()
const api = useApi()
const toast = useToast()

const source = ref<KnowledgeSource | null>(null)
const version = ref<SourceVersion | null>(null)
const chunks = ref<KnowledgeChunk[]>([])
const search = ref('')
const type = ref<KnowledgeChunk['chunkType'] | 'all'>('all')
const expanded = ref<KnowledgeChunk[]>([])

const TYPE_LABELS: Record<KnowledgeChunk['chunkType'], string> = { article: 'مادة', regulation: 'لائحة', definition: 'تعريف', section: 'قسم', form: 'نموذج' }
const typeOptions = computed(() => [{ label: 'الكل', value: 'all' }, ...[...new Set(chunks.value.map((c) => c.chunkType))].map((t) => ({ label: TYPE_LABELS[t], value: t }))])
const filtered = computed(() => chunks.value.filter((c) => (type.value === 'all' || c.chunkType === type.value) && (!search.value.trim() || c.text.includes(search.value.trim()) || c.chunkKey.includes(search.value.trim()))))

useHead(() => ({ title: version.value ? `${source.value?.title} — ${version.value.edition}` : 'معاينة المقاطع' }))

onMounted(async () => {
  try {
    const id = String(route.params.versionId)
    const [info, list] = await Promise.all([api.admin.getVersion(id), api.admin.listChunks(id)])
    source.value = info.source
    version.value = info.version
    chunks.value = list
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
})
</script>

<template>
  <div>
    <PanelTopbar :title="source?.title ?? 'معاينة المقاطع'" :crumb="version ? `قاعدة المعرفة / ${version.versionLabel} · ${version.edition}` : 'قاعدة المعرفة'">
      <template #actions>
        <MockDataBadge />
        <span v-if="version" class="badge" :class="VERSION_STATUS[version.status].cls">{{ VERSION_STATUS[version.status].label }}</span>
        <Button size="small" severity="secondary" outlined @click="navigateTo('/admin/knowledge')"><AppIcon name="chevR" :size="15" /> قاعدة المعرفة</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-3">
          <SelectButton v-model="type" :options="typeOptions" option-label="label" option-value="value" :allow-empty="false" size="small" />
          <IconField class="w-full sm:w-72">
            <InputIcon><AppIcon name="search" :size="16" /></InputIcon>
            <InputText v-model="search" placeholder="بحث في نص المقاطع" class="w-full" />
          </IconField>
        </div>
        <DataTable v-model:expanded-rows="expanded" :value="filtered" data-key="id" paginator :rows="15" row-hover>
          <template #empty><div class="py-10 text-center muted">لا توجد مقاطع مطابقة.</div></template>
          <Column expander class="w-10" />
          <Column header="المرجع"><template #body="{ data }"><span class="mono">{{ data.chunkKey }}</span></template></Column>
          <Column header="النوع"><template #body="{ data }"><span class="badge b-teal">{{ TYPE_LABELS[data.chunkType as KnowledgeChunk['chunkType']] }}</span></template></Column>
          <Column header="المادة / الفقرة"><template #body="{ data }">{{ data.paragraphRef ? `اللائحة ${data.paragraphRef}` : data.articleNo ? `المادة ${data.articleNo}` : '—' }}</template></Column>
          <Column header="الصفحة" field="pages" />
          <Column header="النص"><template #body="{ data }"><span class="text-[12.5px] muted">{{ data.text.slice(0, 80) }}…</span></template></Column>
          <template #expansion="{ data }"><p class="m-0 px-4 py-2 leading-8">{{ data.text }}</p></template>
        </DataTable>
      </div>
    </div>
  </div>
</template>
