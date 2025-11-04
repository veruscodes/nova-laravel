<template>
  <div v-if="shouldShowButtons">
    <!-- Attach Related Models -->
    <InertiaButton
      class="shrink-0"
      v-if="shouldShowAttachButton"
      dusk="attach-button"
      :href="
        $url(
          `/resources/${viaResource}/${viaResourceId}/attach/${resourceName}`,
          {
            viaRelationship,
            polymorphic: relationshipType === 'morphToMany' ? '1' : '0',
          }
        )
      "
    >
      <slot>
        <span class="hidden md:inline-block">
          {{ __('Attach :resource', { resource: singularName }) }}
        </span>
        <span class="inline-block md:hidden">
          {{ __('Attach') }}
        </span>
      </slot>
    </InertiaButton>

    <!-- Create Related Models -->
    <InertiaButton
      v-else-if="shouldShowCreateButton"
      class="shrink-0 h-9 px-4 focus:outline-none ring-primary-200 dark:ring-gray-600 focus:ring text-white dark:text-gray-800 inline-flex items-center font-bold"
      dusk="create-button"
      :href="
        $url(`/resources/${resourceName}/new`, {
          viaResource: viaResource,
          viaResourceId: viaResourceId,
          viaRelationship: viaRelationship,
          relationshipType: relationshipType,
        })
      "
    >
      <span class="hidden md:inline-block">
        {{ label }}
      </span>
      <span class="inline-block md:hidden">
        {{ __('Create') }}
      </span>
    </InertiaButton>
  </div>
</template>

<script setup>
import { useLocalization } from '@/composables/useLocalization'
import { computed } from 'vue'

const { __ } = useLocalization()

const props = defineProps({
  type: {
    type: String,
    default: 'button',
    validator: val => ['button', 'outline-button'].includes(val),
  },
  label: {},
  singularName: {},
  resourceName: {},
  viaResource: {},
  viaResourceId: {},
  viaRelationship: {},
  relationshipType: {},
  authorizedToCreate: {},
  authorizedToRelate: {},
  alreadyFilled: { type: Boolean, default: false },
})

const shouldShowAttachButton = computed(() => {
  return (
    ['belongsToMany', 'morphToMany'].includes(props.relationshipType) &&
    props.authorizedToRelate
  )
})

const shouldShowCreateButton = computed(() => {
  return (
    props.authorizedToCreate && props.authorizedToRelate && !props.alreadyFilled
  )
})

const shouldShowButtons = computed(() => {
  return shouldShowAttachButton || shouldShowCreateButton
})
</script>
