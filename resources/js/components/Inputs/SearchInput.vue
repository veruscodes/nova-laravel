<template>
  <div v-bind="$attrs" class="relative" :dusk="dusk" ref="searchInputContainer">
    <div
      ref="input"
      @click.stop="open"
      @keydown.space.prevent="open"
      @keydown.down.prevent="open"
      @keydown.up.prevent="open"
      :class="{
        'ring dark:border-gray-500 dark:ring-gray-700': dropdownShown,
        'form-input-border-error': error,
        'bg-gray-50 dark:bg-gray-700': disabled || readOnly,
      }"
      class="relative flex items-center form-control form-input form-control-bordered form-select pr-6"
      :tabindex="dropdownShown ? -1 : 0"
      :aria-expanded="dropdownShown === true ? 'true' : 'false'"
      :dusk="`${dusk}-selected`"
    >
      <span
        v-if="shouldShowDropdownArrow && !disabled"
        class="pointer-events-none absolute inset-y-0 right-[11px] flex items-center"
      >
        <IconArrow />
      </span>

      <slot name="default">
        <div class="text-gray-400 dark:text-gray-400">
          {{ __('Click to choose') }}
        </div>
      </slot>
    </div>

    <button
      type="button"
      @click="clear"
      v-if="!shouldShowDropdownArrow && !disabled"
      tabindex="-1"
      class="absolute p-2 inline-block right-[4px]"
      style="top: 6px"
      :dusk="`${dusk}-clear-button`"
    >
      <svg
        class="block fill-current icon h-2 w-2"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="278.046 126.846 235.908 235.908"
      >
        <path
          d="M506.784 134.017c-9.56-9.56-25.06-9.56-34.62 0L396 210.18l-76.164-76.164c-9.56-9.56-25.06-9.56-34.62 0-9.56 9.56-9.56 25.06 0 34.62L361.38 244.8l-76.164 76.165c-9.56 9.56-9.56 25.06 0 34.62 9.56 9.56 25.06 9.56 34.62 0L396 279.42l76.164 76.165c9.56 9.56 25.06 9.56 34.62 0 9.56-9.56 9.56-25.06 0-34.62L430.62 244.8l76.164-76.163c9.56-9.56 9.56-25.06 0-34.62z"
        />
      </svg>
    </button>
  </div>

  <teleport to="body">
    <div
      v-if="dropdownShown"
      ref="dropdown"
      class="rounded-lg px-0 bg-white dark:bg-gray-900 shadow border border-gray-200 dark:border-gray-700 absolute top-0 left-0 my-1 overflow-hidden"
      :style="{ width: inputWidth + 'px', zIndex: 2000 }"
      :dusk="`${dusk}-dropdown`"
    >
      <!-- Search Input -->
      <input
        :disabled="disabled || readOnly"
        v-model="searchText"
        ref="search"
        @keydown.enter.prevent="chooseSelected"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        class="h-10 outline-none w-full px-3 text-sm leading-normal bg-white dark:bg-gray-700 rounded-t border-b border-gray-200 dark:border-gray-800"
        tabindex="-1"
        type="search"
        :autocomplete="autocomplete"
        spellcheck="false"
        :placeholder="__('Search')"
      />

      <!-- Search Results -->
      <div
        ref="container"
        class="relative overflow-y-scroll text-sm"
        tabindex="-1"
        style="max-height: 155px"
        :dusk="`${dusk}-results`"
      >
        <div
          v-for="(option, index) in options"
          :dusk="`${dusk}-result-${index}`"
          :key="getTrackedByKey(option)"
          :ref="index === selectedOptionIndex ? 'selected' : 'unselected'"
          @click.stop="choose(option)"
          class="px-3 py-1.5 cursor-pointer z-[50]"
          :class="{
            'border-t border-gray-100 dark:border-gray-700': index !== 0,
            [`search-input-item-${index}`]: true,
            'hover:bg-gray-100 dark:hover:bg-gray-800':
              index !== selectedOptionIndex,
            'bg-primary-500 text-white dark:text-gray-900':
              index === selectedOptionIndex,
          }"
        >
          <slot
            name="option"
            :option="option"
            :selected="index === selectedOptionIndex"
          />
        </div>
      </div>
    </div>

    <Backdrop @click="close" :show="dropdownShown" :style="{ zIndex: 1999 }" />
  </teleport>
</template>

<script setup>
import { watch } from 'vue'
import {
  computed,
  nextTick,
  onBeforeMount,
  onBeforeUnmount,
  ref,
  useTemplateRef,
} from 'vue'
import { createPopper } from '@popperjs/core'
import debounce from 'lodash/debounce'
import get from 'lodash/get'
import findIndex from 'lodash/findIndex'
import { mapProps } from '@/mixins'
import { useEventListener } from '@vueuse/core'

