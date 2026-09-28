<script setup lang="ts">
const props = withDefaults(defineProps<{
  message?: string | string[] | null
  // 'inline' is the 2026 redesign field error (red icon + text, no container).
  variant?: 'default' | 'material' | 'inline'
}>(), {
  variant: 'default'
})

function text(msg?: string | string[] | null) {
  if (!msg) return ""
  return Array.isArray(msg) ? msg.filter(Boolean).join("\n") : msg
}
</script>

<template>
  <div v-if="text(message)" :class="['error-message', props.variant === 'material' ? 'error-message-material' : '', props.variant === 'inline' ? 'error-message-inline' : '']">
    <span v-if="props.variant === 'default'" class="warning-emoji">⚠️</span>
    <svg v-if="props.variant === 'material'" class="error-icon-material" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    <svg v-if="props.variant === 'inline'" class="error-icon-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
      <circle cx="12" cy="12" r="9"/>
      <path d="M12 7.5v5.5"/>
      <path d="M12 16.5h.01"/>
    </svg>
    <p :class="['error-text', props.variant === 'material' ? 'error-text-material' : '', props.variant === 'inline' ? 'error-text-inline' : '']">
      {{ text(message) }}
    </p>
  </div>
</template>

<style scoped>
.error-message {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 4px;
  margin-bottom: 8px;
}

.warning-emoji {
  font-size: 16px;
  flex-shrink: 0;
  margin-top: 2px;
  line-height: 1;
}

.error-text {
  font-size: 0.875rem;
  color: #ef4444;
  margin: 0;
  line-height: 1.5;
  white-space: pre-line;
}

/* Material Design variant - Pill container for better readability */
.error-message-material {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 6px;
  margin-bottom: 8px;
  background: rgba(0, 0, 0, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-left: 3px solid #FB7185;
  padding: 6px 10px;
  border-radius: 10px;
}

.error-icon-material {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  color: #FB7185;
  margin-top: 2px;
}

.error-text-material {
  font-size: 12px;
  font-weight: 500;
  line-height: 1.3;
  color: rgba(255, 255, 255, 0.92);
  margin: 0;
  text-align: left;
  white-space: normal;
}

/* Inline variant - 2026 redesign field error */
.error-message-inline {
  margin: 2px 0 0;
  color: var(--rl-color-alert);
}

.error-icon-inline {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  margin-top: 1px;
}

.error-text-inline {
  font-size: 14px;
  line-height: 1.45;
  color: inherit;
}
</style>
