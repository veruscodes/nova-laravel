<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div class="flex flex-wrap items-stretch w-full relative">
        <div class="flex -mr-px">
          <span
            class="flex items-center leading-normal rounded rounded-r-none border border-r-0 border-gray-300 dark:border-gray-700 px-3 whitespace-nowrap bg-gray-100 dark:bg-gray-800 text-gray-500 text-sm font-bold"
          >
            {{ currentField.currency }}
          </span>
        </div>

        <input
          v-bind="extraAttributes"
          :id="currentField.uniqueKey"
          :value="value"
          :disabled="currentlyIsReadonly"
          @input="handleChange"
          class="flex-shrink flex-grow flex-auto leading-normal w-px flex-1 rounded-l-none form-control form-input form-control-bordered"
          :dusk="field.attribute"
        />
      </div>
    </template>
  </DefaultField>
</template>

<script>
import { DependentFormField, HandlesValidationErrors } from '@/mixins'

export default {
  mixins: [HandlesValidationErrors, DependentFormField],

  props: ['resourceName', 'resourceId', 'field'],

  computed: {
    defaultAttributes() {
      return {
        type: 'number',
        min: this.currentField.min,
        max: this.currentField.max,
        step: this.currentField.step,
        pattern: this.currentField.pattern,
        placeholder: this.placeholder,
        class: this.errorClasses,
      }
    },

    extraAttributes() {
      return {
        // Leave the default attributes even though we can now specify
        // whatever attributes we like because the old number field still
        // uses the old field attributes
        ...this.defaultAttributes,
        ...this.currentField.extraAttributes,
      }
    },
  },
}
</script>
