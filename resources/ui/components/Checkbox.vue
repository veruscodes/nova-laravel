<script setup lang="ts">
import { computed, ref } from 'vue'

type CheckboxState = 'checked' | 'unchecked' | 'indeterminate'

type CheckboxProps = {
  modelValue?: boolean
  indeterminate?: boolean
  disabled?: boolean
  label?: string
}

const props = withDefaults(defineProps<CheckboxProps>(), {
  modelValue: false,
  indeterminate: false,
  disabled: false,
})

const emit = defineEmits(['update:modelValue', 'change'])

const focused = ref(false)
const theCheckbox = ref<HTMLInputElement | null>(null)

const checkedState = computed<CheckboxState>(() => {
  return props.indeterminate
    ? 'indeterminate'
    : props.modelValue
    ? 'checked'
    : 'unchecked'
})

const handleChange = (event: Event) => {
  if (props.disabled) return

  emit('change', !props.modelValue)
  emit('update:modelValue', !props.modelValue)
}

const labelProps = computed(() => {
  const { label, disabled } = props
  return {
    'aria-label': label,
    'aria-disabled': disabled,
    'data-focus': !props.disabled && focused.value,
    'data-state': checkedState.value,
    ':aria-checked': props.indeterminate ? 'mixed' : props.modelValue,
    checkedValue: props.modelValue,
    checkedState: checkedState.value,
  }
})

const labelComponent = computed(() => {
  return 'div'
})

const focus = () => {
  focused.value = true
  theCheckbox.value?.focus()
}

defineExpose({ focus })
</script>

<template>
  <component
    :is="labelComponent"
    @click="handleChange"
    @keydown.space.prevent="handleChange"
    @focus="focused = true"
    @blur="focused = false"
    :tabindex="disabled ? '-1' : 0"
    class="group inline-flex shrink-0 items-center gap-2 focus:outline-none"
    role="checkbox"
    v-bind="labelProps"
    ref="theCheckbox"
  >
    <span
      class="relative inline-flex h-4 w-4 items-center justify-center rounded border border-gray-950/20 bg-white text-white ring-offset-2 group-data-[state=checked]:border-primary-500 group-data-[state=indeterminate]:border-primary-500 group-data-[state=checked]:bg-primary-500 group-data-[state=indeterminate]:bg-primary-500 group-data-[focus=true]:ring-2 group-data-[focus=true]:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 group-data-[focus]:dark:ring-offset-gray-950"
      :class="{
        'bg-gray-200 opacity-50 dark:!border-gray-500 dark:!bg-gray-600':
          disabled,
      }"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-3 w-3"
        viewBox="0 0 12 12"
      >
        <g fill="currentColor" fill-rule="nonzero">
          <path
            class="group-data-[state=checked]:opacity-0 group-data-[state=indeterminate]:opacity-100 group-data-[state=unchecked]:opacity-0"
            d="M9.999 6a1 1 0 0 1-.883.993L8.999 7h-6a1 1 0 0 1-.117-1.993L2.999 5h6a1 1 0 0 1 1 1Z"
          />
          <path
            class="group-data-[state=checked]:opacity-100 group-data-[state=indeterminate]:opacity-0 group-data-[state=unchecked]:opacity-0"
            d="M3.708 5.293a1 1 0 1 0-1.415 1.416l2 2a1 1 0 0 0 1.414 0l4-4a1 1 0 0 0-1.414-1.416L5.001 6.587 3.708 5.293Z"
          />
        </g>
      </svg>
    </span>
    <span v-if="label || $slots.default">
      <slot>{{ label }}</slot>
    </span>
  </component>
</template>
