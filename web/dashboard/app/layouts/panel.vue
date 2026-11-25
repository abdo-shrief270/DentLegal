<script setup lang="ts">
import type { IconName } from '~/utils/icons'

interface NavItem { to: string; label: string; icon: IconName; exact?: boolean }
interface NavGroup { title?: string; items: NavItem[] }

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const drawerOpen = ref(false)

const groups = computed<NavGroup[]>(() =>
  auth.role === 'admin'
    ? [
        { title: 'التشغيل', items: [
          { to: '/admin', label: 'نظرة عامة', icon: 'grid', exact: true },
          { to: '/admin/dentists', label: 'الأطباء', icon: 'users' },
          { to: '/admin/lawyers', label: 'المحامون', icon: 'scale' },
        ] },
        { title: 'المحتوى', items: [{ to: '/admin/knowledge', label: 'قاعدة المعرفة', icon: 'database' }] },
        { title: 'الجودة', items: [
          { to: '/admin/ratings', label: 'التقييمات', icon: 'checkCircle' },
          { to: '/admin/feedback', label: 'الملاحظات', icon: 'chats' },
          { to: '/admin/ai-runs', label: 'سجل الذكاء الاصطناعي', icon: 'sparkles' },
        ] },
      ]
    : [
        { items: [
          { to: '/lawyer', label: 'قائمة المراجعة', icon: 'listChecks', exact: true },
          { to: '/lawyer/samples', label: 'عينة الاستشهادات', icon: 'badge' },
        ] },
      ],
)

function isActive(item: NavItem) {
  return item.exact ? route.path === item.to || (item.to === '/lawyer' && route.path.startsWith('/lawyer/reviews')) : route.path.startsWith(item.to)
}

watch(() => route.fullPath, () => { drawerOpen.value = false })

async function logout() {
  await auth.logout()
  await router.push('/login')
}
</script>

<template>
  <div class="min-h-screen lg:grid lg:grid-cols-[256px_1fr]" style="background: var(--bg)">
    <!-- Sidebar (right side in RTL), drawer on small screens -->
    <aside
      class="fixed inset-y-0 start-0 z-40 flex w-[256px] flex-col gap-1 overflow-y-auto px-3.5 py-4 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
      :class="drawerOpen ? 'translate-x-0' : 'translate-x-full'"
      style="background: var(--navy-900); color: #cfe0e4"
    >
      <div class="flex items-center gap-2.5 px-1.5 pb-4 text-[17px] font-bold text-white">
        <BrandMark :size="36" />
        DentLegal
        <span class="badge" style="background: rgba(127,209,194,.16); color: #8fe3d3">{{ auth.role === 'admin' ? 'الإدارة' : 'بوابة المحامي' }}</span>
      </div>
      <div v-if="auth.user" class="mb-3 flex items-center gap-2.5 rounded-[13px] p-2.5" style="background: rgba(255,255,255,.06)">
        <span class="avatar" :class="{ gold: auth.role === 'lawyer' }">{{ auth.user.initials }}</span>
        <div class="min-w-0">
          <div class="truncate text-[13px] font-semibold text-white">{{ auth.user.name }}</div>
          <div class="truncate text-[11px]" style="color: #8fb3bb">{{ auth.user.subtitle }}</div>
        </div>
      </div>
      <nav aria-label="القائمة الرئيسية" class="flex flex-col gap-1">
        <template v-for="(group, gi) in groups" :key="gi">
          <div v-if="group.title" class="px-3 pt-3 pb-1 text-[11px] font-semibold" style="color: #6f949d">{{ group.title }}</div>
          <NuxtLink
            v-for="item in group.items" :key="item.to" :to="item.to"
            class="flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13.5px] font-medium no-underline transition-colors"
            :class="isActive(item) ? 'text-white' : 'hover:bg-white/5'"
            :style="isActive(item) ? 'background: rgba(22,160,138,.22)' : 'color: #cfe0e4'"
          >
            <AppIcon :name="item.icon" />
            {{ item.label }}
          </NuxtLink>
        </template>
      </nav>
      <div class="grow" />
      <button type="button" class="flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13.5px] hover:bg-white/5" style="color: #cfe0e4" @click="logout">
        <AppIcon name="logout" /> تسجيل الخروج
      </button>
    </aside>
    <div v-if="drawerOpen" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="drawerOpen = false" />

    <div class="min-w-0">
      <div class="flex items-center gap-2 border-b border-line bg-white px-4 py-2 lg:hidden">
        <Button text rounded aria-label="القائمة" @click="drawerOpen = true"><AppIcon name="menu" /></Button>
        <BrandMark :size="28" /><b>DentLegal</b>
        <span class="grow" />
        <MockDataBadge />
      </div>
      <slot />
    </div>
  </div>
</template>
