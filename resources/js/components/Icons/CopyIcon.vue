<template>
  <Icon :name="name" type="micro" class="!w-3 !h-3" :class="classes" />
</template>

<script setup>
import { Icon } from 'laravel-nova-ui'
import { computed } from 'vue'

const props = defineProps({
  copied: { type: Boolean, default: false },
})

const name = computed(() => {
  return props.copied === true ? 'check-circle' : 'clipboard'
})

const classes = computed(() => {
  return props.copied === true
    ? 'text-green-500'
    : 'text-gray-400 dark:text-gray-500'
})
</script>
