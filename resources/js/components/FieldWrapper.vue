<template>
  <div class="flex flex-col" :class="{ 'md:flex-row': !stacked }">
    <slot />
  </div>
</template>
<script setup>
defineProps({
  stacked: { type: Boolean, default: false },
})
</script>
