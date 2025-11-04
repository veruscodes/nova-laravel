<template>
  <div>
    <Head :title="__('Two-factor Confirmation')" />

    <form
      @submit.prevent="attempt"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6">
        {{ __('Two-factor Confirmation') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <p class="block mb-2">
          {{
            __(
              recovery
                ? 'Please confirm access to your account by entering one of your emergency recovery codes.'
                : 'Please confirm access to your account by entering the authentication code provided by your authenticator application.'
            )
          }}
        </p>
      </div>

      <div v-if="!recovery" class="mb-6">
        <label class="block mb-2" for="code">{{ __('Code') }}</label>
        <input
          ref="codeInput"
          v-model="form.code"
          id="code"
          type="text"
          name="code"
          inputmode="numeric"
          autocomplete="one-time-code"
          autofocus
          class="form-control form-input form-control-bordered w-full"
          :class="{
            'form-control-bordered-error': form.errors.has('code'),
          }"
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('code')">
          {{ form.errors.first('code') }}
        </HelpText>
      </div>

      <div v-else class="mb-6">
        <label class="block mb-2" for="recovery_code">{{
          __('Recovery Code')
        }}</label>
        <input
          ref="recoveryCodeInput"
          v-model="form.recovery_code"
          id="recovery_code"
          type="text"
          name="recovery_code"
          autocomplete="one-time-code"
          autofocus
          class="form-control form-input form-control-bordered w-full"
          :class="{
            'form-control-bordered-error': form.errors.has('code'),
          }"
        />

        <HelpText
          v-if="form.errors.has('recovery_code')"
          class="mt-2 text-red-500"
        >
          {{ form.errors.first('recovery_code') }}
        </HelpText>
      </div>

      <div class="flex mb-6">
        <div class="ml-auto">
          <Button
            type="button"
            variant="ghost"
            @click.prevent="toggleRecovery"
            class="text-gray-500 font-bold no-underline"
          >
            {{
              __(
                !recovery ? 'Use a recovery code' : 'Use an authentication code'
              )
            }}
          </Button>
        </div>
      </div>

      <Button
        :loading="form.processing"
        :disabled="completed"
        type="submit"
        class="w-full flex justify-center"
      >
        {{ __('Log In') }}
      </Button>
    </form>
  </div>
</template>

<script>
import Auth from '@/layouts/Auth'
import { Button } from 'laravel-nova-ui'

export default {
  layout: Auth,

  components: {
    Button,
  },

  data: () => ({
    form: Nova.form({
      code: '',
      recovery_code: '',
    }),
    recovery: false,
    completed: false,
  }),

  watch: {
    recovery(value) {
      this.$nextTick(() => {
        if (value) {
          this.$refs.recoveryCodeInput.focus()
          this.form.code = ''
        } else {
          this.$refs.codeInput.focus()
          this.form.recovery_code = ''
        }
      })
    },
  },

  methods: {
    async attempt() {
      try {
        const { redirect } = await this.form.post(
          Nova.url('/user-security/two-factor-challenge')
        )

        this.completed = true

        let path = { url: Nova.url('/'), remote: true }

        if (redirect != null) {
          path = { url: redirect, remote: true }
        }

        Nova.visit(path)
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }
    },

    async toggleRecovery() {
      this.recovery ^= true
    },
  },
}
</script>
