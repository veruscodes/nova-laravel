<template>
  <UpdateAttachedResource
    :resource-name="resourceName"
    :resource-id="resourceId"
    :related-resource-name="relatedResourceName"
    :related-resource-id="relatedResourceId"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :parent-resource="parentResource"
    :via-relationship="viaRelationship"
    :via-pivot-id="viaPivotId"
    :polymorphic="polymorphic"
    :form-unique-id="formUniqueId"
  />
</template>

<script setup>
import { ref } from 'vue'
import { uid } from 'uid/single'

defineOptions({
  name: 'UpdateAttached',
})

defineProps({
  resourceName: { type: String, required: true },
  resourceId: { required: true },
  relatedResourceName: { type: String, required: true },
  relatedResourceId: { required: true },
  viaResource: { default: '' },
  viaResourceId: { default: '' },
  parentResource: { type: Object },
  viaRelationship: { default: '' },
  viaPivotId: { default: null },
  polymorphic: { default: false },
})

const formUniqueId = uid()
</script>
