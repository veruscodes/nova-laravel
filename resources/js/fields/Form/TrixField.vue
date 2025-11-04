<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :full-width-content="fullWidthContent"
    :key="trixIndex"
    :show-help-text="showHelpText"
  >
    <template #field>
      <div class="rounded-lg" :class="{ disabled: currentlyIsReadonly }">
        <Trix
          v-bind="extraAttributes"
          name="trixman"
          :value="value"
          :with-files="currentField.withFiles"
          :disabled="currentlyIsReadonly"
          @change="handleChange"
          @file-added="handleFileAdded"
          @file-removed="handleFileRemoved"
          class="rounded-lg"
          :class="{ 'form-control-bordered-error': hasError }"
        />
      </div>
    </template>
  </DefaultField>
</template>

<script>
import {
  DependentFormField,
  HandlesFieldAttachments,
  HandlesValidationErrors,
} from '@/mixins'

export default {
  emits: ['field-changed'],

  mixins: [
    HandlesValidationErrors,
    HandlesFieldAttachments,
    DependentFormField,
  ],

  data: () => ({ trixIndex: 0 }),

  mounted() {
    Nova.$on(this.fieldAttributeValueEventName, this.listenToValueChanges)
  },

  beforeUnmount() {
    Nova.$off(this.fieldAttributeValueEventName, this.listenToValueChanges)

    this.clearAttachments()
    this.clearFilesMarkedForRemoval()
  },

  methods: {
    /**
     * Update the field's internal value when it's value changes
     */
    handleChange(value) {
      this.value = value

      this.$emit('field-changed')
    },

    fill(formData) {
      this.fillIfVisible(formData, this.fieldAttribute, this.value || '')

      this.fillAttachmentDraftId(formData)
    },

    /**
     * Initiate an attachement upload
     */
    handleFileAdded({ attachment }) {
      if (attachment.file) {
        // Trix provides file if it's an upload
        const onCompleted = (path, url) => {
          return attachment.setAttributes({
            url: url,
            href: url,
          })
        }

        const onUploadProgress = progressEvent => {
          attachment.setUploadProgress(
            Math.round((progressEvent.loaded * 100) / progressEvent.total)
          )
        }

        this.uploadAttachment(attachment.file, {
          onCompleted,
          onUploadProgress,
        })
      } else {
        // fx 'undo' which restores a previous attachment without a file upload
        this.unflagFileForRemoval(attachment.attachment.attributes.values.url)
      }
    },

    handleFileRemoved({ attachment: { attachment } }) {
      this.flagFileForRemoval(attachment.attributes.values.url)
    },

    onSyncedField() {
      this.handleChange(this.currentField.value ?? this.value)
      this.trixIndex++
    },

    listenToValueChanges(value) {
      this.trixIndex++
    },
  },

  computed: {
    extraAttributes() {
      return {
        // Leave the default attributes even though we can now specify
        // whatever attributes we like because the old number field still
        // uses the old field attributes
        placeholder: this.placeholder,
        ...this.currentField.extraAttributes,
      }
    },
  },
}
</script>
