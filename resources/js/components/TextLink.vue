<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { HTMLAttributes } from 'vue'

import { cn } from '@/lib/utils'

const props = defineProps<{
  href: string
  method?: 'get' | 'post' | 'put' | 'patch' | 'delete'
  as?: string
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <Link
    :href="props.href"
    :method="props.method"
    :as="props.as"
    :class="cn('text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors hover:decoration-current dark:decoration-neutral-500', props.class)"
  >
    <slot />
  </Link>
</template>
