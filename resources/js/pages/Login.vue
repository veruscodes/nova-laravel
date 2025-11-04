<template>
  <div>
    <Head :title="__('Log In')" />

    <form
      @submit.prevent="attempt"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 max-w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6">
        {{ __('Welcome Back!') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <label class="block mb-2" for="username">{{ __(usernameLabel) }}</label>
        <input
          v-model="form[username]"
          class="w-full form-control form-input form-control-bordered"
          :class="{ 'form-control-bordered-error': form.errors.has(username) }"
          id="username"
          :type="usernameInputType"
          :name="username"
          autofocus=""
          autocomplete="username"
          required
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has(username)">
          {{ form.errors.first(username) }}
        </HelpText>
      </div>

      <div class="mb-6">
        <label class="block mb-2" for="password">{{ __('Password') }}</label>
        <input
          v-model="form.password"
          class="w-full form-control form-input form-control-bordered"
          :class="{
            'form-control-bordered-error': form.errors.has('password'),
          }"
          id="password"
          type="password"
          name="password"
          autocomplete="current-password"
          required
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('password')">
          {{ form.errors.first('password') }}
        </HelpText>
      </div>

      <div class="flex mb-6">
        <Checkbox
          @change="() => (form.remember = !form.remember)"
          :model-value="form.remember"
          dusk="remember-button"
          :label="__('Remember me')"
        />

        <div
          v-if="supportsPasswordReset || forgotPasswordPath !== false"
          class="ml-auto"
        >
          <Link
            v-if="forgotPasswordPath === false"
            :href="$url('/password/reset')"
            class="text-gray-500 font-bold no-underline"
            v-text="__('Forgot your password?')"
          />
          <a
            v-else
            :href="forgotPasswordPath"
            class="text-gray-500 font-bold no-underline"
            v-text="__('Forgot your password?')"
          />
        </div>
      </div>

      <Button
        class="w-full flex justify-center"
        type="submit"
        :loading="form.processing"
      >
        <span>
          {{ __('Log In') }}
        </span>
      </Button>
    </form>
  </div>
</template>

<script>
import Auth from '@/layouts/Auth'
import { Button, Checkbox } from 'laravel-nova-ui'

export default {
  name: 'LoginPage',

  layout: Auth,

  components: {
    Checkbox,
    Button,
  },

  props: {
    username: { type: String, default: 'email' },
    email: { type: String, default: 'email' },
  },

  data() {
    return {
      form: Nova.form({
        [this.username]: '',
        password: '',
        remember: false,
      }),
    }
  },

  methods: {
    async attempt() {
      try {
        const { redirect, two_factor } = await this.form.post(
          Nova.url('/login')
        )

        let path = { url: Nova.url('/'), remote: true }

        if (two_factor === true) {
          path = {
            url: Nova.url('/user-security/two-factor-challenge'),
            remote: false,
          }
        } else if (redirect != null) {
          path = { url: redirect, remote: true }
        }

        Nova.visit(path)
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }
    },
  },

  computed: {
    usernameLabel() {
      return this.username === this.email ? 'Email Address' : 'Username'
    },

    usernameInputType() {
      return this.username === this.email ? 'email' : 'text'
    },

    supportsPasswordReset() {
      return Nova.config('withPasswordReset')
    },

    forgotPasswordPath() {
      return Nova.config('forgotPasswordPath')
    },
  },
}
</script>
