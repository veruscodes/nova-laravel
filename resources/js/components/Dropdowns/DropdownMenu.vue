<template>
  <div
    :style="styles"
    class="select-none overflow-hidden bg-white dark:bg-gray-900 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700"
    :class="{ 'max-w-sm lg:max-w-lg': width === 'auto' }"
  >
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  width: { type: [Number, String], default: 120 },
})

const styles = computed(() => {
  return {
    width: props.width === 'auto' ? 'auto' : `${props.width}px`,
  }
})
</script>
