<template>
  <div
    class="divide-y divide-gray-200 dark:divide-gray-600"
    dusk="notifications-content"
  >
    <div
      v-for="notification in notifications"
      :key="notification.id"
      class="dark:border-gray-600"
    >
      <!-- Leave the extra div below, it allows the side border to work correctly -->
      <div
        class="relative bg-white dark:bg-gray-800 transition transition-colors flex flex-col gap-2 pt-4 pb-2"
      >
        <span
          v-if="!notification.read_at"
          class="absolute rounded-full top-[20px] right-[16px] bg-primary-500 w-[5px] h-[5px]"
        />

        <component
          :is="notification.component || `MessageNotification`"
          :notification="notification"
          @delete-notification="deleteNotification(notification)"
          @toggle-notifications="store.commit('nova/toggleNotifications')"
          @toggle-mark-as-read="toggleMarkNotificationAsRead(notification)"
        />

        <div class="ml-12">
          <div class="flex items-start">
            <Button
              @click="toggleMarkNotificationAsRead(notification)"
              dusk="mark-as-read-button"
              variant="link"
              state="mellow"
              size="small"
              :label="
                notification.read_at ? __('Mark Unread') : __('Mark Read')
              "
            />

            <Button
              @click="deleteNotification(notification)"
              dusk="delete-button"
              variant="link"
              state="mellow"
              size="small"
              :label="__('Delete')"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Button } from 'laravel-nova-ui'
import { useStore } from 'vuex'

const store = useStore()

defineProps({
  notifications: { type: Array },
})

function toggleMarkNotificationAsRead(notification) {
  notification.read_at
    ? store.dispatch('nova/markNotificationAsUnread', notification.id)
    : store.dispatch('nova/markNotificationAsRead', notification.id)
}

function deleteNotification(notification) {
  store.dispatch('nova/deleteNotification', notification.id)
}
</script>
