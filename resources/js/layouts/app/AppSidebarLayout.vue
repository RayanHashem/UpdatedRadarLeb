<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

import type { BreadcrumbItemType } from '@/types'

withDefaults(defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>(), {
  breadcrumbs: () => [],
})
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <header class="border-b bg-background">
      <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4">
        <Link href="/dashboard" class="font-semibold">
          RadarLeb
        </Link>

        <nav v-if="breadcrumbs.length" class="flex items-center gap-2 text-sm text-muted-foreground">
          <template v-for="(item, index) in breadcrumbs" :key="item.href">
            <span v-if="index > 0">/</span>
            <Link :href="item.href" class="hover:text-foreground">
              {{ item.title }}
            </Link>
          </template>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
      <slot />
    </main>
  </div>
</template>
