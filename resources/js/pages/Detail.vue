<template>
  <ResourceDetail
    :resourceName="resourceName"
    :resourceId="resourceId"
    :shouldOverrideMeta="true"
    :shouldEnableShortcut="true"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'

defineOptions({
  name: 'Detail',
})

defineProps(mapProps(['resourceName', 'resourceId']))
</script>
