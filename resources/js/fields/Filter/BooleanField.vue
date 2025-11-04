<template>
  <FilterContainer>
    <div>
      <label class="block">{{ filter.name }}</label>

      <button type="button" @click="handleChange" class="p-0 m-0">
        <IconBoolean
          class="mt-2"
          :value="value"
          :nullable="true"
          :dusk="filter.uniqueKey"
        />
      </button>
    </div>
  </FilterContainer>
</template>

<script>
export default {
  emits: ['change'],

  props: {
    resourceName: { type: String, required: true },
    filterKey: { type: String, required: true },
    lens: String,
  },

  methods: {
    handleChange() {
      let value = this.nextValue(this.value)

      this.$emit('change', {
        filterClass: this.filterKey,
        value: value ?? '',
      })
    },

    nextValue(value) {
      if (value === true) {
        return false
      } else if (value === false) {
        return null
      }

      return true
    },
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

    value() {
      let value = this.filter.currentValue

      return value === true || value === false ? value : null
    },
  },
}
</script>
