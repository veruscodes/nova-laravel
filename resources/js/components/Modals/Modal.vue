<template>
  <teleport to="body">
    <template v-if="show">
      <div
        v-bind="defaultAttributes"
        class="modal fixed inset-0 z-[60]"
        :class="{
          'px-3 md:px-0 py-3 md:py-6 overflow-x-hidden overflow-y-auto':
            modalStyle === 'window',
          'h-full': modalStyle === 'fullscreen',
        }"
        :role="role"
        :data-modal-open="show"
        :aria-modal="show"
      >
        <div
          class="@container/modal relative mx-auto z-20"
          :class="contentClasses"
          ref="modalContent"
        >
          <slot />
        </div>
      </div>

      <div
        class="fixed inset-0 z-[55] bg-gray-500/75 dark:bg-gray-900/75"
        dusk="modal-backdrop"
      />
    </template>
  </teleport>
</template>

<script setup>
import { useStore } from 'vuex'
import filter from 'lodash/filter'
import omit from 'lodash/omit'
import { useFocusTrap } from '@vueuse/integrations/useFocusTrap'
import { useEventListener } from '@vueuse/core'
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  useAttrs,
  useTemplateRef,
  watch,
} from 'vue'

const modalContentRef = useTemplateRef('modalContent')

const attrs = useAttrs()

const emitter = defineEmits(['showing', 'closing', 'close-via-escape'])

defineOptions({ inheritAttrs: false })

const props = defineProps({
  show: { type: Boolean, default: false },
  size: {
    type: String,
    default: 'xl',
    validator: v =>
      [
        'sm',
        'md',
        'lg',
        'xl',
        '2xl',
        '3xl',
        '4xl',
        '5xl',
        '6xl',
        '7xl',
      ].includes(v),
  },
  modalStyle: { type: String, default: 'window' },
  role: { type: String, default: 'dialog' },
  useFocusTrap: { type: Boolean, default: true },
})

const usesFocusTrap = ref(true)

const hasTrapFocus = computed(() => {
  return props.useFocusTrap && usesFocusTrap.value === true
})

const { activate, deactivate } = useFocusTrap(modalContentRef, {
  immediate: false,
  allowOutsideClick: true,
  escapeDeactivates: false,
})

watch(
  () => props.show,
  v => handleVisibilityChange(v)
)

watch(hasTrapFocus, enable => {
  try {
    if (enable) {
      nextTick(() => activate())
    } else {
      deactivate()
    }
  } catch (e) {
    //
  }
})

useEventListener(document, 'keydown', e => {
  if (e.key === 'Escape' && props.show === true) {
    emitter('close-via-escape', e)
  }
})

const disableModalFocusTrap = () => {
  usesFocusTrap.value = false
}

const enableModalFocusTrap = () => {
  usesFocusTrap.value = true
}

onMounted(() => {
  Nova.$on('disable-focus-trap', disableModalFocusTrap)
  Nova.$on('enable-focus-trap', enableModalFocusTrap)

  if (props.show === true) handleVisibilityChange(true)
})

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-hidden')
  Nova.resumeShortcuts()

  Nova.$off('disable-focus-trap', disableModalFocusTrap)
  Nova.$off('enable-focus-trap', enableModalFocusTrap)

  usesFocusTrap.value = false
})

const store = useStore()

async function handleVisibilityChange(showing) {
  if (showing === true) {
    emitter('showing')
    document.body.classList.add('overflow-hidden')
    Nova.pauseShortcuts()

    usesFocusTrap.value = true
  } else {
    usesFocusTrap.value = false

    emitter('closing')
    document.body.classList.remove('overflow-hidden')
    Nova.resumeShortcuts()
  }

  store.commit('allowLeavingModal')
}

const defaultAttributes = computed(() => {
  return omit(attrs, ['class'])
})

const sizeClasses = computed(() => {
  return {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
  }
})

const contentClasses = computed(() => {
  let windowClasses = props.modalStyle === 'window' ? sizeClasses.value : {}

  return filter([
    windowClasses[props.size] ?? null,
    props.modalStyle === 'fullscreen' ? 'h-full' : '',
    attrs.class,
  ])
})
</script>
