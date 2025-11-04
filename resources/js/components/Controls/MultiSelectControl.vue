<template>
  <div class="flex relative" :class="$attrs.class">
    <select
      v-bind="defaultAttributes"
      ref="selectControl"
      @change="handleChange"
      class="w-full min-h-[10rem] block form-control form-control-bordered form-input"
      multiple
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
          >
            {{ labelFor(option) }}
          </option>
        </template>
      </template>
    </select>
  </div>
</template>

<script setup>
import { computed, useAttrs, useTemplateRef } from 'vue'
import groupBy from 'lodash/groupBy'
import omit from 'lodash/omit'

defineOptions({
  inheritAttrs: false,
})

const emitter = defineEmits(['selected'])

const props = defineProps({
  hasError: { type: Boolean, default: false },
  label: { default: 'label' },
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
  return modelValue.value.indexOf(option.value) > -1
}

const handleChange = event => {
  let values = Object.values(event.target.options)
    .filter(option => option.selected)
    .map(option => option.value)

  let selected = (props.options ?? []).filter(
    o => values.includes(o.value) || values.includes(o.value.toString())
  )

  modelValue.value = selected.map(o => o.value)
  emitter('selected', selected)
}

const defaultAttributes = computed(() => omit(attrs, ['class']))
const groupedOptions = computed(() =>
  groupBy(props.options, option => option.group || '')
)
</script>
