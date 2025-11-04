<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <SelectControl
        v-model="value"
        :options="field.morphToTypes"
        label="singularLabel"
        :dusk="filter.uniqueKey"
      >
        <option value="" :selected="!filledValue">&mdash;</option>
      </SelectControl>
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce'
import filled from '@/util/filled'

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

  data: () => ({
    value: null,
    debouncedEventEmitter: null,
  }),

  created() {
    this.debouncedEventEmitter = debounce(() => this.emitFilterChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.setCurrentFilterValue)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.setCurrentFilterValue)
  },

  watch: {
    value() {
      this.debouncedEventEmitter()
    },
  },

  methods: {
    setCurrentFilterValue() {
      let selectedOption = this.field.morphToTypes.find(
        o => o.type === this.filter.currentValue
      )

      this.value = selectedOption != null ? selectedOption.value : ''
    },

    emitFilterChange() {
      let selectedOption = this.field.morphToTypes.find(
        o => o.value === this.value
      )

      this.$emit('change', {
        filterClass: this.filterKey,
        value: selectedOption != null ? selectedOption.type : '',
      })
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

    hasMorphToTypes() {
      return this.field.morphToTypes.length > 0
    },

    filledValue() {
      return filled(this.value)
    },
  },
}
</script>
