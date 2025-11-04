<template>
  <Modal
    dusk="new-relation-modal"
    :show="show"
    @close-via-escape="handlePreventModalAbandonmentOnClose"
    :size="size"
    :use-focus-trap="!loading"
  >
    <div
      class="bg-gray-100 dark:bg-gray-700 rounded-lg shadow-lg overflow-hidden p-8"
    >
      <CreateResource
        :resource-name="resourceName"
        @create-cancelled="handleCreateCancelled"
        @finished-loading="$nextTick(() => (loading = false))"
        @refresh="handleRefresh"
        mode="modal"
        resource-id=""
        via-relationship=""
        via-resource-id=""
        via-resource=""
      />
    </div>
  </Modal>
</template>

<script>
import { PreventsModalAbandonment } from '@/mixins'
import CreateResource from '@/views/Create'

export default {
  emits: ['set-resource', 'create-cancelled'],

  mixins: [PreventsModalAbandonment],

  components: {
    CreateResource,
  },

  props: {
    show: { type: Boolean, default: false },
    size: { type: String, default: '2xl' },
    resourceName: {},
    resourceId: {},
    viaResource: {},
    viaResourceId: {},
    viaRelationship: {},
  },

  data: () => ({
    loading: true,
  }),

  methods: {
    handleRefresh(data) {
      this.$emit('set-resource', data)
    },

    handleCreateCancelled() {
      return this.$emit('create-cancelled')
    },

    handlePreventModalAbandonmentOnClose(event) {
      this.handlePreventModalAbandonment(
        () => {
          this.handleCreateCancelled()
        },
        () => {
          event.stopPropagation()
        }
      )
    },
  },
}
</script>
