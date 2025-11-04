<template>
  <div ref="searchInputContainer" v-bind="$attrs" :dusk="dusk">
    <div class="relative">
      <!-- Search Input -->
      <input
        @click.stop="open"
        @keydown.enter.prevent="chooseSelected"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        class="w-full block form-control form-input form-control-bordered"
        :class="{
          'form-control-bordered-error': error,
        }"
        v-model="searchText"
        :disabled="disabled"
        ref="searchInput"
        tabindex="0"
        type="search"
        :placeholder="__(placeholder)"
        :autocomplete="autocomplete"
        spellcheck="false"
        :aria-expanded="dropdownShown === true ? 'true' : 'false'"
      />
    </div>

    <!-- Search Result Dropdown -->
    <teleport to="body">
      <div
        v-if="dropdownShown"
        ref="searchResultsDropdown"
        :style="{ zIndex: 2000 }"
        :dusk="`${dusk}-dropdown`"
      >
        <div
          v-show="loading || options.length > 0"
          class="rounded-lg px-0 bg-white dark:bg-gray-900 shadow border border-gray-200 dark:border-gray-700 my-1 overflow-hidden"
          :style="{ width: searchInputWidth + 'px', zIndex: 2000 }"
        >
          <!-- Search Results -->
          <div
            ref="searchResultsContainer"
            class="relative overflow-y-scroll text-sm divide-y divide-gray-100 dark:divide-gray-800"
            tabindex="-1"
            style="max-height: 155px"
            :dusk="`${dusk}-results`"
          >
            <div v-if="loading" class="px-3 py-2">
              <Loader width="30" />
            </div>

            <div
              v-else
              v-for="(option, index) in options"
              :dusk="`${dusk}-result-${index}`"
              @click.stop="choose(option)"
              :ref="el => setSelectedRef(index, el)"
              :key="getTrackedByKey(option)"
              class="px-3 py-1.5 cursor-pointer"
              :class="{
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
                :dusk="`${dusk}-result-${index}`"
              />
            </div>
          </div>
        </div>
      </div>

      <Backdrop @click="close" :show="dropdownShown" class="z-[35]" />
    </teleport>
  </div>
</template>

<script setup>
import { createPopper } from '@popperjs/core'
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue'
import debounce from 'lodash/debounce'
import get from 'lodash/get'
import { useEventListener } from '@vueuse/core'

defineOptions({ inheritAttrs: false })

// Events
const emitter = defineEmits(['clear', 'input', 'selected'])

// Props
const props = defineProps({
  autocomplete: { type: String, required: false, default: null },
  dusk: { type: String },
  error: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: 'Search' },
  options: { type: Array, default: [] },
  loading: { type: Boolean, default: false },
  debounce: { type: Number, default: 500 },
  trackBy: { type: String },
})

const debouncer = debounce(callback => callback(), props.debounce)

const modelValue = defineModel({ type: Array, default: [] })

// References
const popper = ref(null)

// Elements
const searchInputRef = useTemplateRef('searchInput')
const searchResultsContainerRef = useTemplateRef('searchResultsContainer')
const searchResultsDropdownRef = useTemplateRef('searchResultsDropdown')
const searchInputContainerRef = useTemplateRef('searchInputContainer')
const selectedOptionRef = useTemplateRef('selectedOption')

// State
const searchText = ref('')
const dropdownShown = ref(false)
const selectedOptionIndex = ref(0)

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

useEventListener(searchInputRef, 'keydown', event => {
  if (dropdownShown.value !== true) {
    return
  }

  if (event.composed && [13, 229].includes(event.keyCode)) {
    searchText.value = event.target.value
  }
})

// Watchers
watch(searchText, newValue => {
  if (newValue) {
    dropdownShown.value = true
  }

  selectedOptionIndex.value = 0

  if (searchResultsContainerRef.value) {
    searchResultsContainerRef.value.scrollTop = 0
  } else {
    nextTick(() => (searchResultsContainerRef.value.scrollTop = 0))
  }

  debouncer(() => emitter('input', newValue))
})

watch(dropdownShown, shown =>
  shown === true ? nextTick(() => createSearchPopper()) : popper.value.destroy()
)

// Computed Properties
const searchInputWidth = computed(() => searchInputRef.value?.offsetWidth)

// Methods
function getTrackedByKey(option) {
  return get(option, props.trackBy)
}

function createSearchPopper() {
  popper.value = createPopper(
    searchInputRef.value,
    searchResultsDropdownRef.value,
    {
      placement: 'bottom-start',
      onFirstUpdate: () => {
        searchInputContainerRef.value.scrollTop =
          searchInputContainerRef.value.scrollHeight
        updateScrollPosition()
      },
    }
  )
}

function open() {
  dropdownShown.value = true
}

function close() {
  dropdownShown.value = false
}

function clear() {
  selectedOptionIndex.value = null
  close()
  emitter('clear')

  modelValue.value = []
}

function move(offset) {
  let newIndex = selectedOptionIndex.value + offset

  if (newIndex >= 0 && newIndex < props.options.length) {
    selectedOptionIndex.value = newIndex

    nextTick(() => updateScrollPosition())
  }
}

function findOption(index) {
  return props.options[index]
}

function choose(option) {
  const found = modelValue.value.filter(t => t.value === option.value)

  emitter('selected', option)
  nextTick(() => close())
  searchText.value = ''

  if (found.length === 0) {
    modelValue.value.push(option)
  }
}

function remove(index) {
  modelValue.value.splice(index, 1)
}

function chooseSelected(event) {
  if (event.isComposing || event.keyCode === 229) return

  const selectedOption = findOption(selectedOptionIndex.value)
  choose(selectedOption)
}

function updateScrollPosition() {
  // If we've highlighted an option...
  if (selectedOptionRef.value) {
    // If we need to scroll the dropdown down to the selected element...
    if (
      selectedOptionRef.value.offsetTop >
      searchResultsContainerRef.value.scrollTop +
        searchResultsContainerRef.value.clientHeight -
        selectedOptionRef.value.clientHeight
    ) {
      searchResultsContainerRef.value.scrollTop =
        selectedOptionRef.value.offsetTop +
        selectedOptionRef.value.clientHeight -
        searchResultsContainerRef.value.clientHeight
    }

    // If we need to scroll the dropdown back up...
    if (
      selectedOptionRef.value.offsetTop <
      searchResultsContainerRef.value.scrollTop
    ) {
      searchResultsContainerRef.value.scrollTop =
        selectedOptionRef.value.offsetTop
    }
  }
}

function setSelectedRef(index, el) {
  if (selectedOptionIndex.value === index) {
    selectedOptionRef.value = el
  }
}

defineExpose({
  open,
  close,
  choose,
  remove,
  clear,
  move,
})
</script>
