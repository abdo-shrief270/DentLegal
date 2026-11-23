<script setup lang="ts">
const { company } = useSite()
const year = new Date().getFullYear()
</script>

<template>
  <div class="min-h-screen flex flex-col bg-bg">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:m-2 focus:p-2 focus:bg-white">تخطَّ إلى المحتوى</a>
    <header class="bg-navy-900">
      <nav class="container-page flex items-center justify-between py-4" aria-label="التنقل الرئيسي">
        <BrandMark />
        <ul class="flex items-center gap-5 text-sm">
          <li><NuxtLink to="/#how" class="text-teal-100 no-underline hover:text-white">كيف يعمل</NuxtLink></li>
          <li><NuxtLink to="/#faq" class="text-teal-100 no-underline hover:text-white">الأسئلة الشائعة</NuxtLink></li>
          <li><NuxtLink to="/support" class="text-teal-100 no-underline hover:text-white">الدعم</NuxtLink></li>
        </ul>
      </nav>
    </header>
    <main id="main" class="flex-1"><slot /></main>
    <footer class="bg-navy-950 text-teal-100 text-sm">
      <div class="container-page py-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ year }} {{ company }}. جميع الحقوق محفوظة.</p>
        <ul class="flex gap-5">
          <li><NuxtLink to="/privacy" class="text-teal-100">سياسة الخصوصية</NuxtLink></li>
          <li><NuxtLink to="/terms" class="text-teal-100">الشروط والأحكام</NuxtLink></li>
          <li><NuxtLink to="/support" class="text-teal-100">الدعم والتواصل</NuxtLink></li>
        </ul>
      </div>
    </footer>
  </div>
</template>
