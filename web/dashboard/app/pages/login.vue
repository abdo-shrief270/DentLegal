<script setup lang="ts">
definePageMeta({ layout: 'auth' })
useHead({ title: 'تسجيل الدخول — DentLegal' })

const auth = useAuthStore()
const config = useRuntimeConfig()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    await navigateTo('/two-factor')
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

function fill(account: 'lawyer' | 'admin') {
  email.value = account === 'lawyer' ? 'sara@dentlegal.test' : 'noura@dentlegal.test'
  password.value = 'Demo@12345'
}
</script>

<template>
  <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
    <div>
      <h1 class="m-0 text-xl font-bold">تسجيل الدخول</h1>
      <p class="m-0 mt-1 text-[13px] muted">لوحة المحامين وإدارة المنصة</p>
    </div>
    <div class="flex flex-col gap-1.5">
      <label for="email" class="text-[13px] font-semibold">البريد الإلكتروني</label>
      <InputText id="email" v-model="email" type="email" autocomplete="username" dir="ltr" class="text-left" required />
    </div>
    <div class="flex flex-col gap-1.5">
      <label for="password" class="text-[13px] font-semibold">كلمة المرور</label>
      <Password v-model="password" input-id="password" :feedback="false" toggle-mask fluid autocomplete="current-password" required />
    </div>
    <Message v-if="error" severity="error" size="small">{{ error }}</Message>
    <Button type="submit" label="دخول" :loading="loading" :disabled="!email || !password" />
    <p class="m-0 text-center text-xs faint">يتطلب الدخول رمز تحقق من تطبيق المصادقة بعد كلمة المرور.</p>

    <div v-if="config.public.mockApi" class="alert amber flex-col !gap-2">
      <b>حسابات تجريبية (للعرض فقط)</b>
      <div class="flex flex-wrap gap-2">
        <Button size="small" severity="secondary" label="محامية: أ. سارة" @click="fill('lawyer')" />
        <Button size="small" severity="secondary" label="الإدارة: م. نورة" @click="fill('admin')" />
      </div>
      <span class="text-xs">رمز التحقق التجريبي: <span class="mono">123456</span></span>
    </div>
  </form>
</template>
