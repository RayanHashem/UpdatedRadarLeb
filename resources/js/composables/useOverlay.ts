import { ref, type Ref } from 'vue'

/**
 * Identifiers for the overlays mounted on top of the dashboard. `null` means
 * no overlay is open. New overlays should add their identifier here so the
 * type stays exhaustive.
 */
export type OverlayKey = 'help' | 'winners' | 'settings' | null

/**
 * Centralised overlay state for the dashboard.
 *
 * Used to live as a bare `ref(null)` inside Dashboard.vue alongside ~30 other
 * refs. Pulling it into a composable means each overlay component can be
 * extracted without re-threading the close handler through props.
 *
 * Pattern:
 *
 *   const { active, open, close, isOpen } = useOverlay()
 *
 *   <button @click="open('help')">?</button>
 *   <HelpOverlay v-if="isOpen('help')" @close="close" />
 */
export function useOverlay() {
    const active: Ref<OverlayKey> = ref(null)

    const open = (key: Exclude<OverlayKey, null>) => {
        active.value = key
    }

    const close = () => {
        active.value = null
    }

    const isOpen = (key: Exclude<OverlayKey, null>) => active.value === key

    return { active, open, close, isOpen }
}
