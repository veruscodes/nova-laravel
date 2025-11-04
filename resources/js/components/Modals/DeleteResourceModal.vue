<template>
  <Modal :show="show" role="alertdialog" size="sm">
    <form
      @submit.prevent="handleConfirm"
      class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
    >
      <slot name="header">
        <ModalHeader v-text="modalTitle" />
      </slot>
      <slot name="content">
        <ModalContent>
          <p class="leading-normal">
            {{
              __(
                'Are you sure you want to ' + mode + ' the selected resources?'
              )
            }}
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
            dusk="cancel-delete-button"
          >
            {{ __('Cancel') }}
          </Button>

          <Button
            type="submit"
            ref="confirmButton"
            dusk="confirm-delete-button"
            :loading="working"
            state="danger"
            :label="__(uppercaseMode)"
          />
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
import startCase from 'lodash/startCase'

const emitter = defineEmits(['confirm', 'close'])

const { __ } = useLocalization()
const { resourceInformation } = useResourceInformation()

const working = ref(false)

const props = defineProps({
  show: { type: Boolean, default: false },

  mode: {
    type: String,
    default: 'delete',
    validator: v => ['force delete', 'delete', 'detach'].includes(v),
  },

  ...mapProps(['resourceName']),
})

watch(
  () => props.show,
  showing => {
    if (showing === false) {
      working.value = false
    }
  }
)

const uppercaseMode = computed(() => startCase(props.mode))

const modalTitle = computed(() => {
  const resource = resourceInformation(props.resourceName)

  if (isNull(resource)) {
    return __(`${uppercaseMode.value} Resource`)
  }

  return __(`${uppercaseMode.value} :resource`, {
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
