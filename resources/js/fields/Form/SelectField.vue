<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <!-- Search Input -->
      <SearchInput
        v-if="!currentlyIsReadonly && isSearchable"
        v-model="value"
        @selected="selectOption"
        @input="performSearch"
        @clear="clearSelection"
        :options="filteredOptions"
        :disabled="currentlyIsReadonly"
        :has-error="hasError"
        :clearable="currentField.nullable"
        trackBy="value"
        :mode="mode"
        class="w-full"
        :dusk="`${field.attribute}-search-input`"
        :autocomplete="currentField.autocomplete"
      >
        <template #default>
          <!-- The Selected Option Slot -->
          <div v-if="selectedOption" class="flex items-center">
            {{ selectedOption.label }}
          </div>
        </template>

        <template #option="{ selected, option }">
          <!-- Options List Slot -->
          <div
            class="flex items-center text-sm font-semibold leading-5"
            :class="{ 'text-white': selected }"
          >
            {{ option.label }}
          </div>
        </template>
      </SearchInput>

      <!-- Select Input Field -->
      <SelectControl
        v-else
        v-model="value"
        @selected="selectOption"
        :options="currentField.options"
        :has-error="hasError"
        :disabled="currentlyIsReadonly"
        :id="field.attribute"
        class="w-full"
        :dusk="field.attribute"
      >
        <option value="" selected :disabled="!currentField.nullable">
          {{ placeholder }}
        </option>
      </SelectControl>
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from '@/mixins'
import first from 'lodash/first'
import filled from '@/util/filled'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],

  data: () => ({
    value: null,
    search: '',
  }),

  created() {
    this.value = this.field.value ?? this.fieldDefaultValue()
  },

  methods: {
    /**
     * Return the field default value.
     */
    fieldDefaultValue() {
      return null
    },

    /**
     * Provide a function that fills a passed FormData object with the
     * field's internal value attribute. Here we are forcing there to be a
     * value sent to the server instead of the default behavior of
     * `this.value || ''` to avoid loose-comparison issues if the keys
     * are truthy or falsey
     *
     * @param {FormData} formData
     */
    fill(formData) {
      this.fillIfVisible(formData, this.fieldAttribute, this.value ?? '')
    },

    /**
     * Set the search string to be used to filter the select field.
     *
     * @param {any} event
     */
    performSearch(event) {
      this.search = event
    },

    /**
     * Clear the current selection for the field.
     */
    clearSelection() {
      this.value = this.fieldDefaultValue()

      if (this.field) {
        this.emitFieldValueChange(this.fieldAttribute, this.value)
      }
    },

    /**
     * Select the given option.
     *
     * @param {Object} option
     */
    selectOption(option) {
      if (option == null) {
        this.clearSelection()
        return
      }

      if (this.field) {
        this.emitFieldValueChange(this.fieldAttribute, this.value)
      }
    },

    /**
     *  Set value using the given option.
     *
     * @param {Object} option
     */
    selectedValueFromOption(option) {
      this.value = option?.value ?? this.fieldDefaultValue()
      this.selectOption(option)
    },

    /**
     * Handle on synced field.
     */
    onSyncedField() {
      let currentSelectedOption = null
      let hasValue = false

      if (this.selectedOption) {
        hasValue = true
        currentSelectedOption = this.currentField.options.find(
          v => v.value === this.selectedOption.value
        )
      }

      let selectedOption = this.currentField.options.find(
        v => v.value == this.currentField.value
      )

      if (currentSelectedOption == null) {
        this.clearSelection()

        if (this.currentField.value) {
          this.selectedValueFromOption(selectedOption)
        } else if (hasValue && !this.currentField.nullable) {
          this.selectedValueFromOption(first(this.currentField.options))
        }

        return
      } else if (currentSelectedOption && selectedOption) {
        this.selectedValueFromOption(selectedOption)

        return
      }

      this.selectedValueFromOption(currentSelectedOption)
    },
  },

  computed: {
    /**
     * Determine if the related resources is searchable.
     *
     * @returns {boolean}
     */
    isSearchable() {
      return this.currentField.searchable
    },

    /**
     * Return the field options filtered by the search string.
     *
     * @returns {Object[]}
     */
    filteredOptions() {
      return this.currentField.options.filter(option => {
        return (
          option.label
            .toString()
            .toLowerCase()
            .indexOf(this.search.toLowerCase()) > -1
        )
      })
    },

    /**
     * Return the placeholder text for the field.
     *
     * @return {string}
     */
    placeholder() {
      return this.currentField.placeholder || this.__('Choose an option')
    },

    /**
     * Determine if the field has a non-empty value.
     *
     * @return {boolean}
     */
    hasValue() {
      return Boolean(
        !(this.value === undefined || this.value === null || this.value === '')
      )
    },

    /**
     * Get the selected option.
     *
     * @return {Object}
     */
    selectedOption() {
      return this.currentField.options.find(
        o => this.value === o.value || this.value === o.value.toString()
      )
    },
  },
}
</script>
