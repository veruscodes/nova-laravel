<template>
  <div class="flex relative" :class="$attrs.class">
    <select
      v-bind="defaultAttributes"
      :value="modelValue"
      @change="handleChange"
      class="w-full block form-control form-control-bordered form-input"
      ref="selectControl"
      :disabled="disabled"
      :class="{
        'h-8 text-xs': size === 'sm',
        'h-7 text-xs': size === 'xs',
        'h-6 text-xs': size === 'xxs',
        'form-control-bordered-error': hasError,
        'form-input-disabled': disabled,
      }"
      :data-disabled="disabled ? 'true' : null"
    >
      <slot />
      <template v-for="(options, group) in groupedOptions">
        <optgroup :label="group" v-if="group" :key="group">
          <option
            v-bind="attrsFor(option)"
            v-for="option in options"
            :key="option.value"
            :selected="isSelected(option)"
            :disabled="isDisabled(option)"
          >
            {{ labelFor(option) }}
          </option>
        </optgroup>
        <template v-else>
          <option
            v-bind="attrsFor(option)"
            v-for="option in options"
            :key="option.value"
            :selected="isSelected(option)"
            :disabled="isDisabled(option)"
          >
            {{ labelFor(option) }}
          </option>
        </template>
      </template>
    </select>

    <span
      class="pointer-events-none absolute inset-y-0 right-[11px] flex items-center"
    >
      <IconArrow />
    </span>
  </div>
</template>

<script setup>
import { computed, onBeforeMount, useAttrs, useTemplateRef } from 'vue'
import groupBy from 'lodash/groupBy'
import omit from 'lodash/omit'

defineOptions({
  inheritAttrs: false,
})

const emitter = defineEmits(['selected'])

const props = defineProps({
  hasError: { type: Boolean, default: false },
  label: { default: 'label' },
  value: { default: null },
  options: { type: Array, default: [] },
  disabled: { type: Boolean, default: false },
  size: {
    type: String,
    default: 'md',
    validator: val => ['xxs', 'xs', 'sm', 'md'].includes(val),
  },
})

const modelValue = defineModel()

const attrs = useAttrs()

const selectControlRef = useTemplateRef('selectControl')

onBeforeMount(() => {
  if (modelValue.value == null && props.value != null) {
    modelValue.value = props.value
  }
})

const labelFor = option => {
  return props.label instanceof Function
    ? props.label(option)
    : option[props.label]
}

const attrsFor = option => {
  return {
    ...(option.attrs || {}),
    ...{ value: option.value },
  }
}

const isSelected = option => {
  return option.value == modelValue.value
}

const isDisabled = option => {
  return option.disabled === true
}

const handleChange = event => {
  let value = event.target.value

  let selectedValue = props.options.find(
    o => value === o.value || value === o.value.toString()
  )

  modelValue.value = selectedValue?.value ?? props.value
  emitter('selected', selectedValue)
}

const resetSelection = () => {
  selectControlRef.value.selectedIndex = 0
}

const defaultAttributes = computed(() => omit(attrs, ['class']))
const groupedOptions = computed(() =>
  groupBy(props.options, option => option.group || '')
)

defineExpose({
  resetSelection,
})
</script>
