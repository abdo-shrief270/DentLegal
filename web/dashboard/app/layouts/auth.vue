<template>
  <div class="grid min-h-screen place-items-center px-4 py-10"
    style="background: linear-gradient(160deg, var(--navy-950), var(--navy-900) 55%, var(--teal-700))">
    <div class="w-full max-w-[430px]">
      <div class="mb-6 flex items-center justify-center gap-3 text-white">
        <BrandMark :size="44" />
        <div>
          <div class="text-2xl font-bold leading-tight">DentLegal</div>
          <div class="text-[13px]" style="color: #a9c3ca">الحماية القانونية لأطباء الأسنان</div>
        </div>
      </div>
      <div class="rounded-[22px] bg-white p-7 shadow-[var(--shadow-lg)]">
        <slot />
      </div>
      <div class="mt-4 flex justify-center"><MockDataBadge /></div>
    </div>
  </div>
</template>
