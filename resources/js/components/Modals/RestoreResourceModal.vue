<template>
  <Modal :show="show" size="sm">
    <form
      @submit.prevent="handleConfirm"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
      style="width: 460px"
    >
      <slot name="header">
        <ModalHeader v-text="modalTitle" />
      </slot>
      <slot name="content">
        <ModalContent>
          <p class="leading-normal">
            {{ __('Are you sure you want to restore the selected resources?') }}
          </p>
        </ModalContent>
      </slot>

      <ModalFooter>
        <div class="ml-auto">
          <Button
            variant="link"
            state="mellow"
            @click.prevent="handleClose"
            class="mr-3"
            dusk="cancel-restore-button"
          >
            {{ __('Cancel') }}
          </Button>

          <Button
            type="submit"
            ref="confirmButton"
            dusk="confirm-restore-button"
            :loading="working"
          >
            {{ __('Restore') }}
          </Button>
        </div>
      </ModalFooter>
    </form>
  </Modal>
</template>

<script setup>
import { mapProps } from '@/mixins'
import { Button } from 'laravel-nova-ui'
import { computed, ref, watch } from 'vue'
import { useLocalization } from '@/composables/useLocalization'
import { useResourceInformation } from '@/composables/useResourceInformation'
import isNull from 'lodash/isNull'

const { __ } = useLocalization()
const { resourceInformation } = useResourceInformation()

const emitter = defineEmits(['confirm', 'close'])

const working = ref(false)

const props = defineProps({
  ...mapProps(['resourceName']),
  show: { type: Boolean, default: false },
})

watch(
  () => props.show,
  showing => {
    if (showing === false) {
      working.value = false
    }
  }
)

const modalTitle = computed(() => {
  const resource = resourceInformation(props.resourceName)

  if (isNull(resource)) {
    return __('Restore Resource')
  }

  return __('Restore :resource', {
    resource: resource.singularLabel,
  })
})

function handleClose() {
  emitter('close')
  working.value = false
}

function handleConfirm() {
  emitter('confirm')
  working.value = true
}
</script>
