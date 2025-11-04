<template>
  <ResourceIndex
    :field="field"
    :resource-name="field.resourceName"
    :via-resource="resourceName"
    :via-resource-id="resourceId"
    :via-relationship="field.hasManyRelationship"
    relationship-type="hasMany"
    @actionExecuted="actionExecuted"
    :load-cards="false"
    :per-page-options="field.perPageOptions || [5]"
    :should-override-meta="false"
    :collapsable="field.collapsable"
  />
</template>

<script>
import { mapProps } from '@/mixins'

export default {
  emits: ['actionExecuted'],

  props: {
    ...mapProps(['resourceName', 'resourceId', 'field']),
    resource: {},
  },

  methods: {
    /**
     * Handle the actionExecuted event and pass it up the chain.
     */
    actionExecuted() {
      this.$emit('actionExecuted')
    },
  },
}
</script>
