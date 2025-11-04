<script>
import {
  autoUpdate,
  flip,
  offset,
  shift,
  size,
  useFloating,
} from '@floating-ui/vue'
import {
  cloneVNode,
  computed,
  h,
  mergeProps,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  Teleport,
  Transition,
  useId,
  watch,
  withModifiers,
} from 'vue'
import { useFocusTrap } from '@vueuse/integrations/useFocusTrap'
import { renderSlotFragments } from '../../util/renderSlotFragments'
import { useCloseOnEsc } from '../../composables/useCloseOnEsc'

export default {
  emits: ['menu-opened', 'menu-closed'],

  inheritAttrs: false,

  props: {
    offset: { type: [Number, String], default: 5 },
    placement: { type: String, default: 'bottom-start' },
    boundary: { type: String, default: 'viewPort' },
    dusk: { type: String, default: null },
    shouldCloseOnBlur: { type: Boolean, default: true },
  },

  setup(props, { slots }) {
    const menuShown = ref(false)
    const triggerRef = ref(null)
    const teleportedRef = ref(null)
    const menuRef = ref(null)

    const dropdownId = useId()

    const { activate, deactivate } = useFocusTrap(menuRef, {
      initialFocus: false,
      allowOutsideClick: true,
    })

    const usesFocusTrap = ref(true)

    const hasTrapFocus = computed(() => {
      return menuShown.value === true && usesFocusTrap.value === true
    })

    const disableModalFocusTrap = () => {
      usesFocusTrap.value = false
    }

    const enableModalFocusTrap = () => {
      usesFocusTrap.value = true
    }

    useCloseOnEsc(() => (menuShown.value = false))

    const dropdownButtonLabel = computed(
      () => `nova-ui-dropdown-button-${dropdownId}`
    )
    const menuLabel = computed(() => `nova-ui-dropdown-menu-${dropdownId}`)

    const resolvedPlacement = computed(() => {
      if (!Nova.config('rtlEnabled')) {
        return props.placement
      }

      return {
        'auto-start': 'auto-end',
        'auto-end': 'auto-start',
        'top-start': 'top-end',
        'top-end': 'top-start',
        'bottom-start': 'bottom-end',
        'bottom-end': 'bottom-start',
        'right-start': 'right-end',
        'right-end': 'right-start',
        'left-start': 'left-end',
        'left-end': 'left-start',
      }[props.placement]
    })

    const { floatingStyles } = useFloating(triggerRef, menuRef, {
      whileElementsMounted: autoUpdate,
      placement: resolvedPlacement.value,
      middleware: [offset(props.offset), flip(), shift({ padding: 5 }), size()],
    })

    watch(
      () => hasTrapFocus,
      async v => {
        await nextTick()
        v ? activate() : deactivate()
      }
    )

    onMounted(() => {
      Nova.$on('disable-focus-trap', disableModalFocusTrap)
      Nova.$on('enable-focus-trap', enableModalFocusTrap)
    })

    onBeforeUnmount(() => {
      Nova.$off('disable-focus-trap', disableModalFocusTrap)
      Nova.$off('enable-focus-trap', enableModalFocusTrap)

      usesFocusTrap.value = false
    })

    return () => {
      const children = renderSlotFragments(slots.default())
      const [trigger, ...otherChildren] = children

      const mergedProps = mergeProps({
        ...trigger.props,
        ...{
          id: dropdownButtonLabel.value,
          'aria-expanded': menuShown.value === true ? 'true' : 'false',
          'aria-haspopup': 'true',
          'aria-controls': menuLabel.value,
          onClick: withModifiers(() => {
            menuShown.value = !menuShown.value
          }, ['stop']),
        },
      })

      const cloned = cloneVNode(trigger, mergedProps)

      // Explicitly override props starting with `on`.
      // It seems cloneVNode from Vue doesn't like overriding `onXXX` props. So
      // we have to do it manually.
      for (const prop in mergedProps) {
        if (prop.startsWith('on')) {
          cloned.props ||= {}
          cloned.props[prop] = mergedProps[prop]
        }
      }

      return h('div', { dusk: props.dusk }, [
        h('span', { ref: triggerRef }, cloned),
        h(
          Teleport,
          { to: 'body' },
          h(
            Transition,
            {
              enterActiveClass: 'transition duration-0 ease-out',
              enterFromClass: 'opacity-0',
              enterToClass: 'opacity-100',
              leaveActiveClass: 'transition duration-300 ease-in',
              leaveFromClass: 'opacity-100',
              leaveToClass: 'opacity-0',
            },
            () => [
              menuShown.value
                ? h(
                    'div',
                    {
                      ref: teleportedRef,
                      dusk: 'dropdown-teleported',
                    },
                    [
                      h(
                        'div',
                        {
                          ref: menuRef,
                          id: menuLabel.value,
                          'aria-labelledby': dropdownButtonLabel.value,
                          tabindex: '0',
                          class: 'relative z-[70]',
                          style: floatingStyles.value,
                          'data-menu-open': menuShown.value,
                          dusk: 'dropdown-menu',
                          onClick: () =>
                            props.shouldCloseOnBlur
                              ? (menuShown.value = false)
                              : null,
                        },
                        slots.menu()
                      ),
                      h('div', {
                        class: 'z-[69] fixed inset-0',
                        dusk: 'dropdown-overlay',
                        onClick: () => (menuShown.value = false),
                      }),
                    ]
                  )
                : null,
            ]
          )
        ),
      ])
    }
  },
}
</script>
