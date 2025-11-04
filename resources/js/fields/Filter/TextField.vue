<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <input
        class="w-full form-control form-input form-control-bordered"
        @input="handleChange"
        :value="value"
        :id="field.uniqueKey"
        :dusk="`${field.uniqueKey}-filter`"
        v-bind="extraAttributes"
        :list="`${field.uniqueKey}-list`"
      />

      <datalist
        v-if="field.suggestions && field.suggestions.length > 0"
        :id="`${field.uniqueKey}-list`"
      >
        <option
          :key="suggestion"
          v-for="suggestion in field.suggestions"
          :value="suggestion"
        />
      </datalist>
    </template>
  </FilterContainer>
</template>

<script>
import debounce from 'lodash/debounce'
import omit from 'lodash/omit'

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
    this.debouncedEventEmitter = debounce(() => this.emitChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.setCurrentFilterValue)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.setCurrentFilterValue)
  },

  methods: {
    setCurrentFilterValue() {
      this.value = this.filter.currentValue
    },

    handleChange(e) {
      this.value = e.target.value
      this.debouncedEventEmitter()
    },

    emitChange() {
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

    field() {
      return this.filter.field
    },

    extraAttributes() {
      const attrs = omit(this.field.extraAttributes, ['readonly'])

      return {
        // Leave the default attributes even though we can now specify
        // whatever attributes we like because the old number field still
        // uses the old field attributes
        type: this.field.type || 'text',
        min: this.field.min,
        max: this.field.max,
        step: this.field.step,
        pattern: this.field.pattern,
        placeholder: this.field.placeholder || this.field.name,
        ...attrs,
      }
    },
  },
}
</script>
