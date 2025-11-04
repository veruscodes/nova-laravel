<template>
  <div
    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded divide-y divide-gray-200 dark:divide-gray-700"
  >
    <div
      class="flex items-center bg-gray-50 dark:bg-gray-800 py-2 px-3 rounded-t"
    >
      <div class="flex items-center space-x-2">
        <Button
          v-if="sortable"
          as="div"
          size="small"
          icon="arrow-up"
          variant="ghost"
          padding="tight"
          @click="$emit('move-up', index)"
          dusk="row-move-up-button"
        />
        <Button
          v-if="sortable"
          as="div"
          size="small"
          icon="arrow-down"
          variant="ghost"
          padding="tight"
          @click="$emit('move-down', index)"
          dusk="row-move-down-button"
        />
      </div>

      <Button
        as="div"
        size="small"
        icon="trash"
        variant="ghost"
        padding="tight"
        @click.stop.prevent="beforeRemove"
        dusk="row-delete-button"
        class="ml-auto"
      />
    </div>

    <div
      class="grid grid-cols-full divide-y divide-gray-100 dark:divide-gray-700"
    >
      <div
        v-for="(nestedField, nestedFieldIndex) in item.fields"
        :key="nestedField.uniqueKey"
      >
        <component
          :ref="fieldRefs[`fields.${nestedField.attribute}`]"
          :is="`form-${nestedField.component}`"
          :field="nestedField"
          :index="nestedFieldIndex"
          :errors="errors"
          :show-help-text="true"
          @file-deleted="$emit('file-deleted')"
          :nested="true"
          :resource-name="resourceName"
          :resource-id="resourceId"
          :shown-via-new-relation-modal="shownViaNewRelationModal"
          :via-resource="viaResource"
          :via-resource-id="viaResourceId"
          :via-relationship="viaRelationship"
        />
        <!--        :related-resource-name="relatedResourceName"-->
        <!--        :related-resource-id="relatedResourceId"-->
        <!--        syncEndpoint,-->
      </div>
    </div>
  </div>
</template>

<script setup>
import { Button } from 'laravel-nova-ui'
import { ref, provide, computed, inject } from 'vue'
import { useLocalization } from '@/composables/useLocalization'
import fromPairs from 'lodash/fromPairs'

const props = defineProps({
  field: { type: Object, required: true },
  index: { type: Number, required: true },
  item: { type: Object, required: true },
  errors: { type: Object, required: true },
  sortable: { type: Boolean, required: false },
  viaParent: { type: String },
})

const emitter = defineEmits(['click', 'move-up', 'move-down', 'file-deleted'])

const { __ } = useLocalization()

provide(
  'viaParent',
  computed(() => props.viaParent)
)
provide(
  'index',
  computed(() => props.index)
)

const fieldKeys = props.item.fields.map(f => f.attribute)
const fieldRefs = fromPairs(fieldKeys.map(k => [`fields.${k}`, ref(null)]))

const resourceName = inject('resourceName')
const resourceId = inject('resourceId')
const shownViaNewRelationModal = inject('shownViaNewRelationModal')
const viaResource = inject('viaResource')
const viaResourceId = inject('viaResourceId')
const viaRelationship = inject('viaRelationship')

const beforeRemove = () =>
  props.item.confirmBeforeRemoval
    ? confirm(__('Are you sure you want to remove this item?'))
      ? remove()
      : null
    : remove()

const remove = () => {
  Object.keys(fieldRefs).forEach(async k => {
    // await fieldRefs[k]?.value[0]?.beforeRemove?()
  })

  emitter('click', props.index)
}
</script>
