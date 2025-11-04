<template>
  <button
    type="button"
    @click.stop="handleClick"
    class="block w-full flex items-center text-left rounded px-1 py-1"
    :class="{
      'hover:bg-gray-50 dark:hover:bg-gray-700': withPreview,
      '!cursor-default': !withPreview,
    }"
  >
    <div class="flex items-center space-x-3">
      <Avatar v-if="tag.avatar" :src="tag.avatar" :rounded="true" medium />

      <div>
        <p class="text-xs font-semibold">{{ tag.display }}</p>
        <p class="text-xs" v-if="withSubtitles">{{ tag.subtitle }}</p>
      </div>
    </div>

    <button
      v-if="editable"
      @click.stop="$emit('tag-removed', index)"
      type="button"
      class="flex inline-flex items-center justify-center appearance-none cursor-pointer ml-auto text-red-500 hover:text-red-600 active:outline-none focus:ring focus:ring-primary-200 focus:outline-none rounded"
    >
      <Icon name="minus-circle" type="solid" class="hover:opacity-50" />
    </button>

    <PreviewResourceModal
      v-if="withPreview"
      @close="handleClick"
      :show="shown"
      :resource-id="tag.value"
      :resource-name="resourceName"
    />
  </button>
</template>

<script setup>
import { Icon } from 'laravel-nova-ui'
import { ref } from 'vue'

const shown = ref(false)

const props = defineProps({
  resourceName: { type: String },
  index: { type: Number, required: true },
  tag: { type: Object, required: true },
  editable: { type: Boolean, default: true },
  withSubtitles: { type: Boolean, default: true },
  withPreview: { type: Boolean, default: false },
})

defineEmits(['tag-removed', 'click'])

function handleClick() {
  if (props.withPreview) {
    shown.value = !shown.value
  }
}
</script>
