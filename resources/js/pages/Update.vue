<template>
  <ResourceUpdate
    :resource-name="resourceName"
    :resource-id="resourceId"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :via-relationship="viaRelationship"
    :form-unique-id="formUniqueId"
  />
</template>

<script setup>
import ResourceUpdate from '@/views/Update'
import { mapProps } from '@/mixins'
import { uid } from 'uid/single'

defineOptions({
  name: 'Update',
})

defineProps(
  mapProps([
    'resourceName',
    'resourceId',
    'viaResource',
    'viaResourceId',
    'viaRelationship',
  ])
)

const formUniqueId = uid()
</script>
