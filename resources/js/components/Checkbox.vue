<template>
  <input
    type="checkbox"
    class="checkbox"
    :disabled="disabled"
    :checked="checked"
    @change="$emit('input', $event)"
    @click.stop
  />
</template>

<script setup>
defineEmits(['input'])

defineProps({
  checked: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})
</script>
