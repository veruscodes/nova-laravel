<template>
  <DashboardView :name="name" />
</template>

<script setup>
import DashboardView from '@/views/Dashboard'

defineOptions({
  name: 'Dashboard',
})

defineProps({
  name: { type: String, required: false, default: 'main' },
})
</script>