defineOptions({
  inheritAttrs: false,
})

const emitter = defineEmits(['clear', 'input', 'shown', 'closed', 'selected'])

const props = defineProps({
  autocomplete: { type: String, required: false, default: null },
  dusk: { type: String, required: true },
  disabled: { type: Boolean, default: false },
  readOnly: { type: Boolean, default: false },
  options: {},
  trackBy: { type: String, required: true },
  error: { type: Boolean, default: false },
  boundary: {},
  debounce: { type: Number, default: 500 },
  clearable: { type: Boolean, default: true },
  ...mapProps(['mode']),
})

const modelValue = defineModel()

const debouncer = debounce(callback => callback(), props.debounce)
const dropdownShown = ref(false)
const searchText = ref('')
const selectedOptionIndex = ref(0)
const popper = ref(null)
const inputWidth = ref(null)

const containerRef = useTemplateRef('container')
const dropdownRef = useTemplateRef('dropdown')
const inputRef = useTemplateRef('input')
const searchRef = useTemplateRef('search')
const selectedRef = useTemplateRef('selected')

watch(searchText, newValue => {
  selectedOptionIndex.value = 0
  if (containerRef.value) {
    containerRef.value.scrollTop = 0
  } else {
    nextTick(() => {
      containerRef.value.scrollTop = 0
    })
  }

  debouncer(() => {
    emitter('input', newValue)
  })
})

watch(dropdownShown, show => {
  if (show) {
    let selected = findIndex(props.options, [
      props.trackBy,
      get(modelValue.value, props.trackBy),
    ])
    if (selected !== -1) selectedOptionIndex.value = selected
    inputWidth.value = inputRef.value.offsetWidth

    Nova.$emit('disable-focus-trap')

    nextTick(() => {
      popper.value = createPopper(inputRef.value, dropdownRef.value, {
        placement: 'bottom-start',
        onFirstUpdate: state => {
          containerRef.value.scrollTop = containerRef.value.scrollHeight
          updateScrollPosition()
          searchRef.value.focus()
        },
      })
    })
  } else {
    if (popper.value) popper.value.destroy()

    Nova.$emit('enable-focus-trap')
    inputRef.value.focus()
  }
})

// Lifecycle Methods
useEventListener(document, 'keydown', event => {
  if (dropdownShown.value !== true) {
    return
  }

  // 'tab' or 'escape'
  if ([9, 27].includes(event.keyCode)) {
    setTimeout(() => close(), 50)
  }
})

useEventListener(inputRef, 'keydown', event => {
  if (dropdownShown.value !== true) {
    return
  }
  if (event.composed && [13, 229].includes(event.keyCode)) {
    searchText.value = event.target.value
  }
})

function getTrackedByKey(option) {
  return get(option, props.trackBy)
}

function open() {
  if (!props.disabled && !props.readOnly) {
    dropdownShown.value = true
    searchText.value = ''
    emitter('shown')
  }
}

function close() {
  dropdownShown.value = false
  emitter('closed')
}

function clear() {
  if (!props.disabled) {
    selectedOptionIndex.value = null
    emitter('clear', null)
  }
}

function move(offset) {
  let newIndex = selectedOptionIndex.value + offset

  if (newIndex >= 0 && newIndex < props.options.length) {
    selectedOptionIndex.value = newIndex
    updateScrollPosition()
  }
}

function updateScrollPosition() {
  nextTick(() => {
    if (selectedRef.value && selectedRef.value[0]) {
      if (
        selectedRef.value[0].offsetTop >
        containerRef.value.scrollTop +
          containerRef.value.clientHeight -
          selectedRef.value[0].clientHeight
      ) {
        containerRef.value.scrollTop =
          selectedRef.value[0].offsetTop +
          selectedRef.value[0].clientHeight -
          containerRef.value.clientHeight
      }

      if (selectedRef.value[0].offsetTop < containerRef.value.scrollTop) {
        containerRef.value.scrollTop = selectedRef.value[0].offsetTop
      }
    }
  })
}

function chooseSelected(event) {
  if (event.isComposing || event.keyCode === 229) return

  if (props.options[selectedOptionIndex.value] !== undefined) {
    let selected = props.options[selectedOptionIndex.value]

    modelValue.value = getTrackedByKey(selected)
    emitter('selected', selected)
    inputRef.value.focus()

    nextTick(() => close())
  }
}

function choose(option) {
  selectedOptionIndex.value = findIndex(props.options, [
    props.trackBy,
    get(option, props.trackBy),
  ])

  modelValue.value = getTrackedByKey(option)
  emitter('selected', option)
  inputRef.value.blur()

  nextTick(() => close())
}

const shouldShowDropdownArrow = computed(() => {
  return modelValue.value == '' || modelValue.value == null || !props.clearable
})

defineExpose({
  open,
  close,
  clear,
  move,
})
</script>
