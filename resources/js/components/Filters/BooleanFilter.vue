<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <div class="space-y-2 mt-2">
        <BooleanOption
          v-for="option in options"
          :key="option.value"
          :resource-name="resourceName"
          :filter="filter"
          :option="option"
          label="label"
          @change="$emit('change')"
          :dusk="`${filter.uniqueKey}-${option.value}-option`"
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

    options() {
      return this.$store.getters[`${this.resourceName}/getOptionsForFilter`](
        this.filterKey
      )
    },
  },
}
</script>
