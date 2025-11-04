<template>
  <label :for="labelFor" class="inline-block leading-tight">
    <slot />
  </label>
</template>

<script setup>
defineProps({
  labelFor: { type: String, required: false },
})
</script>
