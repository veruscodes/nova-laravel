<template>
  <Link v-bind="{ ...$props, ...$attrs }" :class="classes">
    <slot />
  </Link>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md'].includes(value),
  },
  variant: {
    type: String,
    default: 'button',
    validator: value => ['button', 'outline'].includes(value),
  },
})

const classes = computed(() => {
  if (props.variant === 'button') {
    return {
      'shadow rounded focus:outline-none ring-primary-200 dark:ring-gray-600 focus:ring bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-800 inline-flex items-center font-bold': true,
      'px-4 h-9 text-sm': props.size === 'md',
      'px-3 h-7 text-xs': props.size === 'sm',
    }
  }

  return 'focus:outline-none ring-primary-200 dark:ring-gray-600 focus:ring-2 rounded border-2 border-gray-200 dark:border-gray-500 hover:border-primary-500 active:border-primary-400 dark:hover:border-gray-400 dark:active:border-gray-300 bg-white dark:bg-transparent text-primary-500 dark:text-gray-400 px-3 h-9 inline-flex items-center font-bold'
})
</script>
