<script setup lang="ts">
usePageMeta(
  'الحماية القانونية لأطباء الأسنان',
  'DentLegal يساعد طبيب الأسنان في السعودية على فهم موقفه النظامي في الشكاوى والإحالات: تحليل ذكي مبني على الأنظمة الصحية مع ذكر المصدر، ومسودة رد يعتمدها محامٍ مرخّص.',
  '/',
)
const { email, whatsappUrl } = useSite()

const values = [
  { icon: 'sparkles', title: 'تحليل ذكي لقضيتك', text: 'مبني على الأنظمة واللوائح الصحية السعودية، مع ذكر المادة التي يستند إليها كل استنتاج.' },
  { icon: 'scale', title: 'اعتماد من محامٍ مرخّص', text: 'لا يصل إليك رد معتمد قبل أن يراجعه محامٍ ويعتمده.' },
  { icon: 'lock', title: 'خصوصية وتشفير كامل', text: 'بيانات المرضى مرمّزة قبل أي تحليل، والمستندات مشفّرة ولا يطّلع عليها إلا أنت والمحامي المراجِع.' },
] as const

const steps = [
  { icon: 'upload', title: 'سجّل قضيتك', text: 'أدخل بيانات الشكوى أو الإحالة وارفع خطاب الشكوى والمستندات المتعلقة بها.' },
  { icon: 'sparkles', title: 'تحليل ذكي بالمصادر النظامية', text: 'تحصل على تقييم لموقفك ونقاط القوة والضعف والإجراءات الموصى بها، ومسودة رد رسمي.' },
  { icon: 'check', title: 'اعتماد من محامٍ مرخّص', text: 'يراجع المحامي المسودة ومصادرها ويعتمدها، فيصلك إشعار بالرد المعتمد.' },
] as const

const features = [
  { icon: 'chat', title: 'المستشار القانوني الذكي', text: 'اسأل عن السرية والإفصاح والموافقة المستنيرة والتأمين والتظلم، واحصل على إجابة تستشهد بالمادة، أو اعتذار صريح عندما لا تكفي المصادر.' },
  { icon: 'file', title: 'تحليل القضية ومسودة الرد', text: 'قراءة المستندات حتى المصوّرة منها، وتحليل منظّم، ومسودة رد بالوقائع والدفوع والطلبات.' },
  { icon: 'scale', title: 'مراجعة المحامي', text: 'محامٍ مرخّص يراجع المسودة ويعدّلها عند الحاجة قبل اعتمادها.' },
  { icon: 'lock', title: 'الخصوصية والتشفير', text: 'ترميز هوية المريض، وتشفير كل ملف بمفتاح مستقل، وسجل تدقيق لكل اطلاع.' },
] as const

const faq = [
  { q: 'هل يغني DentLegal عن المحامي؟', a: 'لا. التحليل الذكي يساعدك على فهم موقفك وإعداد ردّك، ولا يُعتمد أي رد رسمي قبل مراجعة محامٍ مرخّص واعتماده.' },
  { q: 'ما الأنظمة التي يستند إليها التحليل؟', a: 'نظام مزاولة المهن الصحية ولائحته التنفيذية، واللائحة التنظيمية لخدمات طب الأسنان، والوصف الوظيفي لطبيب الأسنان العام. ويُذكر المصدر مع كل استنتاج.' },
  { q: 'كيف تُحمى بيانات المرضى؟', a: 'تُرمَّز بيانات المريض المعرِّفة (الاسم والهوية والجوال وغيرها) قبل أي تحليل، فلا تصل إلى أنظمة الذكاء الاصطناعي. والمستندات الأصلية مشفّرة ولا يطّلع عليها إلا أنت والمحامي الذي يراجع قضيتك.' },
  { q: 'من يمكنه استخدام التطبيق؟', a: 'أطباء الأسنان المدعوّون حاليًا. يصلك رابط التحميل برسالة نصية، ثم تسجّل الدخول برقم جوالك.' },
  { q: 'كيف أحذف حسابي؟', a: 'من «حسابي» داخل التطبيق ← «حذف الحساب»، أو عبر صفحة الدعم. تُحذف قضاياك ومستنداتك خلال 30 يومًا.' },
] as const
</script>

