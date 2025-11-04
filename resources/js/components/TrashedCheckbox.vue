<template>
  <div>
    <CheckboxWithLabel
      v-bind="{ ...$attrs }"
      :dusk="`${resourceName}-with-trashed-checkbox`"
      :checked="withTrashed"
      @input="$emit('input')"
    >
      <span>{{ __('With Trashed') }}</span>
    </CheckboxWithLabel>
  </div>
</template>

<script setup>
defineOptions({
  inheritAttrs: false,
})

defineEmits(['input'])

defineProps({
  resourceName: String,
  withTrashed: Boolean,
})
</script>
