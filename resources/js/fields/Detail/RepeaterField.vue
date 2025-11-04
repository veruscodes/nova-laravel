<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <div v-if="fieldHasValue" class="space-y-4" :dusk="fieldAttribute">
        <div
          v-for="item in field.value"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded divide-y divide-gray-200 dark:divide-gray-700"
        >
          <div
            class="grid grid-cols-full divide-y divide-gray-100 dark:divide-gray-700 px-6"
          >
            <div v-for="(field, fieldIndex) in item.fields">
              <component
                :key="index"
                :index="index"
                :is="resolveComponentName(field)"
                :resource-name="resourceName"
                :resource-id="resourceId"
                :resource="resource"
                :field="field"
                @actionExecuted="actionExecuted"
              />
            </div>
          </div>
        </div>
      </div>
      <p v-else>&mdash;</p>
    </template>
  </PanelItem>
</template>

<script>
import { BehavesAsPanel, FieldValue } from '@/mixins'

export default {
  mixins: [BehavesAsPanel, FieldValue],

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  methods: {
    /**
     * Resolve the component name.
     */
    resolveComponentName(field) {
      return field.prefixComponent
        ? 'detail-' + field.component
        : field.component
    },
  },

  computed: {
    fieldHasValue() {
      return this.field.value.length > 0
    },
  },
}
</script>
