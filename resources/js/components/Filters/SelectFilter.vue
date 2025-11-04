<template>
  <FilterContainer>
    <span>{{ filter.name }}</span>

    <template #filter>
      <SearchInput
        v-if="isSearchable"
        ref="searchable"
        v-model="value"
        @input="performSearch"
        @clear="handleClearSearchInput"
        @shown="handleShowingActiveSearchInput"
        :options="availableOptions"
        :clearable="true"
        trackBy="value"
        mode="modal"
        class="w-full"
        :dusk="`${filter.uniqueKey}-search-input`"
      >
        <div v-if="selectedOption" class="flex items-center">
          {{ selectedOption.label }}
        </div>

        <template #option="{ selected, option }">
          <div class="flex items-center">
            <div class="flex-auto">
              <div
                class="text-sm font-semibold leading-normal"
                :class="{ 'text-white dark:text-gray-900': selected }"
              >
                {{ option.label }}
              </div>
            </div>
          </div>
        </template>
      </SearchInput>

      <SelectControl
        v-else-if="options.length > 0"
        v-model="value"
        :options="options"
        size="sm"
        label="label"
        class="w-full block"
        :dusk="filter.uniqueKey"
      >
        <option value="" :selected="!filledValue">{{ __('&mdash;') }}</option>
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
    search: '',
    availableOptions: [],
  }),

  created() {
    this.debouncedEventEmitter = debounce(() => this.emitFilterChange(), 500)
    this.initializeComponent()

    Nova.$on('filter-active', this.handleClosingInactiveSearchInputs)
  },

  mounted() {
    Nova.$on('filter-reset', this.handleFilterReset)
  },

  beforeUnmount() {
    Nova.$off('filter-active', this.handleClosingInactiveSearchInputs)
    Nova.$off('filter-reset', this.handleFilterReset)
  },

  watch: {
    value() {
      this.debouncedEventEmitter()
    },
  },

  methods: {
    initializeComponent() {
      if (this.filter.currentValue) {
        this.setCurrentFilterValue()
      }
    },

    setCurrentFilterValue() {
      this.value = this.filter.currentValue
    },

    emitFilterChange() {
      this.$store.commit(`${this.resourceName}/updateFilterState`, {
        filterClass: this.filterKey,
        value: this.value ?? '',
      })

      this.$emit('change')
    },

    handleShowingActiveSearchInput() {
      Nova.$emit('filter-active', this.filterKey)
    },

    closeSearchableRef() {
      if (this.$refs.searchable) {
        this.$refs.searchable.close()
      }
    },

    handleClosingInactiveSearchInputs(key) {
      if (key !== this.filterKey) {
        this.closeSearchableRef()
      }
    },

    handleClearSearchInput() {
      this.clearSelection()
    },

    handleFilterReset() {
      if (this.filter.currentValue != '') {
        this.setCurrentFilterValue()
        return
      }

      this.clearSelection()
      this.closeSearchableRef()

      this.initializeComponent()
    },

    /**
     * Clear the selected option and searchOptions
     */
    clearSelection() {
      this.value = null
      this.availableOptions = []
    },

    /**
     * Perform a search to get the relatable resources.
     */
    performSearch(search) {
      this.search = search

      const trimmedSearch = search.trim()
      // If the user performs an empty search, it will load all the results
      // so let's just set the availableOptions to an empty array to avoid
      // loading a huge result set
      if (trimmedSearch == '') {
        return
      }

      this.searchOptions(trimmedSearch)
    },

    /**
     * Update searchOptions with filtered options based on search parameter
     *
     * @param search
     */
    searchOptions(search) {
      this.availableOptions = this.options.filter(option =>
        option.label?.includes(search)
      )
    },

    /**
     * Debounce function for the search handler
     */
    searchDebouncer: debounce(callback => callback(), 500),
  },

  computed: {
    filter() {
      return this.$store.getters[`${this.resourceName}/getFilter`](
        this.filterKey
      )
    },

    options() {
      return this.filter.options
    },

    isSearchable() {
      return this.filter.searchable
    },

    selectedOption() {
      return this.options.find(
        o => this.value === o.value || this.value === o.value.toString()
      )
    },

    filledValue() {
      return filled(this.value)
    },
  },
}
</script>
