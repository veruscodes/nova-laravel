<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :full-width-content="fullWidthContent"
    :show-help-text="showHelpText"
  >
    <template #field>
      <div class="space-y-1">
        <textarea
          v-bind="extraAttributes"
          :id="currentField.uniqueKey"
          :value="value"
          :maxlength="field.enforceMaxlength ? field.maxlength : -1"
          @input="handleChange"
          class="w-full h-auto py-3 block form-control form-input form-control-bordered"
          :dusk="field.attribute"
          :disabled="currentlyIsReadonly"
        />

        <CharacterCounter
          v-if="field.maxlength"
          :count="value.length"
          :limit="field.maxlength"
        />
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from '@/mixins'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],

  computed: {
    defaultAttributes() {
      return {
        rows: this.currentField.rows,
        class: this.errorClasses,
        placeholder: this.placeholder,
      }
    },

    extraAttributes() {
      return {
        ...this.defaultAttributes,
        ...this.currentField.extraAttributes,
      }
    },
  },
}
</script>
