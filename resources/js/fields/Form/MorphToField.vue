<template>
  <div class="border-b border-gray-100 dark:border-gray-700">
    <DefaultField
      :field="currentField"
      :show-errors="false"
      :field-name="fieldName"
      :show-help-text="showHelpText"
      :full-width-content="fullWidthContent"
    >
      <template #field>
        <div v-if="hasMorphToTypes" class="flex relative">
          <select
            :disabled="
              (viaRelatedResource && !shouldIgnoresViaRelatedResource) ||
              currentlyIsReadonly
            "
            :dusk="`${field.attribute}-type`"
            :value="resourceType"
            @change="refreshResourcesForTypeChange"
            class="w-full block form-control form-input form-control-bordered"
          >
            <option value="" selected :disabled="!currentField.nullable">
              {{ __('Choose Type') }}
            </option>

            <option
              v-for="option in currentField.morphToTypes"
              :key="option.value"
              :value="option.value"
              :selected="resourceType == option.value"
            >
              {{ option.singularLabel }}
            </option>
          </select>

          <span
            class="pointer-events-none absolute inset-y-0 right-[11px] flex items-center"
          >
            <IconArrow />
          </span>
        </div>
        <label v-else class="flex items-center select-none mt-2">
          {{ __('There are no available options for this resource.') }}
        </label>
      </template>
    </DefaultField>

    <DefaultField
      :field="currentField"
      :errors="errors"
      :show-help-text="false"
      :field-name="fieldTypeName"
      v-if="hasMorphToTypes"
      :full-width-content="fullWidthContent"
    >
      <template #field>
        <div class="flex items-center mb-3">
          <SearchInput
            v-if="useSearchInput"
            v-model="selectedResourceId"
            @selected="selectResourceFromSelectOrSearch"
            @input="performResourceSearch"
            @clear="clearResourceSelection"
            :options="filteredResources"
            :disabled="currentlyIsReadonly"
            :debounce="currentField.debounce"
            :clearable="
              currentField.nullable ||
              editingExistingResource ||
              viaRelatedResource ||
              createdViaRelationModal
            "
            trackBy="value"
            :mode="mode"
            :autocomplete="currentField.autocomplete"
            class="w-full"
            :dusk="`${field.attribute}-search-input`"
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
                  <img
                    :src="option.avatar"
                    class="w-8 h-8 rounded-full block"
                  />
                </div>

                <div class="flex-auto">
                  <div
                    class="text-sm font-semibold leading-5"
                    :class="{ 'text-white': selected }"
                  >
                    {{ option.display }}
                  </div>

                  <div
                    v-if="currentField.withSubtitles"
                    class="mt-1 text-xs font-semibold leading-5 text-gray-500"
                    :class="{ 'text-white': selected }"
                  >
                    <span v-if="option.subtitle">{{ option.subtitle }}</span>
                    <span v-else>{{ __('No additional information...') }}</span>
                  </div>
                </div>
              </div>
            </template>
          </SearchInput>

          <SelectControl
            v-else
            v-model="selectedResourceId"
            @selected="selectResourceFromSelectOrSearch"
            :options="availableResources"
            :disabled="!resourceType || currentlyIsReadonly"
            label="display"
            class="w-full"
            :class="{ 'form-control-bordered-error': hasError }"
            :dusk="`${field.attribute}-select`"
          >
            <option
              value=""
              :disabled="!currentField.nullable"
              :selected="selectedResourceId === ''"
            >
              {{ __('Choose') }} {{ fieldTypeName }}
            </option>
          </SelectControl>

          <Button
            v-if="canShowNewRelationModal"
            variant="link"
            size="small"
            leading-icon="plus-circle"
            @click="openRelationModal"
            class="ml-2"
            :dusk="`${field.attribute}-inline-create`"
          />
        </div>

        <CreateRelationModal
          v-if="canShowNewRelationModal"
          :show="relationModalOpen"
          :size="field.modalSize"
          @set-resource="handleSetResource"
          @create-cancelled="closeRelationModal"
          :resource-name="resourceType"
          :via-relationship="viaRelationship"
          :via-resource="viaResource"
          :via-resource-id="viaResourceId"
        />

        <TrashedCheckbox
          v-if="shouldShowTrashed"
          class="mt-3"
          :resource-name="field.attribute"
          :checked="withTrashed"
          @input="toggleWithTrashed"
        />
      </template>
    </DefaultField>
  </div>
</template>

<script>
import { Button } from 'laravel-nova-ui'
import storage from '@/storage/MorphToFieldStorage'
import {
  DependentFormField,
  HandlesValidationErrors,
  InteractsWithQueryString,
  PerformsSearches,
  TogglesTrashed,
} from '@/mixins'
import filled from '@/util/filled'

