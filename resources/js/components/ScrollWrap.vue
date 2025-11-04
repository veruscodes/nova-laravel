<template>
  <div class="scroll-wrap overflow-x-hidden overflow-y-auto" :style="styles">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  height: { type: Number, default: 288 },
})

const styles = computed(() => {
  return {
    maxHeight: `${props.height}px`,
  }
})
</script>
