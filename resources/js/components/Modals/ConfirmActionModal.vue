<template>
  <Modal
    :show="show"
    @close-via-escape="handlePreventModalAbandonmentOnClose"
    role="dialog"
    :size="action.modalSize"
    :modal-style="action.modalStyle"
    :use-focus-trap="usesFocusTrap"
  >
    <form
      ref="theForm"
      autocomplete="off"
      @change="onUpdateFormStatus"
      @submit.prevent.stop="$emit('confirm')"
      :data-form-unique-id="formUniqueId"
      class="bg-white dark:bg-gray-800"
      :class="{
        'rounded-lg shadow-lg overflow-hidden space-y-6':
          action.modalStyle === 'window',
        'flex flex-col justify-between h-full':
          action.modalStyle === 'fullscreen',
      }"
    >
      <div
        class="space-y-6"
        :class="{
          'overflow-hidden overflow-y-auto': action.modalStyle === 'fullscreen',
        }"
      >
        <ModalHeader v-text="action.name" />

        <!-- Confirmation Text -->
        <p
          v-if="action.confirmText"
          class="px-8"
          :class="{ 'text-red-500': action.destructive }"
        >
          {{ action.confirmText }}
        </p>

        <!-- Action Fields -->
        <div v-if="action.fields.length > 0">
          <div
            class="action"
            v-for="field in action.fields"
            :key="field.attribute"
          >
            <component
              :is="'form-' + field.component"
              :errors="errors"
              :resource-name="resourceName"
              :field="field"
              :show-help-text="true"
              :form-unique-id="formUniqueId"
              :mode="
                action.modalStyle === 'fullscreen'
                  ? 'action-fullscreen'
                  : 'action-modal'
              "
              :sync-endpoint="syncEndpoint"
              @field-changed="onUpdateFieldStatus"
            />
          </div>
        </div>
      </div>

      <ModalFooter>
        <div class="flex items-center ml-auto">
          <Button
            variant="link"
            state="mellow"
            @click="$emit('close')"
            dusk="cancel-action-button"
            class="ml-auto mr-3"
          >
            {{ action.cancelButtonText }}
          </Button>

          <Button
            ref="runButton"
            type="submit"
            :loading="working"
            variant="solid"
            :state="action.destructive ? 'danger' : 'default'"
            dusk="confirm-action-button"
          >
            {{ action.confirmButtonText }}
          </Button>
        </div>
      </ModalFooter>
    </form>
  </Modal>
</template>

<script>
import { PreventsModalAbandonment } from '@/mixins'
import isObject from 'lodash/isObject'
import { uid } from 'uid/single'
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['confirm', 'close'],

  mixins: [PreventsModalAbandonment],

  props: {
    action: { type: Object, required: true },
    endpoint: { type: String, required: false },
    errors: { type: Object, required: true },
    resourceName: { type: String, required: true },
    selectedResources: { type: [Array, String], required: true },
    show: { type: Boolean, default: false },
    working: Boolean,
  },

  data: () => ({
    loading: true,
    formUniqueId: uid(),
  }),

  created() {
    document.addEventListener('keydown', this.handleKeydown)
  },

  mounted() {
    this.loading = false
  },

  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeydown)
  },

  methods: {
    /**
     * Prevent accidental abandonment only if form was changed.
     */
    onUpdateFormStatus() {
      this.updateModalStatus()
    },

    onUpdateFieldStatus() {
      this.onUpdateFormStatus()
    },

    handlePreventModalAbandonmentOnClose(event) {
      this.handlePreventModalAbandonment(
        () => {
          this.$emit('close')
        },
        () => {
          event.stopPropagation()
        }
      )
    },
  },

  computed: {
    syncEndpoint() {
      let searchParams = new URLSearchParams({ action: this.action.uriKey })

      if (this.selectedResources === 'all') {
        searchParams.append('resources', 'all')
      } else {
        this.selectedResources.forEach(resource => {
          searchParams.append(
            'resources[]',
            isObject(resource) ? resource.id.value : resource
          )
        })
      }

      return (
        (this.endpoint || `/nova-api/${this.resourceName}/action`) +
        '?' +
        searchParams.toString()
      )
    },

    usesFocusTrap() {
      return this.loading === false && this.action.fields.length > 0
    },
  },
}
</script>
