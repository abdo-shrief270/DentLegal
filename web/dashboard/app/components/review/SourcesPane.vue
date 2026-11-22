<script setup lang="ts">
import type { RetrievedSource, ReviewDetail } from '~/types/api'

const props = defineProps<{ detail: ReviewDetail; readonly: boolean }>()
const emit = defineEmits<{ openSource: [source: RetrievedSource]; addNote: [text: string] }>()
const note = ref('')

function submitNote() {
  const text = note.value.trim()
  if (!text) return
  emit('addNote', text)
  note.value = ''
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <section class="pane" aria-label="فحوصات الجودة الآلية">
      <div class="pane-h"><span class="flex items-center gap-2"><AppIcon name="shield" />فحوصات الجودة الآلية</span></div>
      <div class="pane-b !py-1">
        <div v-for="(check, i) in props.detail.checks" :key="i" class="check-row">
          <span :style="{ color: check.ok ? 'var(--green-600)' : 'var(--amber-600)' }"><AppIcon :name="check.ok ? 'checkCircle' : 'alert'" :size="16" /></span>
          <BidiText :text="check.text" />
        </div>
      </div>
    </section>

    <section class="pane" aria-label="المصادر المسترجعة">
      <div class="pane-h"><span class="flex items-center gap-2"><AppIcon name="database" />المصادر المسترجعة</span><span class="badge b-gray">{{ detail.sources.length }}</span></div>
      <div class="pane-b flex flex-col gap-2">
        <div v-for="s in detail.sources" :key="s.ref" class="src-card" role="button" tabindex="0" @click="emit('openSource', s)" @keydown.enter="emit('openSource', s)">
          <div class="h"><span>{{ s.ref }} · {{ s.label }} — {{ s.title }}</span><span class="mono">{{ Math.round(s.score * 100) }}%</span></div>
          <div class="text-xs muted">{{ s.text.slice(0, 96) }}…</div>
        </div>
      </div>
    </section>

    <section class="pane" aria-label="ملاحظات داخلية">
      <div class="pane-h"><span class="flex items-center gap-2"><AppIcon name="chats" />ملاحظات داخلية</span><span class="text-[11px] font-normal muted">لا تظهر للطبيب</span></div>
      <div class="pane-b flex flex-col gap-2">
        <div v-for="n in detail.notes" :key="n.id" class="rounded-xl p-2.5 text-[12.5px]" style="background: var(--bg)">
          <b>{{ n.author }}:</b> {{ n.text }}
          <div class="mt-1 text-[11px] faint"><DualDate :value="n.createdAt" with-time inline /></div>
        </div>
        <p v-if="!detail.notes.length" class="m-0 text-xs muted">لا توجد ملاحظات.</p>
        <template v-if="!readonly">
          <Textarea v-model="note" rows="2" auto-resize placeholder="أضف ملاحظة داخلية…" class="text-[13px]" />
          <Button size="small" severity="secondary" label="إضافة ملاحظة" :disabled="!note.trim()" @click="submitNote" />
        </template>
      </div>
    </section>
  </div>
</template>
