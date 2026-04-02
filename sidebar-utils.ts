import type { ComputedRef, InjectionKey, Ref } from 'vue'
import { computed, getCurrentInstance, inject, ref } from 'vue'

export const SIDEBAR_WIDTH_MOBILE = '18rem'
export const SIDEBAR_WIDTH = '16rem'
export const SIDEBAR_WIDTH_ICON = '3rem'
export const SIDEBAR_COOKIE_NAME = 'sidebar:state'
export const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 7 // 7 days
export const SIDEBAR_KEYBOARD_SHORTCUT = 'b'

export interface SidebarContext {
  state: ComputedRef<'expanded' | 'collapsed'>
  open: Ref<boolean>
  setOpen: (value: boolean) => void
  isMobile: Ref<boolean>
  openMobile: Ref<boolean>
  setOpenMobile: (value: boolean) => void
  toggleSidebar: () => void
}

const SIDEBAR_INJECTION_KEY: InjectionKey<SidebarContext> = Symbol('sidebar')

export function provideSidebarContext(context: SidebarContext) {
  const instance = getCurrentInstance()
  if (instance) (instance as any).provides[SIDEBAR_INJECTION_KEY as any] = context
}

const defaultContext: SidebarContext = (() => {
  const isMobile = ref(false)
  const openMobile = ref(false)
  const open = ref(true)
  const state = computed(() => (open.value ? 'expanded' : 'collapsed') as 'expanded' | 'collapsed')
  return {
    state,
    open,
    setOpen: (v: boolean) => { open.value = v },
    isMobile,
    openMobile,
    setOpenMobile: (v: boolean) => { openMobile.value = v },
    toggleSidebar: () => { isMobile.value ? (openMobile.value = !openMobile.value) : (open.value = !open.value) },
  }
})()

export function useSidebar(): SidebarContext {
  return inject(SIDEBAR_INJECTION_KEY) ?? defaultContext
}
