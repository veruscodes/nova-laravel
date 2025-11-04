<template>
  <div>
    <Head :title="__('Secure Area')" />

    <form
      @submit.prevent="submit"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6">
        {{ __('Secure Area') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <p class="block mb-2">
          {{
            __(
              'This is a secure area of the application. Please confirm your password before continuing.'
            )
          }}
        </p>
      </div>

      <div class="mb-6">
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
          required=""
          autocomplete="current-password"
          autofocus
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('password')">
          {{ form.errors.first('password') }}
        </HelpText>
      </div>

      <Button
        class="w-full flex justify-center"
        type="submit"
        :loading="form.processing"
        :disabled="completed"
      >
        {{ __('Confirm') }}
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
      password: '',
    }),
    completed: false,
  }),

  methods: {
    async submit() {
      try {
        let { redirect } = await this.form.post(
          Nova.url('/user-security/confirm-password')
        )

        this.completed = true

        let path = { url: Nova.url('/'), remote: true }

        if (redirect !== undefined && redirect !== null) {
          path = { url: redirect, remote: true }
        }

        Nova.visit(path)
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }

      this.form.password = ''
      this.$refs.passwordInput.focus()
    },
  },
}
</script>
