<template>
  <ResourceCreate
    :resource-name="resourceName"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :via-relationship="viaRelationship"
    mode="form"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'
import ResourceCreate from '@/views/Create'

defineOptions({
  name: 'Create',
})

defineProps(
  mapProps(['resourceName', 'viaResource', 'viaResourceId', 'viaRelationship'])
)
</script>
