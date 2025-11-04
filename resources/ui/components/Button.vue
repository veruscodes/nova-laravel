<script setup lang="ts">
import Icon from './Icon.vue'
import Loader from './Loader.vue'
import { computed } from 'vue'
import type {
  ButtonSize,
  ButtonPadding,
  ButtonVariant,
  IconName,
  IconType,
} from './types'
import { useButtonStyles } from './composables/useButtonStyles'

type ButtonState = 'default' | 'danger'

type ButtonProps = {
  as?: 'button' | 'div' | 'span'
  size?: ButtonSize
  label?: string | number
  variant?: ButtonVariant
  state?: ButtonState
  padding?: ButtonPadding
  loading?: boolean
  disabled?: boolean
  icon?: IconName
  leadingIcon?: IconName
  trailingIcon?: IconName
}

const { base, baseAs, variants, disabled, validateSize, validatePadding } =
  useButtonStyles()

const props = withDefaults(defineProps<ButtonProps>(), {
  as: 'button',
  size: 'large',
  variant: 'solid',
  state: 'default',
  padding: 'default',
  loading: false,
  disabled: false,
})

const buttonSize = computed(() => props.size)
const buttonPadding = computed(() => props.padding)

validateSize(props.variant, buttonSize.value as ButtonSize)
validatePadding(props.variant, buttonPadding.value as ButtonPadding)

const shouldBeDisabled = computed(() => props.disabled || props.loading)

const classes = computed(() => {
  return [
    base,
    props.as ? baseAs : '',
    props.disabled && !props.loading && disabled,
    variants[props.variant]?.class || '',
    variants[props.variant]?.sizes[buttonSize.value] || '',
    variants[props.variant]?.padding[props.padding]?.[buttonSize.value] || '',
    variants[props.variant]?.states[props.state]?.[buttonSize.value] || '',
  ]
})

const loaderSize = computed(() => {
  return variants[props.variant]?.loaderSize[buttonSize.value]
})

const iconType = computed<IconType>(() => {
  if (buttonSize.value === 'large') {
    return 'outline'
  }

  if (buttonSize.value === 'small') {
    return 'micro'
  }

  return 'mini'
})

const trailingIconType = computed<IconType>(() => 'mini')
</script>

<template>
  <component
    :is="as"
    :type="as === 'button' ? 'button' : null"
    :class="classes"
    :disabled="shouldBeDisabled"
  >
    <span
      class="flex items-center gap-1"
      :class="{
        invisible: loading,
      }"
    >
      <span v-if="leadingIcon">
        <Icon :name="leadingIcon" :type="trailingIconType" />
      </span>

      <span v-if="icon">
        <Icon :name="icon" :type="iconType" />
      </span>

      <slot>
        {{ label }}
      </slot>

      <span v-if="trailingIcon">
        <Icon :name="trailingIcon" :type="trailingIconType" />
      </span>
    </span>

    <span
      v-if="loading"
      class="absolute"
      style="top: 50%; left: 50%; transform: translate(-50%, -50%)"
    >
      <Loader :width="loaderSize" />
    </span>
  </component>
</template>
