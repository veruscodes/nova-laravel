<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <!-- Search Input -->
      <SearchInput
        v-if="isSearchable"
        ref="searchable"
        v-model="value"
        @input="performSearch"
        @clear="clearSelection"
        :options="filteredOptions"
        :clearable="true"
        trackBy="value"
        mode="modal"
        class="w-full"
        :dusk="`${filter.uniqueKey}-search-input`"
      >
        <!-- The Selected Option Slot -->
        <div v-if="selectedOption" class="flex items-center">
          {{ selectedOption.label }}
        </div>

        <!-- Options List Slot -->
        <template #option="{ option, selected }">
          <div
            class="flex items-center text-sm font-semibold leading-5"
            :class="{ 'text-white': selected }"
          >
            {{ option.label }}
          </div>
        </template>
      </SearchInput>

      <!-- Select Input Field -->
      <SelectControl
        v-else
        v-model="value"
        :options="field?.options ?? []"
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
    search: '',

    value: null,
    debouncedHandleChange: null,
  }),

  mounted() {
    Nova.$on('filter-reset', this.handleFilterReset)
  },

  created() {
    this.debouncedHandleChange = debounce(() => this.handleFilterChange(), 500)
    this.value = this.filter.currentValue
  },

  beforeUnmount() {
    Nova.$off('filter-reset', this.handleFilterReset)
  },

  watch: {
    value() {
      this.debouncedHandleChange()
    },
  },

  methods: {
    /**
     * Set the search string to be used to filter the select field.
     */
    performSearch(event) {
      this.search = event
    },

    /**
     * Clear the current selection for the field.
     */
    clearSelection() {
      this.value = ''

      if (this.$refs.searchable) {
        this.$refs.searchable.close()
      }
    },

    handleFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.value ?? '',
      })
    },

    handleFilterReset() {
      if (this.filter.currentValue !== '') {
        this.setCurrentFilterValue()
        return
      }

      this.clearSelection()
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

    /**
     * Determine if the related resources is searchable
     */
    isSearchable() {
      return this.field.searchable
    },

    /**
     * Return the field options filtered by the search string.
     */
    filteredOptions() {
      return this.field.options.filter(option => {
        return (
          option.label
            .toString()
            .toLowerCase()
            .indexOf(this.search.toLowerCase()) > -1
        )
      })
    },

    selectedOption() {
      return this.field.options.find(
        o => this.value === o.value || this.value === o.value.toString()
      )
    },

    filledValue() {
      return filled(this.value)
    },
  },
}
</script>