export default {
  components: {
    Button,
  },

  mixins: [
    DependentFormField,
    HandlesValidationErrors,
    InteractsWithQueryString,
    PerformsSearches,
    TogglesTrashed,
  ],

  data: () => ({
    resourceType: '',
    initializingWithExistingResource: false,
    createdViaRelationModal: false,
    softDeletes: false,
    selectedResourceId: null,
    search: '',
    relationModalOpen: false,
    withTrashed: false,
  }),

  /**
   * Mount the component.
   */
  mounted() {
    this.initializeComponent()
  },

  methods: {
    initializeComponent() {
      this.selectedResourceId = this.field.value

      if (this.editingExistingResource) {
        this.initializingWithExistingResource = true
        this.resourceType = this.field.morphToType
        this.selectedResourceId = this.field.morphToId
      } else if (this.viaRelatedResource) {
        this.initializingWithExistingResource = true
        this.resourceType = this.viaResource
        this.selectedResourceId = this.viaResourceId
      }

      if (this.shouldSelectInitialResource) {
        if (!this.resourceType && this.field.defaultResource) {
          this.resourceType = this.field.defaultResource
        }
        this.getAvailableResources()
      }

      if (this.resourceType) {
        this.determineIfSoftDeletes()
      }

      this.field.fill = this.fill
    },

    /**
     * Set the currently selected resource
     */
    selectResourceFromSelectOrSearch(resource) {
      if (this.field) {
        this.emitFieldValueChange(
          `${this.fieldAttribute}_type`,
          this.resourceType
        )
      }

      this.selectResource(resource)
    },

    /**
     * Fill the forms formData with details from this field
     */
    fill(formData) {
      if (this.selectedResourceId && this.resourceType) {
        this.fillIfVisible(
          formData,
          this.fieldAttribute,
          this.selectedResourceId ?? ''
        )
        this.fillIfVisible(
          formData,
          `${this.fieldAttribute}_type`,
          this.resourceType
        )
      } else {
        this.fillIfVisible(formData, this.fieldAttribute, '')
        this.fillIfVisible(formData, `${this.fieldAttribute}_type`, '')
      }

      this.fillIfVisible(
        formData,
        `${this.fieldAttribute}_trashed`,
        this.withTrashed
      )
    },

    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources(search = '') {
      Nova.$progress.start()

      return storage
        .fetchAvailableResources(this.resourceName, this.fieldAttribute, {
          params: this.queryParams,
        })
        .then(({ data: { resources, softDeletes, withTrashed } }) => {
          if (this.initializingWithExistingResource || !this.isSearchable) {
            this.withTrashed = withTrashed
          }

          if (this.isSearchable) {
            this.initializingWithExistingResource = false
          }
          this.availableResources = resources
          this.softDeletes = softDeletes
        })
        .finally(() => {
          Nova.$progress.done()
        })
    },

    onSyncedField() {
      if (this.resourceType !== this.currentField.resourceName) {
        this.refreshResourcesForTypeChange(this.currentField.morphToType)
      }
    },

    /**
     * Determine if the selected resource type is soft deleting.
     */
    determineIfSoftDeletes() {
      return storage
        .determineIfSoftDeletes(this.resourceType)
        .then(({ data: { softDeletes } }) => (this.softDeletes = softDeletes))
    },

    /**
     * Handle the changing of the resource type.
     */
    async refreshResourcesForTypeChange(event) {
      this.resourceType = event?.target?.value ?? event
      this.availableResources = []
      this.selectedResourceId = null
      this.withTrashed = false

      this.softDeletes = false
      this.determineIfSoftDeletes()

      if (!this.isSearchable && this.resourceType) {
        this.getAvailableResources().then(() => {
          this.emitFieldValueChange(
            `${this.fieldAttribute}_type`,
            this.resourceType
          )
          this.emitFieldValueChange(this.fieldAttribute, null)
        })
      }
    },

    /**
     * Toggle the trashed state of the search
     */
    toggleWithTrashed() {
      // Reload the data if the component doesn't have selected resource
      if (!filled(this.selectedResourceId)) {
        this.withTrashed = !this.withTrashed

        // Reload the data if the component doesn't support searching
        if (!this.isSearchable) {
          this.getAvailableResources()
        }
      }
    },

    openRelationModal() {
      Nova.$emit('create-relation-modal-opened')
      this.relationModalOpen = true
    },

    closeRelationModal() {
      this.relationModalOpen = false
      Nova.$emit('create-relation-modal-closed')
    },

    handleSetResource({ id }) {
      this.closeRelationModal()
      this.selectedResourceId = id
      this.createdViaRelationModal = true
      this.initializingWithExistingResource = true
      this.getAvailableResources().then(() => {
        this.emitFieldValueChange(
          `${this.fieldAttribute}_type`,
          this.resourceType
        )
        this.emitFieldValueChange(this.fieldAttribute, this.selectedResourceId)
      })
    },

    performResourceSearch(search) {
      if (this.useSearchInput) {
        this.performSearch(search)
      } else {
        this.search = search
      }
    },

    clearResourceSelection() {
      this.clearSelection()

      if (this.viaRelatedResource && !this.createdViaRelationModal) {
        this.pushAfterUpdatingQueryString({
          viaResource: null,
          viaResourceId: null,
          viaRelationship: null,
          relationshipType: null,
        }).then(() => {
          Nova.$router.reload({
            onSuccess: () => {
              this.initializingWithExistingResource = false
              this.initializeComponent()
            },
          })
        })
      } else {
        if (this.createdViaRelationModal) {
          this.createdViaRelationModal = false
          this.initializingWithExistingResource = false
        }

        this.getAvailableResources()
      }
    },

    isSelectedResourceId(value) {
      return (
        value != null &&
        value?.toString() === this.selectedResourceId?.toString()
      )
    },
  },

  computed: {
    /**
     * Determine if an existing resource is being updated.
     */
    editingExistingResource() {
      return Boolean(this.field.morphToId && this.field.morphToType)
    },

    /**
     * Determine if we are creating a new resource via a parent relation
     */
    viaRelatedResource() {
      return Boolean(
        this.currentField.morphToTypes.find(
          type => type.value == this.viaResource
        ) != null &&
          this.viaResource &&
          this.viaResourceId &&
          this.currentField.reverse
      )
    },

    /**
     * Determine if we should select an initial resource when mounting this field
     */
    shouldSelectInitialResource() {
      return Boolean(
        this.editingExistingResource ||
          this.viaRelatedResource ||
          Boolean(this.field.value && this.field.defaultResource)
      )
    },

    /**
     * Determine if the related resources is searchable
     */
    isSearchable() {
      return Boolean(this.currentField.searchable)
    },

    shouldLoadFirstResource() {
      return (
        ((this.useSearchInput &&
          !this.shouldIgnoreViaRelatedResource &&
          this.shouldSelectInitialResource) ||
          this.createdViaRelationModal) &&
        this.initializingWithExistingResource
      )
    },

    /**
     * Get the query params for getting available resources
     */
    queryParams() {
      return {
        type: this.resourceType,
        current: this.selectedResourceId,
        first: this.shouldLoadFirstResource,
        search: this.search,
        withTrashed: this.withTrashed,
        viaResource: this.viaResource,
        viaResourceId: this.viaResourceId,
        viaRelationship: this.viaRelationship,
        component: this.field.dependentComponentKey,
        dependsOn: this.encodedDependentFieldValues,
        editing: true,
        editMode:
          this.resourceId == null || this.resourceId === ''
            ? 'create'
            : 'update',
      }
    },

    /**
     * Return the morphable type label for the field
     */
    fieldName() {
      return this.field.name
    },

    /**
     * Return the selected morphable type's label
     */
    fieldTypeName() {
      if (this.resourceType) {
        return (
          this.currentField.morphToTypes.find(type => {
            return type.value == this.resourceType
          })?.singularLabel || ''
        )
      }

      return ''
    },

    /**
     * Determine whether there are any morph to types.
     */
    hasMorphToTypes() {
      return this.currentField.morphToTypes.length > 0
    },

    authorizedToCreate() {
      return (
        Nova.config('resources').find(resource => {
          return resource.uriKey == this.resourceType
        })?.authorizedToCreate || false
      )
    },

    canShowNewRelationModal() {
      return (
        this.currentField.showCreateRelationButton &&
        this.resourceType &&
        !this.shownViaNewRelationModal &&
        !this.viaRelatedResource &&
        !this.currentlyIsReadonly &&
        this.authorizedToCreate
      )
    },

    shouldShowTrashed() {
      return (
        this.softDeletes &&
        !this.viaRelatedResource &&
        !this.currentlyIsReadonly &&
        this.currentField.displaysWithTrashed
      )
    },

    currentFieldValues() {
      return {
        [this.fieldAttribute]: this.value,
        [`${this.fieldAttribute}_type`]: this.resourceType,
      }
    },

    /**
     * Return the field options filtered by the search string.
     */
    filteredResources() {
      if (!this.isSearchable) {
        return this.availableResources.filter(option => {
          return (
            option.display.toLowerCase().indexOf(this.search.toLowerCase()) >
              -1 || new String(option.value).indexOf(this.search) > -1
          )
        })
      }

      return this.availableResources
    },

    shouldIgnoresViaRelatedResource() {
      return this.viaRelatedResource && filled(this.search)
    },

    useSearchInput() {
      return this.isSearchable || this.viaRelatedResource
    },

    selectedResource() {
      return this.availableResources.find(r =>
        this.isSelectedResourceId(r.value)
      )
    },
  },
}
</script>
