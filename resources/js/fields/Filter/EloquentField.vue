<template>
  <FilterContainer v-if="shouldShowFilter">
    <span>{{ filter.name }}</span>

    <template #filter>
      <SearchInput
        v-if="isSearchable"
        ref="searchable"
        v-model="selectedResourceId"
        @input="performSearch"
        @clear="handleClearSelection"
        @shown="handleShowingActiveSearchInput"
        :options="availableResources"
        :debounce="field.debounce"
        :clearable="true"
        trackBy="value"
        mode="modal"
        class="w-full"
        :dusk="`${filter.uniqueKey}-search-input`"
      >
        <div v-if="selectedResource" class="flex items-center">
          <div v-if="selectedResource.avatar" class="mr-3">
            <img
              :src="selectedResource.avatar"
              class="w-8 h-8 rounded-full block"
            />
          </div>

          {{ selectedResource.display }}
        </div>

        <template #option="{ selected, option }">
          <div class="flex items-center">
            <div v-if="option.avatar" class="flex-none mr-3">
              <img :src="option.avatar" class="w-8 h-8 rounded-full block" />
            </div>

            <div class="flex-auto">
              <div
                class="text-sm font-semibold leading-normal"
                :class="{ 'text-white dark:text-gray-900': selected }"
              >
                {{ option.display }}
              </div>

              <div
                v-if="field.withSubtitles"
                class="text-xs font-semibold leading-normal text-gray-500"
                :class="{ 'text-white dark:text-gray-700': selected }"
              >
                <span v-if="option.subtitle">{{ option.subtitle }}</span>
                <span v-else>{{ __('No additional information...') }}</span>
              </div>
            </div>
          </div>
        </template>
      </SearchInput>

      <SelectControl
        v-else-if="availableResources.length > 0"
        v-model="selectedResourceId"
        :options="availableResources"
        label="display"
        :dusk="filter.uniqueKey"
      >
        <option value="" selected>&mdash;</option>
      </SelectControl>
    </template>
  </FilterContainer>
</template>

<script>
import { PerformsSearches } from '@/mixins'
import storage from '@/storage/ResourceSearchStorage'
import debounce from 'lodash/debounce'

export default {
  emits: ['change'],

  mixins: [PerformsSearches],

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
    availableResources: [],
    selectedResourceId: '',
    softDeletes: false,
    withTrashed: false,
    search: '',

    debouncedEventEmitter: null,
  }),

  mounted() {
    Nova.$on('filter-reset', this.handleFilterReset)

    this.initializeComponent()
  },

  created() {
    this.debouncedEventEmitter = debounce(() => this.emitFilterChange(), 500)

    Nova.$on('filter-active', this.handleClosingInactiveSearchInputs)
  },

  beforeUnmount() {
    Nova.$off('filter-active', this.handleClosingInactiveSearchInputs)
    Nova.$off('filter-reset', this.handleFilterReset)
  },

  watch: {
    selectedResourceId() {
      this.debouncedEventEmitter()
    },
  },

  methods: {
    /**
     * Initialize the component.
     */
    initializeComponent() {
      let shouldSelectInitialResource = false

      if (this.filter.currentValue) {
        this.selectedResourceId = this.filter.currentValue

        if (this.isSearchable === true) {
          shouldSelectInitialResource = true
        }
      }

      if (!this.isSearchable || shouldSelectInitialResource) {
        this.getAvailableResources()
      }
    },

    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources(search) {
      let queryParams = this.queryParams

      if (search != null) {
        queryParams.first = false
        queryParams.current = null
        queryParams.search = search
      }

      return storage
        .fetchAvailableResources(this.field.resourceName, {
          params: queryParams,
        })
        .then(({ data: { resources, softDeletes, withTrashed } }) => {
          if (!this.isSearchable) {
            this.withTrashed = withTrashed
          }

          this.availableResources = resources
          this.softDeletes = softDeletes
        })
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

    /**
     * Handle clear search selection
     */
    handleClearSelection() {
      this.clearSelection()
    },

    emitFilterChange() {
      this.$emit('change', {
        filterClass: this.filterKey,
        value: this.selectedResourceId ?? '',
      })
    },

    handleFilterReset() {
      if (this.filter.currentValue !== '') {
        return
      }

      this.selectedResourceId = ''
      this.availableResources = []

      this.closeSearchableRef()

      this.initializeComponent()
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

    shouldShowFilter() {
      return (
        this.isSearchable ||
        (!this.isSearchable && this.availableResources.length > 0)
      )
    },

    /**
     * Determine if the related resources is searchable
     */
    isSearchable() {
      return this.field.searchable
    },

    /**
     * Get the query params for getting available resources
     */
    queryParams() {
      return {
        current: this.selectedResourceId,
        first: this.selectedResourceId && this.isSearchable,
        search: this.search,
        withTrashed: this.withTrashed,
      }
    },

    selectedResource() {
      return this.availableResources.find(
        r => r.value === this.selectedResourceId
      )
    },
  },
}
</script>
