<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div class="flex items-center">
        <SearchInput
          v-if="useSearchInput"
          v-model="selectedResourceId"
          @selected="selectResource"
          @input="performResourceSearch"
          @clear="clearResourceSelection"
          :options="filteredResources"
          :has-error="hasError"
          :debounce="currentField.debounce"
          :disabled="currentlyIsReadonly"
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
          :dusk="`${field.resourceName}-search-input`"
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
            <SearchInputResult
              :option="option"
              :selected="selected"
              :with-subtitles="currentField.withSubtitles"
            />
          </template>
        </SearchInput>

        <SelectControl
          v-else
          v-model="selectedResourceId"
          @selected="selectResource"
          :options="availableResources"
          :has-error="hasError"
          :disabled="currentlyIsReadonly"
          label="display"
          class="w-full"
          :dusk="`${field.resourceName}-select`"
        >
          <option value="" selected :disabled="!currentField.nullable">
            {{ placeholder }}
          </option>
        </SelectControl>

        <Button
          v-if="canShowNewRelationModal"
          variant="link"
          size="small"
          leading-icon="plus-circle"
          v-tooltip="__('Create :resource', { resource: field.singularLabel })"
          @click="openRelationModal"
          :dusk="`${field.attribute}-inline-create`"
        />
      </div>

      <CreateRelationModal
        :show="canShowNewRelationModal && relationModalOpen"
        :size="field.modalSize"
        @set-resource="handleSetResource"
        @create-cancelled="closeRelationModal"
        :resource-name="field.resourceName"
        :resource-id="resourceId"
        :via-relationship="viaRelationship"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
      />

      <TrashedCheckbox
        v-if="shouldShowTrashed"
        class="mt-3"
        :resource-name="field.resourceName"
        :checked="withTrashed"
        @input="toggleWithTrashed"
      />
    </template>
  </DefaultField>
</template>

