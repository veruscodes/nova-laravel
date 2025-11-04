<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <audio
        v-if="hasPreviewableAudio"
        v-bind="defaultAttributes"
        class="w-full"
        :src="field.previewUrl"
        controls
        controlslist="nodownload"
      />

      <span v-if="!hasPreviewableAudio">&mdash;</span>

      <p v-if="shouldShowToolbar" class="flex items-center text-sm mt-3">
        <a
          v-if="field.downloadable"
          :dusk="field.attribute + '-download-link'"
          @keydown.enter.prevent="download"
          @click.prevent="download"
          tabindex="0"
          class="cursor-pointer text-gray-500 inline-flex items-center"
        >
          <Icon name="download" type="micro" class="mr-2" />
          <span class="class mt-1">{{ __('Download') }}</span>
        </a>
      </p>
    </template>
  </PanelItem>
</template>

<script>
import { Icon } from 'laravel-nova-ui'
import { FieldValue } from '@/mixins'

export default {
  components: {
    Icon,
  },

  mixins: [FieldValue],

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  methods: {
    /**
     * Download the linked file
     */
    download() {
      const { resourceName, resourceId } = this
      const attribute = this.field.attribute

      let link = document.createElement('a')
      link.href = `/nova-api/${resourceName}/${resourceId}/download/${attribute}`
      link.download = 'download'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    },
  },

  computed: {
    hasPreviewableAudio() {
      return this.field.previewUrl != null
    },

    shouldShowToolbar() {
      return Boolean(this.field.downloadable && this.fieldHasValue)
    },

    defaultAttributes() {
      return {
        src: this.field.previewUrl,
        autoplay: this.field.autoplay,
        preload: this.field.preload,
      }
    },
  },
}
</script>
