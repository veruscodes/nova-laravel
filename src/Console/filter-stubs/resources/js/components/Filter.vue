<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <SelectControl
        :selected="value"
        @update:selected="value = $event"
        :options="filter.options"
        size="sm"
        label="label"
        class="w-full block"
        :dusk="filter.uniqueKey"
      >
        <option value="" :selected="value === ''">{{ __('&mdash;') }}</option>
      </SelectControl>
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce'

export default {
  emits: ['change'],

  props: {
    resourceName: { type: String, required: true },
    filterKey: { type: String, required: true },
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

  watch: {
    value() {
      this.debouncedHandleChange()
    },
  },

  methods: {
    setCurrentFilterValue() {
      this.value = this.filter.currentValue
    },

    emitFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.value,
      })
    },
  },

  computed: {
    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
        this.filterKey
      )
    },
  },
}
</script>
