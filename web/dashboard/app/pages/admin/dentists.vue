<script setup lang="ts">
import type { DentistRow, UserStatus } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'الأطباء — DentLegal' })

const api = useApi()
const toast = useToast()
const confirm = useConfirm()

const rows = ref<DentistRow[]>([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref<UserStatus | 'all'>('all')
const inviteOpen = ref(false)
const inviting = ref(false)
const form = reactive({ name: '', phone: '', specialty: 'طب أسنان عام', city: '' })

const SPECIALTIES = ['طب أسنان عام', 'جراحة وزراعة الأسنان', 'جراحة الفم', 'تقويم الأسنان', 'علاج الجذور', 'طب أسنان الأطفال', 'اللثة', 'طب أسنان تجميلي', 'التركيبات']
const statusOptions = [
  { label: 'الكل', value: 'all' }, { label: 'نشط', value: 'active' }, { label: 'مدعو', value: 'invited' }, { label: 'موقوف', value: 'disabled' },
]

const filtered = computed(() => rows.value.filter((r) =>
  (statusFilter.value === 'all' || r.status === statusFilter.value)
  && (!search.value.trim() || r.name.includes(search.value.trim()) || r.phone.includes(search.value.trim()))))

const phoneValid = computed(() => /^5\d{8}$/.test(form.phone.replace(/\s/g, '')))

async function load() {
  loading.value = true
  try {
    rows.value = await api.admin.listDentists()
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    loading.value = false
  }
}
onMounted(load)

async function invite() {
  inviting.value = true
  try {
    const row = await api.admin.inviteDentist({ name: form.name.trim(), phone: `+966${form.phone.replace(/\s/g, '')}`, specialty: form.specialty, city: form.city.trim() })
    rows.value.unshift(row)
    inviteOpen.value = false
    Object.assign(form, { name: '', phone: '', specialty: 'طب أسنان عام', city: '' })
    toast.add({ severity: 'success', summary: 'أُرسلت الدعوة', detail: `رسالة نصية إلى ${row.phone} برابط تحميل التطبيق`, life: 3500 })
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    inviting.value = false
  }
}

async function resend(row: DentistRow) {
  try {
    await api.admin.resendInvite(row.id)
    toast.add({ severity: 'success', summary: 'أُعيد إرسال الدعوة', life: 2500 })
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  }
}

function toggle(row: DentistRow) {
  const activate = row.status === 'disabled'
  confirm.require({
    header: activate ? 'تفعيل الحساب' : 'إيقاف الحساب',
    message: activate ? `سيتمكن ${row.name} من الدخول مجددًا.` : `سيُسجَّل خروج ${row.name} من جميع الأجهزة ولن يتمكن من الدخول.`,
    acceptProps: { label: activate ? 'تفعيل' : 'إيقاف', severity: activate ? undefined : 'danger' },
    rejectProps: { label: 'إلغاء', severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await api.admin.setActive(row.id, activate)
        row.status = activate ? 'active' : 'disabled'
      } catch (e) {
        toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
      }
    },
  })
}
</script>

<template>
  <div>
    <PanelTopbar title="الأطباء المشاركون" crumb="لوحة الإدارة">
      <template #actions>
        <MockDataBadge />
        <Button size="small" @click="inviteOpen = true"><AppIcon name="plus" :size="16" /> دعوة طبيب</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div class="card !p-0 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-3">
          <SelectButton v-model="statusFilter" :options="statusOptions" option-label="label" option-value="value" :allow-empty="false" size="small" />
          <IconField class="w-full sm:w-72">
            <InputIcon><AppIcon name="search" :size="16" /></InputIcon>
            <InputText v-model="search" placeholder="بحث بالاسم أو الجوال" class="w-full" />
          </IconField>
        </div>
        <DataTable :value="filtered" :loading="loading" data-key="id" paginator :rows="10" row-hover>
          <template #empty><div class="py-10 text-center muted">لا يوجد أطباء مطابقون.</div></template>
          <Column header="الطبيب">
            <template #body="{ data }"><div class="font-semibold">{{ data.name }}</div><div class="text-xs muted">{{ data.specialty }} · {{ data.city }}</div></template>
          </Column>
          <Column header="الجوال"><template #body="{ data }"><span class="mono">{{ data.phone }}</span></template></Column>
          <Column header="الحالة"><template #body="{ data }"><span class="badge" :class="USER_STATUS[data.status as UserStatus].cls">{{ USER_STATUS[data.status as UserStatus].label }}</span></template></Column>
          <Column header="تاريخ الدعوة"><template #body="{ data }"><DualDate :value="data.joinedAt" /></template></Column>
          <Column header="آخر نشاط"><template #body="{ data }"><span v-if="data.lastActiveAt">{{ formatRelative(data.lastActiveAt) }}</span><span v-else class="faint">—</span></template></Column>
          <Column field="cases" header="القضايا" />
          <Column field="consultations" header="الاستشارات" />
          <Column header="" class="w-px whitespace-nowrap">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button v-if="data.status === 'invited'" size="small" text label="إعادة إرسال" @click="resend(data)" />
                <Button size="small" text :severity="data.status === 'disabled' ? undefined : 'danger'" :label="data.status === 'disabled' ? 'تفعيل' : 'إيقاف'" @click="toggle(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
      <div class="alert blue"><AppIcon name="info" /><div>يتلقى الطبيب المدعو رسالة نصية برابط التطبيق، ويدخل برقم جواله ورمز تحقق. لا يستطيع غير المدعوين التسجيل.</div></div>
    </div>

    <Dialog v-model:visible="inviteOpen" modal header="دعوة طبيب للتجربة" :style="{ width: '460px' }">
      <form id="invite-dentist" class="flex flex-col gap-3" @submit.prevent="invite">
        <div class="flex flex-col gap-1.5"><label for="d-name" class="text-[13px] font-semibold">الاسم</label><InputText id="d-name" v-model="form.name" placeholder="د. …" /></div>
        <div class="flex flex-col gap-1.5">
          <label for="d-phone" class="text-[13px] font-semibold">رقم الجوال</label>
          <InputGroup dir="ltr">
            <InputGroupAddon>+966</InputGroupAddon>
            <InputText id="d-phone" v-model="form.phone" inputmode="tel" placeholder="5X XXX XXXX" :invalid="!!form.phone && !phoneValid" />
          </InputGroup>
          <small v-if="form.phone && !phoneValid" style="color: var(--red-600)">رقم سعودي من 9 أرقام يبدأ بـ 5</small>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5"><label for="d-spec" class="text-[13px] font-semibold">التخصص</label><Select v-model="form.specialty" input-id="d-spec" :options="SPECIALTIES" /></div>
          <div class="flex flex-col gap-1.5"><label for="d-city" class="text-[13px] font-semibold">المدينة</label><InputText id="d-city" v-model="form.city" /></div>
        </div>
      </form>
      <template #footer>
        <Button severity="secondary" text label="إلغاء" @click="inviteOpen = false" />
        <Button type="submit" form="invite-dentist" label="إرسال الدعوة" :loading="inviting" :disabled="!form.name.trim() || !phoneValid || !form.city.trim()" />
      </template>
    </Dialog>
  </div>
</template>
