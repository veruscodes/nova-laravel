<template>
  <CreateForm
    mode="form"
    :resource-name="resourceName"
    :from-resource-id="resourceId"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :via-relationship="viaRelationship"
    should-override-meta
    :form-unique-id="formUniqueId"
    @resource-created="handleResourceCreated"
    @create-cancelled="cancelCreatingResource"
    @update-form-status="onUpdateFormStatus"
  />
</template>

<script setup>
import { mapProps } from '@/mixins'
import ResourceCreate from '@/views/Create'

defineOptions({
  name: 'Replicate',
  extends: ResourceCreate,
})

defineProps(mapProps(['resourceName', 'resourceId']))
</script>
