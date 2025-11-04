<template>
  <span>
    <Icon :name="name" :type="type" :class="classes" />
  </span>
</template>

<script setup>
import { Icon } from 'laravel-nova-ui'
import { computed } from 'vue'

const props = defineProps({
  value: { type: Boolean, default: false },
  nullable: { type: Boolean, default: false },
  type: { type: String, default: 'solid', required: false },
})

const name = computed(() => {
  if (props.value === true) {
    return 'check-circle'
  } else if (props.value === null && props.nullable === true) {
    return 'minus-circle'
  }

  return 'x-circle'
})

const classes = computed(() => {
  if (props.value === true) {
    return 'text-green-500'
  } else if (props.value === null && props.nullable === true) {
    return 'text-gray-200 dark:text-gray-800'
  }

  return 'text-red-500'
})
</script>
