<template>
  <ResourceIndex
    :resourceName="resourceName"
    :shouldOverrideMeta="true"
    :shouldEnableShortcut="true"
    :collapsable="false"
    :per-page-options="perPageOptions"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'

defineOptions({
  name: 'Index',
})

defineProps({
  perPageOptions: { type: Array, required: true },
  ...mapProps(['resourceName']),
})
</script>
