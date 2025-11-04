<template>
  <DefaultField
    :field="field"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="fullWidthContent"
  >
    <template #field>
      <div class="flex items-center">
        <input
          v-bind="extraAttributes"
          ref="theInput"
          :value="value"
          @blur="handleChangesOnBlurEvent"
          @keyup.enter="handleChangeOnPressingEnterEvent"
          @keydown.enter="handleChangeOnPressingEnterEvent"
          @keydown="handleChangeOnKeyPressEvent"
          :id="field.uniqueKey"
          :disabled="isReadonly"
          :readonly="isImmutable"
          class="w-full form-control form-input form-control-bordered"
          :dusk="field.attribute"
          autocomplete="off"
          spellcheck="false"
        />

        <button
          v-if="field.showCustomizeButton"
          type="button"
          @click="toggleCustomizeClick"
          :dusk="`${field.attribute}-slug-field-edit-button`"
          class="rounded inline-flex text-sm ml-3 link-default"
        >
          {{ __('Customize') }}
        </button>
      </div>
    </template>
  </DefaultField>
</template>

<script>
import {
  FormField,
  HandlesFieldPreviews,
  HandlesValidationErrors,
} from '@/mixins'
import debounce from 'lodash/debounce'
import isNil from 'lodash/isNil'

export default {
  mixins: [FormField, HandlesFieldPreviews, HandlesValidationErrors],

  data: () => ({
    isListeningToChanges: false,
    isCustomisingValue: false,
    debouncedHandleChange: null,
  }),

  mounted() {
    this.debouncedHandleChange = debounce(this.handleChange, 250)
    this.registerChangeListener()
  },

  beforeUnmount() {
    this.removeChangeListener()
  },

  methods: {
    registerChangeListener() {
      if (this.shouldRegisterInitialListener === true) {
        Nova.$on(this.eventName, this.debouncedHandleChange)

        this.isListeningToChanges = true
      }
    },

    removeChangeListener() {
      if (this.isListeningToChanges === true) {
        Nova.$off(this.eventName)
      }
    },

    handleChangeOnPressingEnterEvent(event) {
      event.preventDefault()
      event.stopPropagation()

      this.listenToValueChanges(event?.target?.value ?? event)
    },

    handleChangesOnBlurEvent(event) {
      this.listenToValueChanges(event?.target?.value ?? event)
    },

    handleChangeOnKeyPressEvent(event) {
      if (this.isImmutable === true) {
        return
      }

      this.allowCustomisingValue()
    },

    allowCustomisingValue() {
      this.isCustomisingValue = true
      this.removeChangeListener()
      this.isListeningToChanges = false
      this.field.writable = true
      this.field.extraAttributes.readonly = false
      this.field.showCustomizeButton = false
    },

    disableCustomisingValue() {
      this.isCustomisingValue = false
      this.registerChangeListener()
      this.field.writable = false
      this.field.extraAttributes.readonly = true
    },

    listenToValueChanges(value) {
      if (this.isImmutable === true) {
        return
      }

      if (this.isCustomisingValue === true) {
        this.value = value
        return
      }

      if (isNil(this.slugFromAttribute)) {
        this.debouncedHandleChange(value)
      }
    },

    async handleChange(value) {
      this.value = await this.fetchPreviewContent(value)
    },

    toggleCustomizeClick() {
      if (this.field.extraAttributes?.readonly === true) {
        this.allowCustomisingValue()
        this.$refs.theInput.focus()
        return
      }

      this.disableCustomisingValue()
    },
  },

  computed: {
    slugFromAttribute() {
      return this.field.slugFrom
    },

    shouldRegisterInitialListener() {
      return this.field.shouldListenToFromChanges
    },

    eventName() {
      return this.getFieldAttributeChangeEventName(this.slugFromAttribute)
    },

    placeholder() {
      if (isNil(this.slugFromAttribute)) {
        return this.field.placeholder ?? this.field.name
      }

      return this.field.placeholder ?? null
    },

    extraAttributes() {
      return {
        class: this.errorClasses,
        placeholder: this.placeholder,
        ...this.field.extraAttributes,
      }
    },
  },
}
</script>
