<template>
  <button
    type="button"
    class="space-x-1 cursor-pointer focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 focus:ring-offset-4 dark:focus:ring-offset-gray-800 rounded-lg mx-auto text-primary-500 font-bold link-default px-3 rounded-b-lg flex items-center"
  >
    <Icon :name="iconType" class="inline-block" />
    <span>
      <slot />
    </span>
  </button>
</template>

<script setup>
import { Icon } from 'laravel-nova-ui'

defineProps({
  iconType: { type: String, default: 'plus-circle' },
})
</script>
