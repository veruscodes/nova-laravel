<template>
  <div
    class="mt-8 leading-normal text-xs text-gray-500 space-y-1"
    v-html="footer"
  />
</template>

<script setup>
import { computed } from 'vue'

defineOptions({
  name: 'Footer',
})

const footer = computed(() => Nova.config('footer'))
</script>
