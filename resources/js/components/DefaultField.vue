<template>
  <div v-if="field.visible" :class="fieldWrapperClasses">
    <div v-if="field.withLabel" :class="labelClasses">
      <slot
        name="default"
        :for="fieldLabelFor"
        :label="fieldLabel"
        :required="field.required"
        :hasHelpText="shouldShowHelpText"
      >
        <FormLabel
          :label-for="fieldLabelFor"
          class="space-x-1"
          :class="{ 'mb-2': shouldShowHelpText }"
          @click.prevent.stop="$emit('focus-form-input')"
        >
          <span>
            {{ fieldLabel }}
          </span>
          <span v-if="field.required" class="text-red-500 text-sm">
            {{ __('*') }}
          </span>
        </FormLabel>
      </slot>
    </div>

    <div :class="controlWrapperClasses">
      <slot name="field" />

      <HelpText class="help-text-error" v-if="showErrors && hasError">
        {{ firstError }}
      </HelpText>

      <HelpText
        class="help-text"
        v-if="shouldShowHelpText"
        v-html="field.helpText"
      />
    </div>
  </div>
</template>

<script>
import { HandlesValidationErrors, mapProps } from '@/mixins'

export default {
  mixins: [HandlesValidationErrors],

  emits: ['focus-form-input'],

  props: {
    field: { type: Object, required: true },
    fieldName: { type: String },
    showErrors: { type: Boolean, default: true },
    fullWidthContent: { type: Boolean, default: false },
    labelFor: { default: null },
    ...mapProps(['showHelpText']),
  },

  computed: {
    /**
     * HTML classes for field wrapper.
     *
     * @returns {string[]}
     */
    fieldWrapperClasses() {
      // prettier-ignore
      return [
        'space-y-2',
        'md:flex @md/modal:flex',
        'md:flex-row @md/modal:flex-row',
        'md:space-y-0 @md/modal:space-y-0',
        this.field.withLabel && !this.field.inline && (this.field.compact ? 'py-3' : 'py-5'),
        this.field.stacked && 'md:flex-col @md/modal:flex-col md:space-y-2 @md/modal:space-y-2',
      ]
    },

    /**
     * HTML classes for label.
     *
     * @returns {string[]}
     */
    labelClasses() {
      // prettier-ignore
      return [
        'w-full',
        this.field.compact ? '!px-3' : 'px-6',
        !this.field.stacked && 'md:mt-2 @md/modal:mt-2',
        this.field.stacked && !this.field.inline && 'md:px-8 @md/modal:px-8',
        !this.field.stacked && !this.field.inline && 'md:px-8 @md/modal:px-8',
        this.field.compact && 'md:!px-6 @md/modal:!px-6',
        !this.field.stacked && !this.field.inline && 'md:w-1/5 @md/modal:w-1/5',
      ]
    },

    /**
     * HTML classes for control wrapper.
     *
     * @returns {string[]}
     */
    controlWrapperClasses() {
      // prettier-ignore
      return [
        'w-full space-y-2',
        this.field.compact ? '!px-3' : 'px-6',
        this.field.compact && 'md:!px-4 @md/modal:!px-4',
        this.field.stacked && !this.field.inline && 'md:px-8 @md/modal:px-8',
        !this.field.stacked && !this.field.inline && 'md:px-8 @md/modal:px-8',
        !this.field.stacked && !this.field.inline && !this.field.fullWidth && 'md:w-3/5 @md/modal:w-3/5',
        this.field.stacked && !this.field.inline && !this.field.fullWidth && 'md:w-3/5 @md/modal:w-3/5',
        !this.field.stacked && !this.field.inline && this.field.fullWidth && 'md:w-4/5 @md/modal:w-4/5',
      ]
    },

    /**
     * Return the label that should be used for the field.
     *
     * @returns {string}
     */
    fieldLabel() {
      // If the field name is purposefully an empty string, then let's show it as such
      if (this.fieldName === '') {
        return ''
      }

      return this.fieldName || this.field.name || this.field.singularLabel
    },

    /**
     * Return the label target for the field.
     *
     * @returns {string}
     */
    fieldLabelFor() {
      return this.labelFor || this.field.uniqueKey
    },

    /**
     * Determine help text should be shown.
     *
     * @returns {boolean}
     */
    shouldShowHelpText() {
      return this.showHelpText && this.field.helpText?.length > 0
    },
  },
}
</script>
