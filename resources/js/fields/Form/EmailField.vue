<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <input
        v-bind="extraAttributes"
        :value="value"
        :id="currentField.uniqueKey"
        :disabled="currentlyIsReadonly"
        @input="handleChange"
        class="w-full form-control form-input form-control-bordered"
        :autocomplete="currentField.autocomplete"
        :dusk="field.attribute"
      />
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from '@/mixins'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],

  computed: {
    extraAttributes() {
      return {
        // Leave the default attributes even though we can now specify
        // whatever attributes we like because the old number field still
        // uses the old field attributes
        type: this.currentField.type || 'email',
        pattern: this.currentField.pattern,
        placeholder: this.placeholder,
        class: this.errorClasses,
        ...this.currentField.extraAttributes,
      }
    },
  },
}
</script>
