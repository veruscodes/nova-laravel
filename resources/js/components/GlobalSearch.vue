<template>
  <div class="flex items-center w-full max-w-xs h-12">
    <div class="flex-1 relative">
      <!-- Search -->
      <div class="relative z-10" ref="searchInput">
        <Icon
          name="magnifying-glass"
          type="mini"
          class="absolute ml-2 text-gray-400"
          :style="{ top: '4px' }"
        />

        <input
          dusk="global-search"
          ref="input"
          @keydown.enter.stop="goToCurrentlySelectedResource"
          @keydown.esc.stop="closeSearch"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          v-model="searchTerm"
          @focus="focusSearch"
          type="search"
          :placeholder="__('Press / to search')"
          class="appearance-none rounded-full h-8 pl-10 w-full bg-gray-100 dark:bg-gray-900 dark:focus:bg-gray-800 focus:bg-white focus:outline-none focus:ring focus:ring-primary-200 dark:focus:ring-gray-600"
          role="search"
          :aria-label="__('Search')"
          :aria-expanded="resultsVisible === true ? 'true' : 'false'"
          spellcheck="false"
        />
      </div>

      <teleport to="body">
        <transition
          enter-active-class="transition duration-100 ease-out"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-show="resultsVisible"
            ref="results"
            class="w-full max-w-lg z-10"
          >
            <!-- Loader -->
            <div
              v-if="loading"
              class="bg-white dark:bg-gray-800 py-6 rounded-lg shadow-lg w-full mt-2 max-h-[calc(100vh-5em)] overflow-x-hidden overflow-y-auto"
            >
              <Loader class="text-gray-300" width="40" />
            </div>

            <!-- Results -->
            <div
              v-if="results.length > 0"
              dusk="global-search-results"
              class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full mt-2 max-h-[calc(100vh-5em)] overflow-x-hidden overflow-y-auto"
              ref="container"
            >
              <div v-for="group in formattedResults" :key="group.resourceTitle">
                <h3
                  class="text-xs font-bold uppercase tracking-wide bg-gray-300 dark:bg-gray-900 py-2 px-3"
                >
                  {{ group.resourceTitle }}
                </h3>

                <ul>
                  <li
                    v-for="item in group.items"
                    :key="item.resourceName + ' ' + item.index"
                    :ref="item.index === selected ? 'selected' : null"
                  >
                    <button
                      :dusk="item.resourceName + ' ' + item.index"
                      @click.exact="goToSelectedResource(item, false)"
                      @click.ctrl="goToSelectedResource(item, true)"
                      @click.meta="goToSelectedResource(item, true)"
                      class="w-full flex items-center hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 py-2 px-3 no-underline font-normal"
                      :class="{
                        'bg-white dark:bg-gray-800': selected !== item.index,
                        'bg-gray-100 dark:bg-gray-700': selected === item.index,
                      }"
                    >
                      <img
                        v-if="item.avatar"
                        :src="item.avatar"
                        class="flex-none h-8 w-8 mr-3"
                        :class="{
                          'rounded-full': item.rounded,
                          rounded: !item.rounded,
                        }"
                      />

                      <div class="flex-auto text-left">
                        <p>{{ item.title }}</p>
                        <p v-if="item.subTitle" class="text-xs mt-1">
                          {{ item.subTitle }}
                        </p>
                      </div>
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            <!-- No Results Found -->
            <div
              v-if="!loading && results.length === 0"
              dusk="global-search-empty-results"
              class="bg-white dark:bg-gray-800 overflow-hidden rounded-lg shadow-lg w-full mt-2 max-h-search overflow-y-auto"
            >
              <h3
                class="text-xs font-bold uppercase tracking-wide bg-40 py-4 px-3"
              >
                {{ __('No Results Found.') }}
              </h3>
            </div>
          </div>
        </transition>

        <transition
          enter-active-class="transition duration-100 ease-out"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <Backdrop
            @click="closeSearch"
            :show="showOverlay"
            class="bg-gray-500/75 dark:bg-gray-900/75 z-0"
          />
        </transition>
      </teleport>
    </div>
  </div>
</template>

<script>
import { createPopper } from '@popperjs/core'
import { CancelToken, isCancel } from 'axios'
import { Icon } from 'laravel-nova-ui'
import debounce from 'lodash/debounce'
import uniqBy from 'lodash/uniqBy'

function fetchSearchResults(search, cancelCallback) {
  return Nova.request().get('/nova-api/search', {
    params: { search },
    cancelToken: new CancelToken(canceller => cancelCallback(canceller)),
  })
}

