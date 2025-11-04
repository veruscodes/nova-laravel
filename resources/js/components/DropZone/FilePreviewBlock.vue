<template>
  <div class="h-full flex items-start justify-center">
    <div class="relative w-full">
      <!-- Remove Button -->
      <button
        v-if="removable"
        type="button"
        class="absolute z-20 top-[-10px] right-[-9px] rounded-full shadow bg-white dark:bg-gray-800 text-center flex items-center justify-center h-[20px] w-[21px]"
        @click.stop="$emit('removed')"
        v-tooltip="__('Remove')"
        :dusk="$attrs.dusk"
      >
        <Icon
          name="x-circle"
          type="solid"
          class="text-gray-800 dark:text-gray-200"
        />
      </button>

      <div
        class="bg-gray-50 dark:bg-gray-700 relative aspect-square flex items-center justify-center border-2 border-gray-200 dark:border-gray-700 overflow-hidden rounded-lg"
      >
        <!-- Upload Overlay -->
        <div
          v-if="file.processing"
          class="absolute inset-0 flex items-center justify-center"
        >
          <ProgressBar
            :title="uploadingLabel"
            class="mx-4"
            color="bg-green-500"
            :value="uploadingPercentage"
          />
          <div class="bg-primary-900 opacity-5 absolute inset-0" />
        </div>

        <!-- Image Preview -->
        <img
          v-if="isImage"
          :src="previewUrl"
          class="aspect-square object-scale-down"
        />
        <div v-else>
          <div class="rounded bg-gray-200 border-2 border-gray-200 p-4">
            <Icon name="document-text" class="!w-[50px] !h-[50px]" />
          </div>
        </div>
      </div>

      <!-- File Information -->
      <p class="font-semibold text-xs mt-1">{{ file.name }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, toRef } from 'vue'
import { Icon } from 'laravel-nova-ui'
import { useFilePreviews } from '@/composables/useFilePreviews'
import { useLocalization } from '@/composables/useLocalization'

defineOptions({
  inheritAttrs: false,
})

defineEmits(['removed'])

const props = defineProps({
  file: { type: Object },
  removable: { type: Boolean, default: true },
})

const { __ } = useLocalization()

const uploadingLabel = computed(() => {
  if (props.file.processing) {
    return __('Uploading') + ' (' + props.file.progress + '%)'
  }

  return props.file.name
})

const uploadingPercentage = computed(() => {
  if (props.file.processing) {
    return props.file.progress
  }

  return 100
})

const { previewUrl, isImage } = useFilePreviews(toRef(props, 'file'))
</script>
