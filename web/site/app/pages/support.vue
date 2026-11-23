<script setup lang="ts">
usePageMeta('الدعم والتواصل', 'تواصل مع فريق DentLegal، واطلب حذف حسابك وبياناتك.', '/support')
const { email, whatsappUrl } = useSite()
</script>

<template>
  <div class="container-page py-14 max-w-3xl">
    <h1 class="text-3xl">الدعم والتواصل</h1>
    <p class="mt-3 text-muted">نرد على الاستفسارات خلال يومي عمل.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
      <a v-if="email" :href="`mailto:${email}`" class="card no-underline flex gap-3 items-center">
        <span class="text-teal-700"><AppIcon name="mail" /></span>
        <span><span class="block font-semibold text-ink">البريد الإلكتروني</span><bdi class="text-muted" dir="ltr">{{ email }}</bdi></span>
      </a>
      <a v-if="whatsappUrl" :href="whatsappUrl" rel="noopener" class="card no-underline flex gap-3 items-center">
        <span class="text-teal-700"><AppIcon name="phone" /></span>
        <span><span class="block font-semibold text-ink">واتساب</span><span class="text-muted">محادثة مع فريق الدعم</span></span>
      </a>
      <p v-if="!email && !whatsappUrl" class="card text-muted sm:col-span-2">تُضاف وسائل التواصل هنا عند الإطلاق.</p>
    </div>

    <section class="mt-12" aria-labelledby="delete-title">
      <h2 id="delete-title" class="text-2xl flex items-center gap-2"><AppIcon name="trash" /> حذف الحساب والبيانات</h2>
      <ol class="mt-4 grid gap-2 list-decimal ps-6 text-ink-2">
        <li>من داخل التطبيق: «حسابي» ← «حذف الحساب» ← تأكيد. يُسجَّل خروجك من كل الأجهزة فورًا.</li>
        <li>أو راسلنا من رقم الجوال المسجّل نفسه وسننفّذ الطلب.</li>
        <li>تُحذف قضاياك ومستنداتك وتحليلاتك واستشاراتك نهائيًا خلال 30 يومًا من الطلب.</li>
      </ol>
    </section>

    <section class="mt-12" aria-labelledby="help-title">
      <h2 id="help-title" class="text-2xl">مساعدة سريعة</h2>
      <ul class="mt-4 grid gap-2 list-disc ps-6 text-ink-2">
        <li>لم يصلك رمز التحقق؟ انتظر دقيقة ثم اطلب إعادة الإرسال، وتأكد أن رقمك مدعو للتطبيق.</li>
        <li>المستند المصوّر غير واضح؟ صوّره في إضاءة جيدة وبشكل مستقيم، أو ارفعه بصيغة PDF.</li>
        <li>التحليل يستغرق وقتًا؟ يصلك عادةً خلال دقائق، ويمكنك إغلاق التطبيق والعودة لاحقًا.</li>
      </ul>
    </section>
  </div>
</template>
