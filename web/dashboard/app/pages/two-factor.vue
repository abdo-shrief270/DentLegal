<script setup lang="ts">
definePageMeta({ layout: 'auth' })
useHead({ title: 'رمز التحقق — DentLegal' })

const auth = useAuthStore()
const useRecovery = ref(false)
const code = ref('')
const recoveryCode = ref('')
const loading = ref(false)
const error = ref('')

const canSubmit = computed(() => (useRecovery.value ? recoveryCode.value.trim().length >= 10 : code.value.length === 6))

async function submit() {
  if (!canSubmit.value) return
  error.value = ''
  loading.value = true
  try {
    await auth.verifyTwoFactor(useRecovery.value ? { recoveryCode: recoveryCode.value } : { code: code.value })
    await navigateTo(auth.homePath)
  } catch (e) {
    error.value = errorMessage(e)
    code.value = ''
  } finally {
    loading.value = false
  }
}

watch(code, (value) => { if (value.length === 6) submit() })
</script>

<template>
  <form class="flex flex-col gap-4" @submit.prevent="submit">
    <div>
      <h1 class="m-0 text-xl font-bold">التحقق بخطوتين</h1>
      <p class="m-0 mt-1 text-[13px] muted">
        {{ useRecovery ? 'أدخل أحد رموز الاسترداد التي حفظتها عند التفعيل.' : 'أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة.' }}
      </p>
    </div>
    <CodeInput v-if="!useRecovery" id="code" v-model="code" />
    <InputText v-else v-model="recoveryCode" dir="ltr" class="mono text-center" placeholder="XXXX-XXXX-XXXX" aria-label="رمز الاسترداد" />
    <Message v-if="error" severity="error" size="small">{{ error }}</Message>
    <Button type="submit" label="تحقق ودخول" :loading="loading" :disabled="!canSubmit" />
    <div class="flex items-center justify-between text-[13px]">
      <button type="button" class="text-[var(--teal-600)] font-semibold" @click="useRecovery = !useRecovery; error = ''">
        {{ useRecovery ? 'استخدام تطبيق المصادقة' : 'استخدام رمز استرداد' }}
      </button>
      <NuxtLink to="/login" class="muted no-underline">رجوع</NuxtLink>
    </div>
  </form>
</template>
