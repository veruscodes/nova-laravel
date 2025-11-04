<template>
  <CreateForm
    @resource-created="handleResourceCreated"
    @resource-created-and-adding-another="handleResourceCreatedAndAddingAnother"
    @create-cancelled="cancelCreatingResource"
    :mode="mode"
    :resource-name="resourceName"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :via-relationship="viaRelationship"
    @update-form-status="onUpdateFormStatus"
    @finished-loading="$emit('finished-loading')"
    :should-override-meta="mode === 'form'"
    :form-unique-id="formUniqueId"
  />
</template>

<script>
import {
  mapProps,
  PreventsFormAbandonment,
  PreventsModalAbandonment,
} from '@/mixins'
import { uid } from 'uid/single'

export default {
  emits: ['refresh', 'create-cancelled', 'finished-loading'],

  mixins: [PreventsFormAbandonment, PreventsModalAbandonment],

  provide() {
    return {
      removeFile: this.removeFile,
    }
  },

  props: {
    mode: {
      type: String,
      default: 'form',
      validator: val => ['modal', 'form'].includes(val),
    },

    ...mapProps([
      'resourceName',
      'viaResource',
      'viaResourceId',
      'viaRelationship',
    ]),
  },

  data: () => ({
    formUniqueId: uid(),
  }),

  methods: {
    handleResourceCreated({ redirect, id }) {
      if (this.mode !== 'form') this.allowLeavingModal()

      Nova.$emit('resource-created', {
        resourceName: this.resourceName,
        resourceId: id,
      })

      if (this.mode === 'form') {
        return Nova.visit(redirect)
      }

      return this.$emit('refresh', { redirect, id })
    },

    handleResourceCreatedAndAddingAnother() {
      this.disableNavigateBackUsingHistory()
    },

    cancelCreatingResource() {
      if (this.mode === 'form') {
        this.handleProceedingToPreviousPage()

        this.proceedToPreviousPage(
          this.isRelation
            ? `/resources/${this.viaResource}/${this.viaResourceId}`
            : `/resources/${this.resourceName}`
        )

        return
      }

      this.allowLeavingModal()
      return this.$emit('create-cancelled')
    },

    /**
     * Prevent accidental abandonment only if form was changed.
     */
    onUpdateFormStatus() {
      if (this.mode !== 'form') this.updateModalStatus()
    },

    removeFile(attribute) {
      //
    },
  },

  computed: {
    isRelation() {
      return Boolean(this.viaResourceId && this.viaRelationship)
    },
  },
}
</script>
