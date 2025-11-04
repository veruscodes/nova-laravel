<template>
  <SelectControl
    v-if="actionSelectionOptions.length > 0"
    v-bind="$attrs"
    ref="actionSelectControl"
    v-model="actionSelectionInput"
    :options="actionSelectionOptions"
    size="xs"
    :class="{ 'max-w-[6rem]': width === 'auto', 'w-full': width === 'full' }"
    dusk="action-select"
    :aria-label="__('Select Action')"
  >
    <option value="" disabled selected>{{ __('Actions') }}</option>
  </SelectControl>

  <!-- Confirm Action Modal -->
  <component
    class="text-left"
    v-if="actionModalVisible"
    :show="actionModalVisible"
    :is="selectedAction?.component"
    :working="working"
    :selected-resources="selectedResources"
    :resource-name="resourceName"
    :action="selectedAction"
    :errors="errors"
    @confirm="executeAction"
    @close="closeConfirmationModal"
  />

  <component
    v-if="responseModalVisible"
    :show="responseModalVisible"
    :is="actionModalReponseData?.component"
    @confirm="closeResponseModal"
    @close="closeResponseModal"
    :data="actionModalReponseData?.payload ?? {}"
  />
</template>

<script setup>
import { useActions } from '@/composables/useActions'
import { computed, nextTick, ref, watch } from 'vue'
import { useStore } from 'vuex'

const emitter = defineEmits(['actionExecuted'])

const props = defineProps({
  width: { type: String, default: 'auto' },
  pivotName: { type: String, default: null },

  resourceName: {},
  viaResource: {},
  viaResourceId: {},
  viaRelationship: {},
  relationshipType: {},
  pivotActions: {
    type: Object,
    default: () => ({ name: 'Pivot', actions: [] }),
  },
  actions: { type: Array, default: [] },
  selectedResources: { type: [Array, String], default: () => [] },
  endpoint: { type: String, default: null },
  triggerDuskAttribute: { type: String, default: null },
})

const actionSelectionInput = ref('')

const store = useStore()

const {
  errors,
  actionModalVisible,
  responseModalVisible,
  openConfirmationModal,
  closeConfirmationModal,
  closeResponseModal,
  handleActionClick,
  selectedAction,
  setSelectedActionKey,
  determineActionStrategy,
  working,
  executeAction,
  availableActions,
  availablePivotActions,
  actionModalReponseData,
} = useActions(props, emitter, store)

watch(actionSelectionInput, value => {
  if (value == '') {
    return
  }

  setSelectedActionKey(value)
  determineActionStrategy()

  nextTick(() => (actionSelectionInput.value = ''))
})

const actionSelectionOptions = computed(() => [
  ...availableActions.value.map(a => ({
    value: a.uriKey,
    label: a.name,
    disabled: a.authorizedToRun === false,
  })),
  ...availablePivotActions.value.map(a => ({
    group: props.pivotName,
    value: a.uriKey,
    label: a.name,
    disabled: a.authorizedToRun === false,
  })),
])
</script>
