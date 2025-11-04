<script setup lang="ts">
import { computed } from 'vue'
import Icon from './Icon.vue'
import { useBadgeStyles } from './composables/useBadgeStyles'

type BadgeVariants = 'info' | 'warning' | 'success' | 'danger'

type BadgeStyles = 'pill' | 'brick'

type BadgeProps = {
  icon?: string
  rounded?: boolean
  variant?: BadgeVariants
  type?: BadgeStyles
  extraClasses?: string | string[]
  removable?: boolean
}

const props = withDefaults(defineProps<BadgeProps>(), {
  type: 'pill',
  variant: 'info',
  removable: false,
})

const { common, variants, types } = useBadgeStyles()

const wrapperClasses = computed(() => {
  // prettier-ignore
  return [
    common.wrapper,
    variants.wrapper[props.variant],
    types[props.type],
  ]
})

const buttonClasses = computed(() => {
  // prettier-ignore
  return [
    variants.button[props.variant],
  ]
})

const buttonStrokeClasses = computed(() => {
  // prettier-ignore
  return [
    variants.buttonStroke[props.variant],
  ]
})
</script>

<template>
  <span :class="wrapperClasses">
    <span v-if="icon" class="-ml-1">
      <Icon :name="icon" type="mini" />
    </span>

    <span>
      <slot />
    </span>

    <button
      v-if="props.removable"
      type="button"
      class="group relative -mr-1 h-3.5 w-3.5 rounded-sm"
      :class="buttonClasses"
    >
      <span class="sr-only">Remove</span>
      <svg viewBox="0 0 14 14" class="h-3.5 w-3.5" :class="buttonStrokeClasses">
        <path d="M4 4l6 6m0-6l-6 6" />
      </svg>
      <span class="absolute -inset-1" />
    </button>
  </span>
</template>
