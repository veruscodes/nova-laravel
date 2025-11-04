<template>
  <Card class="isolate">
    <div
      v-show="loading"
      class="absolute inset-0 z-30 flex items-center justify-center rounded-lg bg-white dark:bg-gray-800"
    >
      <Loader class="text-gray-300" width="30" />
    </div>

    <slot />
  </Card>
</template>

<script setup>
defineProps({
  loading: { type: Boolean, default: true },
})
</script>