<template>
  <div>
    <section class="bg-gradient-to-bl from-navy-900 via-navy-800 to-teal-700 text-white">
      <div class="container-page py-16 sm:py-24 grid gap-10 lg:grid-cols-2 lg:items-center">
        <div>
          <h1 class="!text-white text-4xl sm:text-5xl">الحماية القانونية لأطباء الأسنان</h1>
          <p class="mt-5 text-lg text-teal-100 max-w-xl">
            عند وصول شكوى أو إحالة، يساعدك DentLegal على فهم موقفك النظامي وإعداد ردّك بمصادر واضحة، ثم يعتمده محامٍ مرخّص.
          </p>
          <div class="mt-8 flex flex-wrap gap-3">
            <NuxtLink to="/#how" class="btn btn-primary">كيف يعمل</NuxtLink>
            <NuxtLink to="/support" class="btn btn-ghost">تواصل معنا</NuxtLink>
          </div>
          <div class="mt-6"><StoreBadges /></div>
        </div>
        <ul class="grid gap-4" aria-label="ما يقدمه DentLegal">
          <li v-for="v in values" :key="v.title" class="flex gap-4 rounded-2xl bg-white/10 p-5">
            <span class="shrink-0 rounded-xl bg-white/10 p-3 text-teal-100"><AppIcon :name="v.icon" /></span>
            <div>
              <h2 class="!text-white text-lg">{{ v.title }}</h2>
              <p class="text-teal-100 text-sm mt-1">{{ v.text }}</p>
            </div>
          </li>
        </ul>
      </div>
    </section>

    <section id="how" class="container-page py-16" aria-labelledby="how-title">
      <h2 id="how-title" class="text-3xl text-center">كيف يعمل</h2>
      <ol class="mt-10 grid gap-5 md:grid-cols-3">
        <li v-for="(s, i) in steps" :key="s.title" class="card">
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-teal-600 font-bold text-white">{{ i + 1 }}</span>
            <span class="text-teal-700"><AppIcon :name="s.icon" /></span>
          </div>
          <h3 class="mt-4 text-xl">{{ s.title }}</h3>
          <p class="mt-2 text-muted">{{ s.text }}</p>
        </li>
      </ol>
    </section>

    <section class="bg-white border-y border-line" aria-labelledby="features-title">
      <div class="container-page py-16">
        <h2 id="features-title" class="text-3xl text-center">المزايا</h2>
        <div class="mt-10 grid gap-5 sm:grid-cols-2">
          <article v-for="f in features" :key="f.title" class="card flex gap-4">
            <span class="shrink-0 h-fit rounded-xl bg-teal-50 p-3 text-teal-700"><AppIcon :name="f.icon" /></span>
            <div>
              <h3 class="text-lg">{{ f.title }}</h3>
              <p class="mt-1 text-muted">{{ f.text }}</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section id="faq" class="container-page py-16 max-w-3xl" aria-labelledby="faq-title">
      <h2 id="faq-title" class="text-3xl text-center">الأسئلة الشائعة</h2>
      <div class="mt-8 grid gap-3">
        <details v-for="item in faq" :key="item.q" class="card !p-5 group">
          <summary class="cursor-pointer font-semibold text-ink list-none flex justify-between gap-4">
            {{ item.q }} <span class="text-teal-600 group-open:rotate-45 transition-transform" aria-hidden="true">+</span>
          </summary>
          <p class="mt-3 text-muted">{{ item.a }}</p>
        </details>
      </div>
    </section>

    <section class="container-page pb-16">
      <div class="card text-center !p-10 bg-teal-50 border-teal-100">
        <h2 class="text-2xl">تواصل معنا</h2>
        <p class="mt-2 text-muted">لديك سؤال أو تريد الانضمام؟ يسعدنا تواصلك.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
          <a v-if="email" :href="`mailto:${email}`" class="btn btn-primary"><AppIcon name="mail" :size="18" /> راسلنا بالبريد</a>
          <a v-if="whatsappUrl" :href="whatsappUrl" class="btn btn-primary" rel="noopener"><AppIcon name="phone" :size="18" /> واتساب</a>
          <NuxtLink v-if="!email && !whatsappUrl" to="/support" class="btn btn-primary">صفحة الدعم</NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>
