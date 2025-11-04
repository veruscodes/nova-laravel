<template>
  <div>
    <input
      class="visually-hidden"
      :dusk="$attrs['input-dusk']"
      @change.prevent="handleChange"
      type="file"
      ref="fileInput"
      :multiple="multiple"
      :accept="acceptedTypes"
      :disabled="disabled"
      tabindex="-1"
    />

    <div class="space-y-4">
      <div v-if="files.length > 0" class="grid grid-cols-4 gap-x-6 gap-y-2">
        <FilePreviewBlock
          v-for="(file, index) in files"
          :key="index"
          :file="file"
          @removed="() => handleRemove(index)"
          :rounded="rounded"
          :dusk="$attrs.dusk"
        />
      </div>

      <div
        tabindex="0"
        role="button"
        @click="handleClick"
        @keydown.space.prevent="handleClick"
        @keydown.enter.prevent="handleClick"
        class="focus:outline-none focus:!border-primary-500 block cursor-pointer p-4 bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-900 border-4 border-dashed hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600 rounded-lg"
        :class="{ 'border-gray-300 dark:border-gray-600': startedDrag }"
        @dragenter.prevent="handleOnDragEnter"
        @dragleave.prevent="handleOnDragLeave"
        @dragover.prevent
        @drop.prevent="handleOnDrop"
      >
        <div class="flex items-center space-x-4 pointer-events-none">
          <p class="text-center pointer-events-none">
            <Button
              as="div"
              :leading-icon="
                multiple ? 'arrow-up-on-square-stack' : 'arrow-up-tray'
              "
            >
              {{ multiple ? __('Choose Files') : __('Choose File') }}
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
  </div>
</template>

<script setup>
import { Button } from 'laravel-nova-ui'
import { ref } from 'vue'
import { useLocalization } from '@/composables/useLocalization'
import { useDragAndDrop } from '@/composables/useDragAndDrop'

defineOptions({
  inheritAttrs: false,
})

const emitter = defineEmits(['fileChanged', 'fileRemoved'])

const props = defineProps({
  files: { type: Array, default: [] },
  multiple: { type: Boolean, default: false },
  rounded: { type: Boolean, default: false },
  acceptedTypes: { type: String, default: null },
  disabled: { type: Boolean, default: false },
})

const { __ } = useLocalization()

const { startedDrag, handleOnDragEnter, handleOnDragLeave } =
  useDragAndDrop(emitter)

const demFiles = ref([])
const fileInput = ref()

const handleClick = () => fileInput.value.click()

const handleOnDrop = e => {
  demFiles.value = props.multiple
    ? e.dataTransfer.files
    : [e.dataTransfer.files[0]]

  emitter('fileChanged', demFiles.value)
}

const handleChange = () => {
  demFiles.value = props.multiple
    ? fileInput.value.files
    : [fileInput.value.files[0]]
  emitter('fileChanged', demFiles.value)
  fileInput.value.files = null
}

const handleRemove = index => {
  emitter('fileRemoved', index)
  fileInput.value.files = null
  fileInput.value.value = null
}
</script>
