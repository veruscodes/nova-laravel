<template>
  <div>
    <header
      class="bg-white dark:bg-gray-800 flex items-center h-14 shadow-b dark:border-b dark:border-gray-700"
    >
      <Button
        icon="bars-3"
        class="lg:hidden ml-1"
        variant="action"
        @click.prevent="toggleMainMenu"
        :aria-label="__('Toggle Sidebar')"
        :aria-expanded="mainMenuShown ? 'true' : 'false'"
      />

      <div class="hidden lg:w-60 shrink-0 md:flex items-center">
        <Link
          :href="$url('/')"
          class="text-gray-900 hover:text-gray-500 active:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 dark:active:text-gray-500 h-12 rounded-lg flex items-center ml-2 focus:ring focus:ring-inset focus:outline-none ring-primary-200 dark:ring-gray-600 px-4"
          :aria-label="appName"
        >
          <AppLogo class="h-6" />
        </Link>

        <LicenseWarning />
      </div>

      <div class="flex flex-1 px-4 sm:px-8 lg:px-12">
        <GlobalSearch
          v-if="globalSearchEnabled"
          class="relative"
          dusk="global-search-component"
        />

        <div class="isolate relative flex items-center pl-6 ml-auto">
          <ThemeDropdown />
          <div class="relative z-50">
            <NotificationCenter v-if="notificationCenterEnabled" />
          </div>
          <div class="relative z-[40] hidden md:flex ml-2">
            <UserMenu />
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Sidebar -->
    <teleport to="body">
      <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="mainMenuShown" class="lg:hidden w-60">
          <div class="fixed inset-0 flex z-50">
            <div class="fixed inset-0" aria-hidden="true">
              <div
                @click="toggleMainMenu"
                class="absolute inset-0 bg-gray-600/75 dark:bg-gray-900/75"
              />
            </div>

            <div
              ref="modalContent"
              class="bg-white dark:bg-gray-800 relative flex flex-col max-w-xxs w-full"
            >
              <!-- Close Button -->
              <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button
                  @click.prevent="toggleMainMenu"
                  class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                  :aria-label="__('Close Sidebar')"
                >
                  <!-- Heroicon name: outline/x -->
                  <svg
                    class="h-6 w-6 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>

              <div class="px-2 border-b border-gray-200 dark:border-gray-700">
                <Link
                  :href="$url('/')"
                  class="text-gray-900 hover:text-gray-500 active:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300 dark:active:text-gray-500 h-12 px-2 rounded-lg flex items-center focus:ring focus:ring-inset focus:outline-none"
                  :aria-label="appName"
                >
                  <AppLogo class="h-6" />
                </Link>
              </div>

              <div
                class="flex flex-col gap-2 justify-between h-full py-3 px-3 overflow-x-auto"
              >
                <div class="py-1">
                  <MainMenu data-screen="responsive" />
                </div>
                <div class="mt-auto">
                  <MobileUserMenu />
                </div>
              </div>

              <div class="shrink-0 w-14" aria-hidden="true">
                <!-- Dummy element to force sidebar to shrink to fit close icon -->
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { useStore } from 'vuex'
import LicenseWarning from '@/components/LicenseWarning'
import { Button } from 'laravel-nova-ui'
import { useFocusTrap } from '@vueuse/integrations/useFocusTrap'
import { computed, onBeforeUnmount, useTemplateRef, watch } from 'vue'

const store = useStore()

const modalContentRef = useTemplateRef('modalContent')

const { activate, deactivate } = useFocusTrap(modalContentRef, {
  initialFocus: true,
  allowOutsideClick: false,
  escapeDeactivates: false,
})

const toggleMainMenu = () => store.commit('toggleMainMenu')

const globalSearchEnabled = computed(() => Nova.config('globalSearchEnabled'))

const notificationCenterEnabled = computed(() =>
  Nova.config('notificationCenterEnabled')
)

const mainMenuShown = computed(() => store.getters.mainMenuShown)
const appName = computed(() => Nova.config('appName'))

watch(
  () => mainMenuShown.value,
  newValue => {
    if (newValue === true) {
      document.body.classList.add('overflow-y-hidden')
      Nova.pauseShortcuts()
      return
    }

    document.body.classList.remove('overflow-y-hidden')
    Nova.resumeShortcuts()
    deactivate()
  }
)

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-hidden')
  Nova.resumeShortcuts()
  deactivate()
})
</script>
