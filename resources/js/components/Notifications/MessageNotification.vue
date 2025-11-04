<template>
  <div
    class="relative flex items-start px-4 gap-4"
    :dusk="`notification-${notification.id}`"
  >
    <div class="shrink-0">
      <Icon :name="icon" class="inline-block" :class="notification.iconClass" />
    </div>

    <div class="flex-auto space-y-4">
      <div>
        <div class="flex items-center">
          <div class="flex-auto">
            <p
              class="mr-1 text-gray-600 dark:text-gray-400 leading-normal break-words"
            >
              {{ notification.message }}
            </p>
          </div>
        </div>

        <p class="mt-1 text-xs" :title="notification.created_at">
          {{ notification.created_at_friendly }}
        </p>
      </div>

      <Button
        v-if="hasUrl"
        @click="handleClick"
        :label="notification.actionText"
        size="small"
      />
    </div>
  </div>
</template>

<script setup>
import { Button, Icon } from 'laravel-nova-ui'
import { computed } from 'vue'

defineOptions({
  name: 'MessageNotification',
})

const emitter = defineEmits(['toggle-mark-as-read', 'toggle-notifications'])

const props = defineProps({
  notification: { type: Object, required: true },
})

const icon = computed(() => props.notification.icon)
const hasUrl = computed(() => props.notification.actionUrl)

function visit() {
  if (hasUrl.value) {
    return Nova.visit(props.notification.actionUrl, {
      openInNewTab: props.notification.openInNewTab || false,
    })
  }
}

function handleClick() {
  emitter('toggle-mark-as-read')
  emitter('toggle-notifications')
  visit()
}
</script>