<script>
import { Button } from 'laravel-nova-ui'
import {
  DependentFormField,
  HandlesValidationErrors,
  InteractsWithQueryString,
  PerformsSearches,
  TogglesTrashed,
} from '@/mixins'
import storage from '@/storage/BelongsToFieldStorage'
import findIndex from 'lodash/findIndex'
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

  props: {
    resourceId: {},
  },

  data: () => ({
    availableResources: [],
    initializingWithExistingResource: false,
    createdViaRelationModal: false,
    selectedResourceId: null,
    softDeletes: false,
    withTrashed: false,
    search: '',
    relationModalOpen: false,
  }),

  /**
   * Mount the component.
   */
  mounted() {
    this.initializeComponent()
  },

  methods: {
    initializeComponent() {
      this.withTrashed = false

      this.selectedResourceId = this.currentField.value

      if (this.editingExistingResource) {
        // If a user is editing an existing resource with this relation
        // we'll have a belongsToId on the field, and we should prefill
        // that resource in this field
        this.initializingWithExistingResource = true
        this.selectedResourceId = this.currentField.belongsToId
      } else if (this.viaRelatedResource) {
        // If the user is creating this resource via a related resource's index
        // page we'll have a viaResource and viaResourceId in the params and
        // should prefill the resource in this field with that information
        this.initializingWithExistingResource = true
        this.selectedResourceId = this.viaResourceId
      }

      if (this.shouldSelectInitialResource) {
        if (this.useSearchInput) {
          // If we should select the initial resource and the field is
          // searchable, we won't load all the resources but we will select
          // the initial option.
          this.getAvailableResources()
        } else {
          // If we should select the initial resource but the field is not
          // searchable we should load all of the available resources into the
          // field first and select the initial option.
          this.initializingWithExistingResource = false

          this.getAvailableResources()
        }
      } else if (!this.isSearchable && this.currentlyIsVisible) {
        // If we don't need to select an initial resource because the user
        // came to create a resource directly and there's no parent resource,
        // and the field is searchable we'll just load all of the resources.
        this.getAvailableResources()
      }

      this.determineIfSoftDeletes()

      this.field.fill = this.fill
    },

    /**
     * Return the field default value.
     *
     * @returns {string}
     */
    fieldDefaultValue() {
      return null
    },

    /**
     * Fill the forms formData with details from this field
     */
    fill(formData) {
      this.fillIfVisible(
        formData,
        this.fieldAttribute,
        this.selectedResourceId ?? ''
      )
      this.fillIfVisible(
        formData,
        `${this.fieldAttribute}_trashed`,
        this.withTrashed
      )
    },

    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources() {
      Nova.$progress.start()

      return storage
        .fetchAvailableResources(this.resourceName, this.fieldAttribute, {
          params: this.queryParams,
        })
        .then(({ data: { resources, softDeletes, withTrashed } }) => {
          if (this.initializingWithExistingResource || !this.isSearchable) {
            this.withTrashed = withTrashed
          }

          if (this.viaRelatedResource) {
            let hasSelectedResource = resources.find(r =>
              this.isSelectedResourceId(r.value)
            )

            if (!hasSelectedResource && !this.shouldIgnoreViaRelatedResource) {
              return Nova.visit('/404')
            }
          }

          // Turn off initializing the existing resource after the first time
          if (this.useSearchInput) {
            this.initializingWithExistingResource = false
          }
          this.availableResources = resources
          this.softDeletes = softDeletes
        })
        .finally(() => {
          Nova.$progress.done()
        })
    },

    /**
     * Determine if the relatd resource is soft deleting.
     */
    determineIfSoftDeletes() {
      return storage
        .determineIfSoftDeletes(this.field.resourceName)
        .then(response => {
          this.softDeletes = response.data.softDeletes
        })
    },

    /**
     * Determine if the given value is numeric.
     */
    isNumeric(value) {
      return !isNaN(parseFloat(value)) && isFinite(value)
    },

    /**
     * Toggle the trashed state of the search
     */
    toggleWithTrashed() {
      let currentlySelectedResourceId

      if (filled(this.selectedResourceId)) {
        currentlySelectedResourceId = this.selectedResourceId
      }

      this.withTrashed = !this.withTrashed

      this.selectedResourceId = null

      if (!this.useSearchInput) {
        this.getAvailableResources().then(() => {
          let index = findIndex(this.availableResources, r => {
            return r.value === currentlySelectedResourceId
          })

          if (index > -1) {
            this.selectedResourceId = currentlySelectedResourceId
          } else {
            // We didn't find the resource anymore, so let's remove the selection...
            this.selectedResourceId = null
          }
        })
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
      this.initializingWithExistingResource = true
      this.createdViaRelationModal = true
      this.getAvailableResources().then(() => {
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
      const id = this.selectedResourceId

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
          this.selectedResourceId = id
          this.createdViaRelationModal = false
          this.initializingWithExistingResource = true
        } else if (this.editingExistingResource) {
          this.initializingWithExistingResource = false
        }

        if (
          (!this.isSearchable || this.shouldLoadFirstResource) &&
          this.currentlyIsVisible
        ) {
          this.getAvailableResources()
        }
      }
    },

    revertSyncedFieldToPreviousValue(field) {
      this.syncedField.belongsToId = field.belongsToId
    },

    onSyncedField() {
      if (this.viaRelatedResource) {
        return
      }

      this.initializeComponent()
    },

    emitOnSyncedFieldValueChange() {
      if (this.viaRelatedResource) {
        return
      }

      this.emitFieldValueChange(this.fieldAttribute, this.selectedResourceId)
    },

    syncedFieldValueHasNotChanged() {
      return this.isSelectedResourceId(this.currentField.value)
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
     * Determine if we are editing and existing resource
     */
    editingExistingResource() {
      return filled(this.field.belongsToId)
    },

    /**
     * Determine if we are creating a new resource via a parent relation
     */
    viaRelatedResource() {
      return Boolean(
        this.viaResource === this.field.resourceName &&
          this.field.reverse &&
          this.viaResourceId
      )
    },

    /**
     * Determine if we should select an initial resource when mounting this field
     */
    shouldSelectInitialResource() {
      return Boolean(
        this.editingExistingResource ||
          this.viaRelatedResource ||
          this.currentField.value
      )
    },

    /**
     * Determine if the related resources is searchable
     */
    isSearchable() {
      return Boolean(this.currentField.searchable)
    },

    /**
     * Get the query params for getting available resources
     */
    queryParams() {
      return {
        current: this.selectedResourceId,
        first: this.shouldLoadFirstResource,
        search: this.search,
        withTrashed: this.withTrashed,
        resourceId: this.resourceId,
        viaResource: this.viaResource,
        viaResourceId: this.viaResourceId,
        viaRelationship: this.viaRelationship,
        component: this.field.dependentComponentKey,
        dependsOn: this.encodedDependentFieldValues,
        editing: true,
        editMode: !filled(this.resourceId) ? 'create' : 'update',
      }
    },

    shouldLoadFirstResource() {
      return (
        (this.initializingWithExistingResource &&
          !this.shouldIgnoreViaRelatedResource) ||
        Boolean(this.currentlyIsReadonly && this.selectedResourceId)
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

    authorizedToCreate() {
      return Nova.config('resources').find(resource => {
        return resource.uriKey === this.field.resourceName
      }).authorizedToCreate
    },

    canShowNewRelationModal() {
      return (
        this.currentField.showCreateRelationButton &&
        !this.shownViaNewRelationModal &&
        !this.viaRelatedResource &&
        !this.currentlyIsReadonly &&
        this.authorizedToCreate
      )
    },

    /**
     * Return the placeholder text for the field.
     */
    placeholder() {
      return this.currentField.placeholder || this.__('—')
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

    shouldIgnoreViaRelatedResource() {
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
