<script setup lang="ts">
import type { InviteInfo, TwoFactorSetup } from '~/types/client'

definePageMeta({ layout: 'auth' })
useHead({ title: 'تفعيل الحساب — DentLegal' })

const route = useRoute()
const api = useApi()
const auth = useAuthStore()

const token = computed(() => String(route.params.token))
const step = ref<1 | 2 | 3>(1)
const invite = ref<InviteInfo | null>(null)
const loadError = ref('')
const password = ref('')
const confirm = ref('')
const setup = ref<TwoFactorSetup | null>(null)
const code = ref('')
const loading = ref(false)
const error = ref('')

const rules = computed(() => [
  { ok: password.value.length >= 10, text: '10 أحرف على الأقل' },
  { ok: /[A-Za-z]/.test(password.value) && /\d/.test(password.value), text: 'حروف وأرقام' },
  { ok: password.value.length > 0 && password.value === confirm.value, text: 'كلمتا المرور متطابقتان' },
])

onMounted(async () => {
  try {
    invite.value = await api.auth.getInvite(token.value)
  } catch (e) {
    loadError.value = errorMessage(e)
  }
})

async function setPassword() {
  error.value = ''
  loading.value = true
  try {
    setup.value = await api.auth.acceptInvite(token.value, password.value)
    step.value = 2
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

async function confirmCode() {
  error.value = ''
  loading.value = true
  try {
    auth.setUser(await api.auth.confirmTwoFactor(code.value))
    step.value = 3
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <Message v-if="loadError" severity="error">{{ loadError }}</Message>

    <template v-else-if="invite">
      <div>
        <div class="text-xs muted">الخطوة {{ step }} من 3</div>
        <h1 class="m-0 text-xl font-bold">
          {{ step === 1 ? 'مرحبًا بك في DentLegal' : step === 2 ? 'تفعيل التحقق بخطوتين' : 'احفظ رموز الاسترداد' }}
        </h1>
        <p v-if="step === 1" class="m-0 mt-1 text-[13px] muted">
          دُعيت باسم <b>{{ invite.name }}</b> ({{ invite.role === 'lawyer' ? 'محامٍ مراجِع' : 'إدارة' }}). اختر كلمة مرور لحسابك.
        </p>
      </div>

      <form v-if="step === 1" class="flex flex-col gap-3" @submit.prevent="setPassword">
        <div class="flex flex-col gap-1.5">
          <label class="text-[13px] font-semibold" for="email">البريد الإلكتروني</label>
          <InputText id="email" :model-value="invite.email" disabled dir="ltr" class="text-left" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-[13px] font-semibold" for="pw">كلمة المرور</label>
          <Password v-model="password" input-id="pw" :feedback="false" toggle-mask fluid autocomplete="new-password" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-[13px] font-semibold" for="pw2">تأكيد كلمة المرور</label>
          <Password v-model="confirm" input-id="pw2" :feedback="false" toggle-mask fluid autocomplete="new-password" />
        </div>
        <ul class="m-0 flex list-none flex-col gap-1 p-0 text-[12.5px]">
          <li v-for="rule in rules" :key="rule.text" class="flex items-center gap-1.5" :style="{ color: rule.ok ? 'var(--green-600)' : 'var(--muted)' }">
            <AppIcon :name="rule.ok ? 'checkCircle' : 'info'" :size="15" /> {{ rule.text }}
          </li>
        </ul>
        <Message v-if="error" severity="error" size="small">{{ error }}</Message>
        <Button type="submit" label="متابعة" :loading="loading" :disabled="!rules.every((r) => r.ok)" />
      </form>

      <form v-else-if="step === 2 && setup" class="flex flex-col gap-3" @submit.prevent="confirmCode">
        <p class="m-0 text-[13px] muted">امسح الرمز بتطبيق مصادقة (مثل Google Authenticator أو Microsoft Authenticator)، ثم أدخل الرمز الظاهر فيه.</p>
        <div class="mx-auto grid size-40 place-items-center rounded-xl border border-dashed border-line text-center text-xs muted" aria-label="رمز QR">
          رمز QR<br>(يُولَّد من الخادم)
        </div>
        <div class="text-center text-xs muted">أو أدخل المفتاح يدويًا: <span class="mono">{{ setup.secret }}</span></div>
        <CodeInput v-model="code" />
        <Message v-if="error" severity="error" size="small">{{ error }}</Message>
        <Button type="submit" label="تفعيل" :loading="loading" :disabled="code.length !== 6" />
      </form>

      <div v-else-if="step === 3 && setup" class="flex flex-col gap-3">
        <div class="alert amber">هذه الرموز تظهر مرة واحدة فقط. احفظها في مكان آمن؛ كل رمز يُستخدم مرة واحدة إذا فقدت هاتفك.</div>
        <div class="grid grid-cols-2 gap-2 rounded-xl p-3" style="background: var(--bg)">
          <span v-for="rc in setup.recoveryCodes" :key="rc" class="mono text-center">{{ rc }}</span>
        </div>
        <Button label="حفظتها، ادخل إلى اللوحة" @click="navigateTo(auth.homePath)" />
      </div>
    </template>

    <div v-else class="flex justify-center py-6"><ProgressSpinner style="width: 36px; height: 36px" /></div>
  </div>
</template>
