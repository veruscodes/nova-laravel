<template>
  <Dropdown dusk="filter-selector" :should-close-on-blur="false">
    <Button
      :variant="filtersWithTrashedAreApplied ? 'solid' : 'ghost'"
      dusk="filter-selector-button"
      icon="funnel"
      trailing-icon="chevron-down"
      padding="tight"
      :label="
        activeFilterWithTrashedCount > 0 ? activeFilterWithTrashedCount : ''
      "
      :aria-label="__('Filter Dropdown')"
    />

    <template #menu>
      <DropdownMenu width="260" dusk="filter-menu">
        <ScrollWrap :height="350" class="bg-white dark:bg-gray-900">
          <div
            class="divide-y divide-gray-200 dark:divide-gray-800 divide-solid"
          >
            <div v-if="filtersWithTrashedAreApplied" class="bg-gray-100">
              <button
                class="py-2 w-full block text-xs uppercase tracking-wide text-center text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 font-bold focus:outline-none focus:text-primary-500"
                @click="handleClearSelectedFiltersClick"
              >
                {{ __('Reset Filters') }}
              </button>
            </div>

            <!-- Custom Filters -->
            <div
              v-for="(filter, index) in filters"
              :key="`${filter.class}-${index}`"
            >
              <component
                :is="filter.component"
                :filter-key="filter.class"
                :lens="lens"
                :resource-name="resourceName"
                @change="handleFilterChanged"
              />
            </div>

            <!-- Soft Deletes -->
            <FilterContainer v-if="softDeletes" dusk="filter-soft-deletes">
              <span>{{ __('Trashed') }}</span>

              <template #filter>
                <SelectControl
                  v-model="trashedValue"
                  :options="[
                    { value: '', label: '—' },
                    { value: 'with', label: __('With Trashed') },
                    { value: 'only', label: __('Only Trashed') },
                  ]"
                  dusk="trashed-select"
                  size="sm"
                />
              </template>
            </FilterContainer>

            <!-- Per Page -->
            <FilterContainer
              v-if="perPageOptionsForFilter.length > 1"
              dusk="filter-per-page"
            >
              <span>{{ __('Per Page') }}</span>

              <template #filter>
                <SelectControl
                  v-model="perPageValue"
                  :options="perPageOptionsForFilter"
                  dusk="per-page-select"
                  size="sm"
                />
              </template>
            </FilterContainer>
          </div>
        </ScrollWrap>
      </DropdownMenu>
    </template>
  </Dropdown>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: [
    'filter-changed',
    'clear-selected-filters',
    'trashed-changed',
    'per-page-changed',
  ],

  props: {
    activeFilterCount: Number,
    filters: Array,
    filtersAreApplied: Boolean,
    lens: { type: String, default: '' },
    perPage: [String, Number],
    perPageOptions: Array,
    resourceName: String,
    softDeletes: Boolean,
    trashed: { type: String, validator: v => ['', 'with', 'only'].includes(v) },
    viaResource: String,
  },

  methods: {
    handleFilterChanged(v) {
      // Older filters generated with our stubs will not have a value, since they committed to the store directly
      // instead of emitting a change event with the `filterKey` and `value`. We need to handle both cases.
      if (v) {
        const { filterClass, value } = v

        if (filterClass) {
          Nova.debug(`Updating filter state ${filterClass}: ${value}`)

          this.$store.commit(`${this.resourceName}/updateFilterState`, {
            filterClass,
            value,
          })
        }
      }

      this.$emit('filter-changed')
    },

    handleClearSelectedFiltersClick() {
      Nova.$emit('clear-filter-values')

      setTimeout(() => {
        this.$emit('clear-selected-filters')
      }, 500)
    },
  },

  computed: {
    filtersWithTrashedAreApplied() {
      return this.filtersAreApplied || this.trashed !== ''
    },

    activeFilterWithTrashedCount() {
      const trashed = this.trashed !== '' ? 1 : 0

      return this.activeFilterCount + trashed
    },

    trashedValue: {
      set(event) {
        let value = event?.target?.value || event

        this.$emit('trashed-changed', value)
      },
      get() {
        return this.trashed
      },
    },

    perPageValue: {
      set(event) {
        let value = event?.target?.value || event

        this.$emit('per-page-changed', value)
      },
      get() {
        return this.perPage
      },
    },

    /**
     * Return the values for the per page filter
     */
    perPageOptionsForFilter() {
      return this.perPageOptions.map(option => {
        return { value: option, label: option }
      })
    },
  },
}
</script>
