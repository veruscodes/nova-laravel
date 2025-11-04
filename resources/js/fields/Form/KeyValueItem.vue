<template>
  <div v-if="isNotObject" class="flex items-center key-value-item">
    <div
      class="flex flex-grow border-b border-gray-200 dark:border-gray-700 key-value-fields"
    >
      <div
        class="flex-none w-48"
        @click="handleKeyFieldFocus"
        :class="[
          !isEditable || readOnlyKeys
            ? disabledBackgroundColors
            : defaultBackgroundColors,
          editMode === true ? 'cursor-text' : 'cursor-default',
        ]"
      >
        <textarea
          rows="1"
          :dusk="`key-value-key-${index}`"
          v-model="item.key"
          @focus="handleKeyFieldFocus"
          ref="keyField"
          type="text"
          class="font-mono text-xs resize-none block w-full px-3 py-3 dark:text-gray-400 bg-clip-border"
          :readonly="!isFormEditable || readOnlyKeys"
          :tabindex="!isFormEditable || readOnlyKeys ? -1 : 0"
          style="background-clip: border-box"
          :class="[
            !isEditable || readOnlyKeys
              ? `${disabledBackgroundColors} focus:outline-none cursor-normal`
              : defaultBackgroundColors,
            editMode === true
              ? 'hover:bg-20 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring focus:ring-inset'
              : 'focus:outline-none cursor-default',
          ]"
        />
      </div>

      <div
        @click="handleValueFieldFocus"
        class="flex-grow border-l border-gray-200 dark:border-gray-700"
        :class="[
          !isEditable ? disabledBackgroundColors : defaultBackgroundColors,
          editMode === true ? 'cursor-text' : 'cursor-default',
        ]"
      >
        <textarea
          rows="1"
          :dusk="`key-value-value-${index}`"
          v-model="item.value"
          @focus="handleValueFieldFocus"
          ref="valueField"
          type="text"
          class="font-mono text-xs block w-full px-3 py-3 dark:text-gray-400 bg-clip-border"
          :readonly="!isFormEditable"
          :tabindex="!isFormEditable ? -1 : 0"
          :class="[
            !isEditable
              ? `${disabledBackgroundColors} focus:outline-none cursor-normal`
              : defaultBackgroundColors,
            editMode === true
              ? 'hover:bg-20 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring focus:ring-inset'
              : 'focus:outline-none cursor-default',
          ]"
        />
      </div>
    </div>

    <div
      v-if="isEditable && canDeleteRow"
      class="flex items-center h-11 w-11 absolute -right-[50px]"
    >
      <Button
        @click="$emit('remove-row', item.id)"
        :dusk="`remove-key-value-${index}`"
        variant="link"
        size="small"
        state="danger"
        type="button"
        tabindex="0"
        :title="__('Delete')"
        icon="minus-circle"
      />
    </div>
  </div>
</template>

<script>
import autosize from 'autosize'
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['remove-row'],

  props: {
    index: Number,
    item: Object,
    editMode: {
      type: Boolean,
      default: true,
    },
    readOnly: {
      type: Boolean,
      default: false,
    },
    readOnlyKeys: {
      type: Boolean,
      default: false,
    },
    canDeleteRow: {
      type: Boolean,
      default: true,
    },
  },

  mounted() {
    this.$nextTick(() => {
      autosize(this.$refs.keyField)
      autosize(this.$refs.valueField)
    })
  },

  methods: {
    handleKeyFieldFocus() {
      autosize(this.$refs.keyField)
      this.$refs.keyField.select()
    },

    handleValueFieldFocus() {
      autosize(this.$refs.valueField)
      this.$refs.valueField.select()
    },
  },

  computed: {
    isNotObject() {
      return !(this.item.value instanceof Object)
    },

    isEditable() {
      return !this.readOnly
    },

    isFormEditable() {
      return this.isEditable && this.editMode === true
    },

    defaultBackgroundColors() {
      return 'bg-white dark:bg-gray-900'
    },

    disabledBackgroundColors() {
      return this.editMode === true
        ? 'bg-gray-50 dark:bg-gray-700'
        : this.defaultBackgroundColors
    },
  },
}
</script>
