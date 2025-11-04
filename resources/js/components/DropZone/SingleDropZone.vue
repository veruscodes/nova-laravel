<template>
  <div class="space-y-4">
    <div v-if="files.length > 0" class="grid grid-cols-4 gap-x-6">
      <FilePreviewBlock
        v-for="(file, index) in files"
        :key="index"
        :file="file"
        @removed="() => handleRemoveClick(index)"
      />
    </div>

    <div
      @click="handleClick"
      class="cursor-pointer p-4 bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-900 border-4 border-dashed hover:border-gray-300 dark:hover:border-gray-600 rounded-lg"
      :class="
        startedDrag
          ? 'border-gray-300 dark:border-gray-600'
          : 'border-gray-200 dark:border-gray-700'
      "
      @dragenter.prevent="handleOnDragEnter"
      @dragleave.prevent="handleOnDragLeave"
      @dragover.prevent
      @drop.prevent="handleOnDrop"
    >
      <div class="flex items-center space-x-4">
        <p class="text-center pointer-events-none">
          <Button as="div">
            {{ __('Choose a file') }}
          </Button>
        </p>

        <p
          class="pointer-events-none text-center text-sm text-gray-500 dark:text-gray-400 font-semibold"
        >
          {{
            multiple
              ? __('Drop files or click to choose')
              : __('Drop file or click to choose')
          }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Button } from 'laravel-nova-ui'
import { useLocalization } from '@/composables/useLocalization'
import { useDragAndDrop } from '@/composables/useDragAndDrop'

const emitter = defineEmits(['fileChanged', 'fileRemoved'])

defineProps({
  files: Array,
  handleClick: Function,
})

const { __ } = useLocalization()

const { startedDrag, handleOnDragEnter, handleOnDragLeave, handleOnDrop } =
  useDragAndDrop(emitter)

function handleRemoveClick(index) {
  emitter('fileRemoved', index)
}
</script>
