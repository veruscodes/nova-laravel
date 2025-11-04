<template>
  <LoadingCard :loading="loading" class="pt-4">
    <div class="h-6 flex items-center px-6 mb-4">
      <h3 class="mr-3 leading-tight text-sm font-bold">{{ card.name }}</h3>
      <HelpTextTooltip :text="card.helpText" :width="card.helpWidth" />
    </div>

    <div class="mb-5 pb-4">
      <div
        v-if="value.length > 0"
        class="overflow-hidden overflow-x-auto relative"
      >
        <table class="w-full table-default table-fixed">
          <tbody
            class="border-t border-b border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700"
          >
            <MetricTableRow v-for="row in value" :row="row" />
          </tbody>
        </table>
      </div>
      <div v-else class="flex flex-col items-center justify-between px-6 gap-2">
        <p class="font-normal text-center py-4">
          {{ card.emptyText }}
        </p>
      </div>
    </div>
  </LoadingCard>
</template>

<script>
import { InteractsWithDates, MetricBehavior } from '@/mixins'

export default {
  name: 'TableCard',

  mixins: [InteractsWithDates, MetricBehavior],

  data: () => ({
    loading: true,
    value: [],
  }),

  watch: {
    resourceId() {
      this.fetch()
    },
  },

  created() {
    this.fetch()
  },

  mounted() {
    if (this.card && this.card.refreshWhenFiltersChange === true) {
      Nova.$on('filter-changed', this.fetch)
      Nova.$on('filter-reset', this.fetch)
    }
  },

  beforeUnmount() {
    if (this.card && this.card.refreshWhenFiltersChange === true) {
      Nova.$off('filter-changed', this.fetch)
      Nova.$off('filter-reset', this.fetch)
    }
  },

  methods: {
    handleFetchCallback() {
      return ({ data: { value } }) => {
        this.value = value
        this.loading = false
      }
    },
  },

  computed: {
    metricPayload() {
      const payload = {
        params: {
          timezone: this.userTimezone,
        },
      }

      if (
        !Nova.missingResource(this.resourceName) &&
        this.card &&
        this.card.refreshWhenFiltersChange === true
      ) {
        payload.params.filter =
          this.$store.getters[`${this.resourceName}/currentEncodedFilters`]
      }

      return payload
    },
  },
}
</script>
