<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <FormKeyValueTable
        v-if="theData.length > 0"
        :edit-mode="false"
        class="overflow-hidden"
      >
        <FormKeyValueHeader
          :key-label="field.keyLabel"
          :value-label="field.valueLabel"
        />

        <div
          class="bg-gray-50 dark:bg-gray-700 overflow-hidden key-value-items"
        >
          <FormKeyValueItem
            v-for="(item, index) in theData"
            :index="index"
            :item="item"
            :edit-mode="false"
            :key="item.key"
          />
        </div>
      </FormKeyValueTable>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  data: () => ({ theData: [] }),

  created() {
    this.theData = Object.entries(this.field.value || {}).map(
      ([key, value]) => ({
        key: `${key}`,
        value,
      })
    )
  },
}
</script>
