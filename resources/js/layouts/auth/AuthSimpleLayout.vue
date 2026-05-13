<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
}>()
</script>

<template>
  <main class="min-h-screen bg-background text-foreground">
    <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12">
      <div v-if="title || description" class="mb-6 text-center">
        <h1 v-if="title" class="text-2xl font-semibold tracking-tight">
          {{ title }}
        </h1>
        <p v-if="description" class="mt-2 text-sm text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <slot />
    </div>
  </main>
</template>
