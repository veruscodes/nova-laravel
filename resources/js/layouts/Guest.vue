<template>
  <div>
    <slot />
  </div>
</template>

<script setup>
import { useInteractsWithErrors } from '@/composables/useInteractsWithErrors'

defineOptions({
  name: 'Guest',
})

useInteractsWithErrors()
</script>
