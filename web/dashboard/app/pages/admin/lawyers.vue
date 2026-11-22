<script setup lang="ts">
import type { LawyerRow } from '~/types/api'

definePageMeta({ layout: 'panel' })
useHead({ title: 'المحامون — DentLegal' })

const api = useApi()
const toast = useToast()
const confirm = useConfirm()

const lawyers = ref<LawyerRow[]>([])
const loading = ref(true)
const inviteOpen = ref(false)
const inviting = ref(false)
const form = reactive({ name: '', email: '', licenseNo: '', firm: '' })
const emailValid = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim()))

onMounted(async () => {
  try {
    lawyers.value = await api.admin.listLawyers()
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    loading.value = false
  }
})

async function invite() {
  inviting.value = true
  try {
    lawyers.value.push(await api.admin.inviteLawyer({ name: form.name.trim(), email: form.email.trim(), licenseNo: form.licenseNo.trim(), firm: form.firm.trim() }))
    inviteOpen.value = false
    toast.add({ severity: 'success', summary: 'أُرسلت الدعوة', detail: `بريد إلى ${form.email} لتعيين كلمة المرور وتفعيل التحقق بخطوتين`, life: 3500 })
    Object.assign(form, { name: '', email: '', licenseNo: '', firm: '' })
  } catch (e) {
    toast.add({ severity: 'error', summary: errorMessage(e), life: 4000 })
  } finally {
    inviting.value = false
  }
}

function toggle(lawyer: LawyerRow) {
  const activate = lawyer.status === 'disabled'
  confirm.require({
    header: activate ? 'تفعيل الحساب' : 'إيقاف الحساب',
    message: activate ? `سيتمكن ${lawyer.name} من الدخول مجددًا.` : `لن يتمكن ${lawyer.name} من الدخول. القضايا التي استلمها تبقى مسندة إليه حتى تُعاد يدويًا.`,
    acceptProps: { label: activate ? 'تفعيل' : 'إيقاف', severity: activate ? undefined : 'danger' },
    rejectProps: { label: 'إلغاء', severity: 'secondary', outlined: true },
    accept: async () => {
      await api.admin.setActive(lawyer.id, activate)
      lawyer.status = activate ? 'active' : 'disabled'
    },
  })
}
</script>

<template>
  <div>
    <PanelTopbar title="المحامون المشاركون" crumb="لوحة الإدارة">
      <template #actions>
        <MockDataBadge />
        <Button size="small" @click="inviteOpen = true"><AppIcon name="plus" :size="16" /> دعوة محامٍ</Button>
      </template>
    </PanelTopbar>

    <div class="flex flex-col gap-4 p-6">
      <div v-if="loading" class="flex justify-center py-10"><ProgressSpinner style="width: 36px; height: 36px" /></div>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="l in lawyers" :key="l.id" class="card flex flex-col gap-3">
          <div class="flex items-center gap-3">
            <span class="avatar gold">{{ l.initials }}</span>
            <div class="min-w-0 grow">
              <div class="font-bold">{{ l.name }}</div>
              <div class="truncate text-xs muted">{{ l.firm }}</div>
            </div>
            <span class="badge" :class="USER_STATUS[l.status].cls">{{ USER_STATUS[l.status].label }}</span>
          </div>
          <dl class="m-0 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-[13px]">
            <dt class="muted">الترخيص</dt><dd class="m-0 font-semibold">ترخيص محاماة {{ l.licenseNo }}</dd>
            <dt class="muted">البريد</dt><dd class="m-0"><span class="mono">{{ l.email }}</span></dd>
            <dt class="muted">قيد المراجعة</dt><dd class="m-0 font-semibold">{{ l.inReview }}</dd>
            <dt class="muted">معتمدة هذا الشهر</dt><dd class="m-0 font-semibold">{{ l.approvedThisMonth }}</dd>
            <dt class="muted">متوسط زمن المراجعة</dt><dd class="m-0 font-semibold">{{ l.avgReviewHours === null ? '—' : `${l.avgReviewHours} ساعة` }}</dd>
          </dl>
          <div class="flex justify-end gap-1 border-t border-line pt-2">
            <Button v-if="l.status === 'invited'" size="small" text label="إعادة إرسال الدعوة" @click="api.admin.resendInvite(l.id).then(() => toast.add({ severity: 'success', summary: 'أُعيد إرسال الدعوة', life: 2500 }))" />
            <Button size="small" text :severity="l.status === 'disabled' ? undefined : 'danger'" :label="l.status === 'disabled' ? 'تفعيل' : 'إيقاف'" @click="toggle(l)" />
          </div>
        </article>
      </div>
      <div class="alert blue"><AppIcon name="info" /><div>في التجربة تعمل قائمة المراجعة كقائمة مشتركة: يستلم المحامي القضية بنفسه. الإسناد التلقائي ومهل المراجعة في المشروع الثاني.</div></div>
    </div>

    <Dialog v-model:visible="inviteOpen" modal header="دعوة محامٍ" :style="{ width: '460px' }">
      <form id="invite-lawyer" class="flex flex-col gap-3" @submit.prevent="invite">
        <div class="flex flex-col gap-1.5"><label for="l-name" class="text-[13px] font-semibold">الاسم</label><InputText id="l-name" v-model="form.name" placeholder="أ. …" /></div>
        <div class="flex flex-col gap-1.5"><label for="l-email" class="text-[13px] font-semibold">البريد الإلكتروني</label><InputText id="l-email" v-model="form.email" type="email" dir="ltr" class="text-left" :invalid="!!form.email && !emailValid" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5"><label for="l-lic" class="text-[13px] font-semibold">رقم الترخيص</label><InputText id="l-lic" v-model="form.licenseNo" dir="ltr" class="text-left" /></div>
          <div class="flex flex-col gap-1.5"><label for="l-firm" class="text-[13px] font-semibold">المكتب</label><InputText id="l-firm" v-model="form.firm" /></div>
        </div>
      </form>
      <template #footer>
        <Button severity="secondary" text label="إلغاء" @click="inviteOpen = false" />
        <Button type="submit" form="invite-lawyer" label="إرسال الدعوة" :loading="inviting" :disabled="!form.name.trim() || !emailValid || !form.licenseNo.trim()" />
      </template>
    </Dialog>
  </div>
</template>
