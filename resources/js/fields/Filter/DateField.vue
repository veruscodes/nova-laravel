<template>
  <FilterContainer>
    <template #filter>
      <label class="block">
        <span class="uppercase text-xs font-bold tracking-wide">{{
          `${filter.name} - ${__('From')}`
        }}</span>
        <input
          ref="startField"
          v-model="startValue"
          v-bind="startExtraAttributes"
          class="w-full flex form-control form-input form-control-bordered"
          :dusk="`${filter.uniqueKey}-range-start`"
        />
      </label>

      <label class="block mt-2">
        <span class="uppercase text-xs font-bold tracking-wide">{{
          `${filter.name} - ${__('To')}`
        }}</span>
        <input
          ref="endField"
          v-model="endValue"
          v-bind="endExtraAttributes"
          class="w-full flex form-control form-input form-control-bordered"
          :dusk="`${filter.uniqueKey}-range-end`"
        />
      </label>
    </template>
  </FilterContainer>
</template>

<script>
import { DateTime } from 'luxon'
import debounce from 'lodash/debounce'
import omit from 'lodash/omit'
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
    startValue: null,
    endValue: null,

    debouncedEventEmitter: null,
  }),

  created() {
    this.debouncedEventEmitter = debounce(() => this.emitFilterChange(), 500)
    this.setCurrentFilterValue()
  },

  mounted() {
    Nova.$on('filter-reset', this.handleFilterReset)
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.handleFilterReset)
  },

  watch: {
    startValue() {
      this.debouncedEventEmitter()
    },

    endValue() {
      this.debouncedEventEmitter()
    },
  },

  methods: {
    setCurrentFilterValue() {
      let [startValue, endValue] = this.filter.currentValue || [null, null]

      this.startValue = filled(startValue)
        ? this.fromDateTimeISO(startValue).toISODate()
        : null
      this.endValue = filled(endValue)
        ? this.fromDateTimeISO(endValue).toISODate()
        : null
    },

    validateFilter(startValue, endValue) {
      startValue = filled(startValue) ? this.toDateTimeISO(startValue) : null
      endValue = filled(endValue) ? this.toDateTimeISO(endValue) : null

      return [startValue, endValue]
    },

    emitFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.validateFilter(this.startValue, this.endValue),
      })
    },

    handleFilterReset() {
      this.$refs.startField.value = ''
      this.$refs.endField.value = ''

      this.setCurrentFilterValue()
    },

    fromDateTimeISO(value) {
      return DateTime.fromISO(value)
    },

    toDateTimeISO(value) {
      return DateTime.fromISO(value).toISODate()
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

    startExtraAttributes() {
      const attrs = omit(this.field.extraAttributes, ['readonly'])

      return {
        // Leave the default attributes even though we can now specify
        // whatever attributes we like because the old number field still
        // uses the old field attributes
        type: this.field.type || 'date',
        placeholder: this.__('Start'),
        ...attrs,
      }
    },

    endExtraAttributes() {
      const attrs = omit(this.field.extraAttributes, ['readonly'])

      return {
        // Leave the default attributes even though we can now specify
        // whatever attributes we like because the old number field still
        // uses the old field attributes
        type: this.field.type || 'date',
        placeholder: this.__('End'),
        ...attrs,
      }
    },
  },
}
</script>
