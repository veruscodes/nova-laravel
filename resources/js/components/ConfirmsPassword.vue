<template>
  <span>
    <component
      :is="modal"
      :show="confirming"
      :title="title"
      :content="content"
      :button="button"
      @confirm="passwordConfirmed"
      @close="cancelConfirming"
    />
    <span
      v-if="
        (!confirmed && !$slots.unconfirmed) || (confirmed && !$slots.confirmed)
      "
      @click.stop="startConfirming"
    >
      <slot />
    </span>
    <span v-if="!confirmed" @click.stop="startConfirming">
      <slot name="unconfirmed" />
    </span>
    <slot v-else name="confirmed" />
  </span>
</template>

<script setup>
import { ref } from 'vue'
import { useConfirmsPassword } from '@/composables/useConfirmsPassword'

const emitter = defineEmits(['confirmed'])

const props = defineProps({
  modal: { default: 'ConfirmsPasswordModal' },
  required: { type: Boolean, default: true },
  mode: {
    type: String,
    default: 'timeout',
    validator(value, props) {
      return ['always', 'timeout'].includes(value)
    },
  },
  title: { type: [String, null], default: null },
  content: { type: [String, null], default: null },
  button: { type: [String, null], default: null },
})

const {
  confirming,
  confirmed,
  confirmingPassword,
  passwordConfirmed,
  cancelConfirming,
} = useConfirmsPassword(emitter)

const startConfirming = e => {
  confirmingPassword({
    mode: props.mode,
    required: props.required,
  })
}
</script>
