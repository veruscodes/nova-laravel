<template>
  <Modal :show="show" role="alertdialog" size="md">
    <div
      class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
      style="width: 460px"
    >
      <ModalHeader v-text="__('Delete File')" />
      <ModalContent>
        <p class="leading-tight">
          {{ __('Are you sure you want to delete this file?') }}
        </p>
      </ModalContent>
      <ModalFooter>
        <div class="ml-auto">
          <Button
            variant="link"
            state="mellow"
            @click.prevent="handleClose"
            class="mr-3"
            dusk="cancel-upload-delete-button"
          >
            {{ __('Cancel') }}
          </Button>

          <Button
            @click.prevent="handleConfirm"
            ref="confirmButton"
            dusk="confirm-upload-delete-button"
            :loading="working"
            state="danger"
            :label="__('Delete')"
          />
        </div>
      </ModalFooter>
    </div>
  </Modal>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['confirm', 'close'],

  props: {
    show: { type: Boolean, default: false },
  },

  data: () => ({ working: false }),

  watch: {
    show(showing) {
      if (showing === false) {
        this.working = false
      }
    },
  },

  methods: {
    handleClose() {
      this.working = false
      this.$emit('close')
    },

    handleConfirm() {
      this.working = true
      this.$emit('confirm')
    },
  },
}
</script>
