<template>
  <ResourceLens
    :resourceName="resourceName"
    :lens="lens"
    :searchable="searchable"
    :per-page-options="perPageOptions"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'
import ResourceLens from '@/views/Lens'

defineOptions({
  name: 'Lens',
})

defineProps({
  lens: { type: String, required: true },
  searchable: { type: Boolean, default: false },
  perPageOptions: { type: Array, required: true },
  ...mapProps(['resourceName']),
})
</script>
