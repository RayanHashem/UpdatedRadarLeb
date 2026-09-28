<script setup lang="ts">
/*
 * Line icons from the 2026 redesign's Radar screen (stroke = currentColor,
 * so colour comes from the parent). Purely decorative: parents supply the
 * accessible name (aria-label) on the surrounding control.
 */
withDefaults(defineProps<{
    name: 'trophy' | 'help' | 'settings' | 'wallet' | 'pin' | 'antenna' | 'mobile' | 'bike' | 'suv' | 'muscle' | 'supercar'
        | 'music' | 'lock' | 'logout' | 'chevron' | 'close';
    size?: number;
    stroke?: number;
}>(), {
    size: 22,
    stroke: 1.7,
});
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="stroke"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <template v-if="name === 'trophy'">
            <path d="M8 4h8v5a4 4 0 0 1-8 0V4z" />
            <path d="M8 6H5a3 3 0 0 0 3 4" />
            <path d="M16 6h3a3 3 0 0 1-3 4" />
            <path d="M12 13v4" />
            <path d="M8 20h8" />
        </template>
        <template v-else-if="name === 'help'">
            <circle cx="12" cy="12" r="9" />
            <path d="M9.6 9.4a2.5 2.5 0 1 1 3.4 2.4c-.6.3-1 .8-1 1.5v.6" />
            <path d="M12 17h.01" />
        </template>
        <template v-else-if="name === 'settings'">
            <circle cx="12" cy="12" r="3" />
            <circle cx="12" cy="12" r="6.5" />
            <path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1" />
        </template>
        <template v-else-if="name === 'wallet'">
            <rect x="3" y="6" width="18" height="13" rx="2.5" />
            <path d="M3 10h18" />
            <path d="M16 14.5h2" />
            <path d="M6 6l9-3 1.5 3" />
        </template>
        <template v-else-if="name === 'pin'">
            <path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z" />
            <circle cx="12" cy="10" r="2.3" />
        </template>
        <template v-else-if="name === 'antenna'">
            <path d="M12 10v11" />
            <path d="M8 21l4-11 4 11" />
            <path d="M9.3 16.5h5.4" />
            <circle cx="12" cy="7.5" r="1.4" />
            <path d="M8.6 4a5 5 0 0 0 0 7" />
            <path d="M15.4 4a5 5 0 0 1 0 7" />
        </template>
        <template v-else-if="name === 'mobile'">
            <rect x="7" y="2.5" width="10" height="19" rx="2.2" />
            <path d="M11 18.5h2" />
        </template>
        <template v-else-if="name === 'bike'">
            <circle cx="5.5" cy="16" r="3.3" />
            <circle cx="18.5" cy="16" r="3.3" />
            <path d="M5.5 16l4-6h5l4 6" />
            <path d="M9.5 10L8 7H5.5" />
            <path d="M14.5 10L16 7h2.5" />
        </template>
        <template v-else-if="name === 'suv'">
            <path d="M3 16.5V12l2.2-5h10.6l3.2 5H21v4.5" />
            <path d="M3 12h18" />
            <circle cx="7.5" cy="16.5" r="2" />
            <circle cx="16.5" cy="16.5" r="2" />
            <path d="M9.5 16.5h5" />
        </template>
        <template v-else-if="name === 'muscle'">
            <path d="M2 15.5V13l4.5-1.2L9.5 9h5.5l4 2.8 3 1.2v2.5" />
            <circle cx="6.5" cy="15.5" r="2" />
            <circle cx="17.5" cy="15.5" r="2" />
            <path d="M8.5 15.5h7" />
        </template>
        <template v-else-if="name === 'music'">
            <path d="M9 18V5l11-2v13" />
            <circle cx="6.5" cy="18" r="2.5" />
            <circle cx="17.5" cy="16" r="2.5" />
        </template>
        <template v-else-if="name === 'lock'">
            <rect x="4.5" y="10.5" width="15" height="10" rx="2" />
            <path d="M8 10.5V7.5a4 4 0 0 1 8 0v3" />
        </template>
        <template v-else-if="name === 'logout'">
            <path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4" />
            <path d="M10 16l-4-4 4-4" />
            <path d="M6 12h10" />
        </template>
        <template v-else-if="name === 'chevron'">
            <path d="M9 5l7 7-7 7" />
        </template>
        <template v-else-if="name === 'close'">
            <path d="M6 6l12 12M18 6L6 18" />
        </template>
        <template v-else-if="name === 'supercar'">
            <path d="M2 15.5l1.2-2.2L11 10.5h4.5l6.5 3.3v1.7" />
            <circle cx="6.5" cy="15.5" r="2" />
            <circle cx="17.5" cy="15.5" r="2" />
            <path d="M8.5 15.5h7" />
        </template>
    </svg>
</template>
