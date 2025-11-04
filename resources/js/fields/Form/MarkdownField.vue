<template>
  <DefaultField
    :field="currentField"
    :errors="errors"
    :full-width-content="fullWidthContent"
    :show-help-text="showHelpText"
  >
    <template #field>
      <MarkdownEditor
        ref="theMarkdownEditor"
        v-show="currentlyIsVisible"
        :class="{ 'form-control-bordered-error': hasError }"
        :attribute="field.attribute"
        :previewer="previewer"
        :uploader="uploader"
        :readonly="currentlyIsReadonly"
        @file-removed="handleFileRemoved"
        @file-added="handleFileAdded"
        @initialize="initialize"
        @change="handleChange"
      />
    </template>
  </DefaultField>
</template>

<script>
import {
  DependentFormField,
  HandlesFieldAttachments,
  HandlesFieldPreviews,
  HandlesValidationErrors,
  mapProps,
} from '@/mixins'

export default {
  mixins: [
    HandlesValidationErrors,
    HandlesFieldAttachments,
    HandlesFieldPreviews,
    DependentFormField,
  ],

  props: mapProps(['resourceName', 'resourceId', 'mode']),

  beforeUnmount() {
    Nova.$off(this.fieldAttributeValueEventName, this.listenToValueChanges)

    this.clearAttachments()
    this.clearFilesMarkedForRemoval()
  },

  methods: {
    initialize() {
      this.$refs.theMarkdownEditor.setValue(
        this.value ?? this.currentField.value
      )

      Nova.$on(this.fieldAttributeValueEventName, this.listenToValueChanges)
    },

    fill(formData) {
      this.fillIfVisible(formData, this.fieldAttribute, this.value || '')

      this.fillAttachmentDraftId(formData)
    },

    handleFileRemoved(url) {
      this.flagFileForRemoval(url)
    },

    handleFileAdded(url) {
      this.unflagFileForRemoval(url)
    },

    handleChange(value) {
      this.value = value

      if (this.field) {
        this.emitFieldValueChange(this.fieldAttribute, this.value)
      }
    },

    onSyncedField() {
      if (this.currentlyIsVisible && this.$refs.theMarkdownEditor) {
        this.$refs.theMarkdownEditor.setValue(
          this.currentField.value ?? this.value
        )
        this.$refs.theMarkdownEditor.setOption(
          'readOnly',
          this.currentlyIsReadonly
        )
      }
    },

    listenToValueChanges(value) {
      if (this.currentlyIsVisible) {
        this.$refs.theMarkdownEditor.setValue(value)
      }

      this.handleChange(value)
    },
  },

  computed: {
    previewer() {
      if (!this.isActionRequest) {
        return this.fetchPreviewContent
      }
    },

    uploader() {
      if (!this.isActionRequest && this.field.withFiles) {
        return this.uploadAttachment
      }
    },
  },
}
</script>
