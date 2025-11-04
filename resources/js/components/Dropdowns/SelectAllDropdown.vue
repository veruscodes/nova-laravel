<template>
  <Dropdown placement="bottom-start" dusk="select-all-dropdown">
    <Button
      variant="ghost"
      trailing-icon="chevron-down"
      class="-ml-1"
      :class="{
        'enabled:bg-gray-700/5 dark:enabled:bg-gray-950':
          selectAllOrSelectAllMatchingChecked || selectedResourcesCount > 0,
      }"
      dusk="select-all-dropdown-trigger"
    >
      <Checkbox
        :aria-label="__('Select this page')"
        :indeterminate="selectAllIndeterminate"
        :model-value="selectAllAndSelectAllMatchingChecked"
        class="pointer-events-none"
        dusk="select-all-indicator"
        tabindex="-1"
      />

      <div
        ref="selectedStatus"
        v-if="selectedResourcesCount > 0"
        class="rounded-lg h-9 inline-flex items-center text-gray-600 dark:text-gray-400"
      >
        <span class="inline-flex items-center gap-1 pl-1">
          <span class="font-bold">{{
            __(':amount selected', {
              amount: selectAllMatchingChecked
                ? allMatchingResourceCount
                : selectedResourcesCount,
              label: singularOrPlural(selectedResourcesCount, 'resources'),
            })
          }}</span>
        </span>
        <Button
          @click.stop="$emit('deselect')"
          variant="link"
          icon="x-circle"
          size="small"
          state="mellow"
          class="-mr-2"
          :aria-label="__('Deselect All')"
          dusk="deselect-all-button"
        />
      </div>
    </Button>

    <template #menu>
      <DropdownMenu direction="ltr" width="250">
        <div class="p-4 flex flex-col items-start gap-4">
          <!--            @click="$emit('toggle-select-all')"-->
          <!--            @keydown.space.stop="$emit('toggle-select-all')"-->
          <Checkbox
            @change="$emit('toggle-select-all')"
            :model-value="selectAllChecked"
            dusk="select-all-button"
          >
            <span>
              {{ __('Select this page') }}
            </span>
            <CircleBadge>
              {{ currentPageCount }}
            </CircleBadge>
          </Checkbox>

          <Checkbox
            @change="$emit('toggle-select-all-matching')"
            :model-value="selectAllMatchingChecked"
            dusk="select-all-matching-button"
          >
            <span>
              <span>
                {{ __('Select all') }}
              </span>
              <CircleBadge dusk="select-all-matching-count">
                {{ allMatchingResourceCount }}
              </CircleBadge>
            </span>
          </Checkbox>
        </div>
      </DropdownMenu>
    </template>
  </Dropdown>
</template>

<script setup>
import { inject } from 'vue'
import { singularOrPlural } from '@/util'
import { Checkbox, Button } from 'laravel-nova-ui'

defineEmits(['toggle-select-all', 'toggle-select-all-matching', 'deselect'])

const selectedResourcesCount = inject('selectedResourcesCount')
const selectAllChecked = inject('selectAllChecked')
const selectAllMatchingChecked = inject('selectAllMatchingChecked')
const selectAllAndSelectAllMatchingChecked = inject(
  'selectAllAndSelectAllMatchingChecked'
)
const selectAllOrSelectAllMatchingChecked = inject(
  'selectAllOrSelectAllMatchingChecked'
)
const selectAllIndeterminate = inject('selectAllIndeterminate')

defineProps({
  currentPageCount: { type: Number, default: 0 },
  allMatchingResourceCount: { type: Number, default: 0 },
})
</script>