export default {
  components: {
    Icon,
  },

  data: () => ({
    searchFunction: null,
    canceller: null,
    showOverlay: false,
    loading: false,
    resultsVisible: false,
    searchTerm: '',
    results: [],
    selected: 0,
  }),

  watch: {
    searchTerm(newValue) {
      if (this.canceller !== null) this.canceller()

      if (newValue !== '') {
        this.search()
        return
      }

      this.resultsVisible = false
      this.selected = -1
      this.results = []
      // this.showOverlay = false
    },

    resultsVisible(newValue) {
      if (newValue === true) {
        document.body.classList.add('overflow-y-hidden')
        return
      }

      document.body.classList.remove('overflow-y-hidden')
    },
  },

  created() {
    this.searchFunction = debounce(async () => {
      this.showOverlay = true

      this.$nextTick(() => {
        this.popper = createPopper(this.$refs.searchInput, this.$refs.results, {
          placement: 'bottom-start',
          boundary: 'viewPort',
          modifiers: [{ name: 'offset', options: { offset: [0, 8] } }],
        })
      })

      if (this.searchTerm === '') {
        this.canceller()
        this.resultsVisible = false
        this.results = []
        return
      }

      this.resultsVisible = true
      this.loading = true
      this.results = []
      this.selected = 0

      try {
        const { data: results } = await fetchSearchResults(
          this.searchTerm,
          canceller => (this.canceller = canceller)
        )

        this.results = results
        this.loading = false
      } catch (e) {
        if (isCancel(e)) {
          return
        }

        this.loading = false

        throw e
      }
    }, Nova.config('debounce'))
  },

  mounted() {
    Nova.addShortcut('/', () => {
      this.focusSearch()

      return false
    })
  },

  beforeUnmount() {
    if (this.canceller !== null) this.canceller()

    this.resultsVisible = false
    Nova.disableShortcut('/')
  },

  methods: {
    async focusSearch() {
      if (this.results.length > 0) {
        this.showOverlay = true
        this.resultsVisible = true
        await this.popper.update()
      }
      this.$refs.input.focus()
    },

    closeSearch() {
      this.$refs.input.blur()
      this.resultsVisible = false
      this.showOverlay = false
    },

    search() {
      this.searchFunction()
    },

    move(offset) {
      if (this.results.length) {
        let newIndex = this.selected + offset

        if (newIndex < 0) {
          this.selected = this.results.length - 1
          this.updateScrollPosition()
        } else if (newIndex > this.results.length - 1) {
          this.selected = 0
          this.updateScrollPosition()
        } else if (newIndex >= 0 && newIndex < this.results.length) {
          this.selected = newIndex
          this.updateScrollPosition()
        }
      }
    },

    updateScrollPosition() {
      const selection = this.$refs.selected
      const container = this.$refs.container

      this.$nextTick(() => {
        if (selection) {
          if (
            selection[0].offsetTop >
            container.scrollTop +
              container.clientHeight -
              selection[0].clientHeight
          ) {
            container.scrollTop =
              selection[0].offsetTop +
              selection[0].clientHeight -
              container.clientHeight
          }
          if (selection[0].offsetTop < container.scrollTop) {
            container.scrollTop = selection[0].offsetTop
          }
        }
      })
    },

    goToCurrentlySelectedResource(event) {
      if (event.isComposing || event.keyCode === 229) return

      if (this.searchTerm !== '') {
        const resource = this.indexedResults.find(
          res => res.index === this.selected
        )

        this.goToSelectedResource(resource, false)
      }
    },

    goToSelectedResource(resource, commandPressed = false) {
      if (this.canceller !== null) this.canceller()

      this.closeSearch()

      if (resource == null) {
        return
      }

      let url = Nova.url(
        `/resources/${resource.resourceName}/${resource.resourceId}`
      )

      if (resource.linksTo === 'edit') {
        url += '/edit'
      }

      commandPressed
        ? window.open(url, '_blank')
        : Nova.visit({ url, remote: false })
    },
  },

  computed: {
    indexedResults() {
      return this.results.map((item, index) => ({ index, ...item }))
    },

    formattedGroups() {
      return uniqBy(
        this.indexedResults.map(item => ({
          resourceName: item.resourceName,
          resourceTitle: item.resourceTitle,
        })),
        'resourceName'
      )
    },

    formattedResults() {
      return this.formattedGroups.map(group => ({
        resourceName: group.resourceName,
        resourceTitle: group.resourceTitle,
        items: this.indexedResults.filter(
          item => item.resourceName === group.resourceName
        ),
      }))
    },
  },
}
</script>
