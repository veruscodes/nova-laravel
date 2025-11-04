<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <input
        ref="dateField"
        @change="handleChange"
        type="date"
        name="date-filter"
        :value="value"
        autocomplete="off"
        class="w-full h-8 flex form-control form-input form-control-bordered text-xs"
        :placeholder="placeholder"
        :dusk="filter.uniqueKey"
      />
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

  methods: {
    handleChange(e) {
      let value = e.target.value

      this.$store.commit(`${this.resourceName}/updateFilterState`, {
        filterClass: this.filterKey,
        value,
      })

      this.$emit('change')
    },
  },

  computed: {
    placeholder() {
      return this.filter.placeholder || this.__('Choose date')
    },

    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
        this.filterKey
      )
    },

    value() {
      return this.filter.currentValue
    },

    options() {
      return this.$store.getters[`${this.resourceName}/getOptionsForFilter`](
        this.filterKey
      )
    },
  },
}
</script>
