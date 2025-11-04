<template>
  <Modal
    :show="show"
    @close-via-escape="handlePreventModalAbandonmentOnClose"
    role="dialog"
    size="2xl"
    modal-style="window"
    :use-focus-trap="show"
  >
    <form
      ref="theForm"
      autocomplete="off"
      @submit.prevent.stop="submit"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden space-y-6"
    >
      <div class="space-y-6">
        <ModalHeader v-text="__(title ?? 'Confirm Password')" />

        <p class="px-8">
          {{
            __(
              content ??
                'For your security, please confirm your password to continue.'
            )
          }}
        </p>

        <div class="px-8 mb-6">
          <input
            v-model="form.password"
            ref="passwordInput"
            class="form-control form-input form-control-bordered w-full"
            :class="{
              'form-control-bordered-error': form.errors.has('password'),
            }"
            :placeholder="__('Password')"
            type="password"
            name="password"
            :disabled="!show"
            required=""
            autocomplete="current-password"
          />

          <HelpText
            class="mt-2 text-red-500"
            v-if="form.errors.has('password')"
          >
            {{ form.errors.first('password') }}
          </HelpText>
        </div>
      </div>

      <ModalFooter>
        <div class="flex items-center ml-auto">
          <Button
            variant="link"
            state="mellow"
            :disabled="loading"
            @click="handleClose"
            dusk="cancel-confirm-password-button"
            class="ml-auto mr-3"
          >
            {{ __('Cancel') }}
          </Button>

          <Button
            ref="runButton"
            type="submit"
            variant="solid"
            state="default"
            :loading="loading"
            :disabled="completed"
            dusk="submit-confirm-password-button"
          >
            {{ __(button ?? 'Confirm') }}
          </Button>
        </div>
      </ModalFooter>
    </form>
  </Modal>
</template>

<script>
import { PreventsModalAbandonment } from '@/mixins'
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  emits: ['confirm', 'close'],

  mixins: [PreventsModalAbandonment],

  props: {
    show: { type: Boolean, default: false },
    title: { type: String, default: null },
    content: {
      type: String,
      default: null,
    },
    button: { type: String, default: null },
  },

  data: () => ({
    form: Nova.form({
      password: '',
    }),
    loading: false,
    completed: false,
  }),

  methods: {
    async submit() {
      try {
        let { redirect } = await this.form.post(
          Nova.url('/user-security/confirm-password')
        )

        this.completed = true

        this.$emit('confirm')
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }

      this.form.password = ''
      this.$refs.passwordInput.focus()
    },

    handlePreventModalAbandonmentOnClose(event) {
      this.handlePreventModalAbandonment(
        () => {
          this.handleClose()
        },
        () => {
          event.stopPropagation()
        }
      )
    },

    handleClose() {
      this.form.password = ''
      this.$emit('close')
    },
  },
}
</script>
