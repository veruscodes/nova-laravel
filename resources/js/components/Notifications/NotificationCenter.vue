<template>
  <div class="relative">
    <Button
      variant="action"
      icon="bell"
      @click.stop="toggleNotifications"
      dusk="notifications-dropdown"
    >
      <template v-if="unreadNotifications">
        <!-- Notification Indicator w/ Count -->
        <span
          v-if="shouldShowUnreadCount"
          v-html="unreadNotifications > 99 ? '99+' : unreadNotifications"
          class="font-black tracking-normal absolute border-[3px] border-white dark:border-gray-800 top-[-5px] left-[15px] inline-flex items-center justify-center bg-primary-500 rounded-full text-white text-xxs p-[0px] px-1 min-w-[26px]"
        />

        <!-- Notification Indicator -->
        <span
          v-else
          class="absolute border-[3px] border-white dark:border-gray-800 top-0 right-[3px] inline-block bg-primary-500 rounded-full w-4 h-4"
        />
      </template>
    </Button>
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
      <div v-if="notificationsShown" class="fixed flex inset-0 z-20">
        <div
          @click="toggleNotifications"
          class="absolute inset-0 bg-gray-600/75 dark:bg-gray-900/75"
          dusk="notifications-backdrop"
        />

        <div
          class="relative divide-y divide-gray-200 dark:divide-gray-700 shadow bg-gray-100 dark:bg-gray-800 w-[20rem] ml-auto border-b border-gray-200 dark:border-gray-700 overflow-x-hidden overflow-y-scroll"
        >
          <!-- Notification Header -->
          <nav
            v-if="notifications.length > 0"
            class="bg-white dark:bg-gray-800 flex items-center h-14 px-4"
          >
            <Heading :level="3" class="ml-1">{{ __('Notifications') }}</Heading>

            <div class="ml-auto">
              <Dropdown>
                <template #default>
                  <Button
                    :dusk="`notification-center-action-dropdown`"
                    variant="ghost"
                    icon="ellipsis-horizontal"
                  />
                </template>

                <template #menu>
                  <DropdownMenu width="200">
                    <div class="py-1 px-1">
                      <DropdownMenuItem
                        as="button"
                        @click="markAllNotificationsAsRead"
                      >
                        {{ __('Mark all as Read') }}
                      </DropdownMenuItem>

                      <DropdownMenuItem
                        as="button"
                        @click="handleDeleteAllNotifications"
                      >
                        {{ __('Delete all notifications') }}
                      </DropdownMenuItem>
                    </div>
                  </DropdownMenu>
                </template>
              </Dropdown>
            </div>
          </nav>

          <!-- Notification List -->
          <NotificationList
            v-if="notifications.length > 0"
            :notifications="notifications"
          />

          <!-- No Notifications -->
          <div v-else class="py-12">
            <p class="text-center">
              <svg
                class="inline-block text-gray-300 dark:text-gray-500"
                xmlns="http://www.w3.org/2000/svg"
                width="65"
                height="51"
                viewBox="0 0 65 51"
              >
                <path
                  class="fill-current"
                  d="M56 40h2c.552285 0 1 .447715 1 1s-.447715 1-1 1h-2v2c0 .552285-.447715 1-1 1s-1-.447715-1-1v-2h-2c-.552285 0-1-.447715-1-1s.447715-1 1-1h2v-2c0-.552285.447715-1 1-1s1 .447715 1 1v2zm-5.364125-8H38v8h7.049375c.350333-3.528515 2.534789-6.517471 5.5865-8zm-5.5865 10H6c-3.313708 0-6-2.686292-6-6V6c0-3.313708 2.686292-6 6-6h44c3.313708 0 6 2.686292 6 6v25.049375C61.053323 31.5511 65 35.814652 65 41c0 5.522847-4.477153 10-10 10-5.185348 0-9.4489-3.946677-9.950625-9zM20 30h16v-8H20v8zm0 2v8h16v-8H20zm34-2v-8H38v8h16zM2 30h16v-8H2v8zm0 2v4c0 2.209139 1.790861 4 4 4h12v-8H2zm18-12h16v-8H20v8zm34 0v-8H38v8h16zM2 20h16v-8H2v8zm52-10V6c0-2.209139-1.790861-4-4-4H6C3.790861 2 2 3.790861 2 6v4h52zm1 39c4.418278 0 8-3.581722 8-8s-3.581722-8-8-8-8 3.581722-8 8 3.581722 8 8 8z"
                />
              </svg>
            </p>

            <p class="mt-3 text-center">
              {{ __('There are no new notifications.') }}
            </p>

            <p class="mt-6 px-4 text-center">
              <Button
                variant="solid"
                @click="toggleNotifications"
                :label="__('Close')"
              />
            </p>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script>
import { createNamespacedHelpers } from 'vuex'
import { Button } from 'laravel-nova-ui'

const { mapMutations, mapActions, mapGetters } = createNamespacedHelpers('nova')

export default {
  components: {
    Button,
  },

  created() {
    this.fetchNotifications()
  },

  watch: {
    notificationsShown(newValue) {
      if (newValue === true) {
        document.body.classList.add('overflow-y-hidden')
        return
      }

      document.body.classList.remove('overflow-y-hidden')
    },
  },

  mounted() {
    Nova.$on('refresh-notifications', () => this.fetchNotifications())
  },

  beforeUnmount() {
    document.body.classList.remove('overflow-y-hidden')
  },

  methods: {
    ...mapMutations(['toggleMainMenu', 'toggleNotifications']),
    ...mapActions([
      'fetchNotifications',
      'deleteNotification',
      'deleteAllNotifications',
      'markNotificationAsRead',
      'markAllNotificationsAsRead',
    ]),

    handleDeleteAllNotifications() {
      if (
        confirm(
          this.__('Are you sure you want to delete all the notifications?')
        )
      ) {
        this.deleteAllNotifications()
      }
    },
  },

  computed: {
    ...mapGetters([
      'mainMenuShown',
      'notificationsShown',
      'notifications',
      'unreadNotifications',
    ]),

    shouldShowUnreadCount() {
      return Nova.config('showUnreadCountInNotificationCenter')
    },
  },
}
</script>
