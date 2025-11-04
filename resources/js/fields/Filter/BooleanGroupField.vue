<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <div class="space-y-2">
        <IconBooleanOption
          :dusk="`${filter.uniqueKey}-${option.value}-option`"
          :resource-name="resourceName"
          :key="option.value"
          v-for="option in field.options"
          :filter="filter"
          :option="option"
          @change="$emit('change')"
          label="label"
        />
      </div>
    </template>
  </FilterContainer>
</template>

<script>
export default {
  emits: ['change'],

  props: {
    resourceName: {
      type: String,
      required: true,
    },
    filterKey: {
      type: String,
      required: true,
    },
    lens: String,
  },

  computed: {
    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
        this.filterKey
      )
    },

    field() {
      return this.filter.field
    },
  },
}
</script>
