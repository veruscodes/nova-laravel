<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div class="flex items-center">
        <input
          type="date"
          class="w-56 form-control form-input form-control-bordered"
          ref="dateTimePicker"
          :id="currentField.uniqueKey"
          :dusk="field.attribute"
          :name="field.name"
          :value="value"
          :class="errorClasses"
          :disabled="currentlyIsReadonly"
          @change="handleChange"
          :min="currentField.min"
          :max="currentField.max"
          :step="currentField.step"
        />
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { DateTime } from 'luxon'
import { DependentFormField, HandlesValidationErrors } from '@/mixins'
import filled from '@/util/filled'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],

  methods: {
    /*
     * Set the initial value for the field
     */
    setInitialValue() {
      if (this.currentField.value != null) {
        this.value = DateTime.fromISO(
          this.currentField.value || this.value
        ).toISODate()
      }
    },

    /**
     * On save, populate our form data
     */
    fill(formData) {
      if (this.currentlyIsVisible) {
        this.fillIfVisible(formData, this.fieldAttribute, this.value)
      }
    },

    /**
     * Update the field's internal value
     */
    handleChange(event) {
      this.value = event?.target?.value ?? event

      if (this.field) {
        this.emitFieldValueChange(this.fieldAttribute, this.value)
      }
    },
  },
}
</script>
