<template>
  <div>
    <Head :title="__('Reset Password')" />

    <form
      @submit.prevent="attempt"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6">
        {{ __('Reset Password') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <label class="block mb-2" for="email">{{ __('Email Address') }}</label>
        <input
          v-model="form.email"
          class="w-full form-control form-input form-control-bordered"
          :class="{ 'form-control-bordered-error': form.errors.has('email') }"
          id="email"
          type="email"
          name="email"
          required=""
          autofocus=""
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('email')">
          {{ form.errors.first('email') }}
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
          required=""
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('password')">
          {{ form.errors.first('password') }}
        </HelpText>
      </div>

      <div class="mb-6">
        <label class="block mb-2" for="password_confirmation">{{
          __('Confirm Password')
        }}</label>
        <input
          v-model="form.password_confirmation"
          class="w-full form-control form-input form-control-bordered"
          :class="{
            'form-control-bordered-error': form.errors.has(
              'password_confirmation'
            ),
          }"
          id="password_confirmation"
          type="password"
          name="password_confirmation"
          required=""
        />

        <HelpText
          class="mt-2 text-red-500"
          v-if="form.errors.has('password_confirmation')"
        >
          {{ form.errors.first('password_confirmation') }}
        </HelpText>
      </div>

      <Button
        class="w-full flex justify-center"
        type="submit"
        :loading="form.processing"
      >
        {{ __('Reset Password') }}
      </Button>
    </form>
  </div>
</template>

<script setup>
import Auth from '@/layouts/Auth'
import { Button } from 'laravel-nova-ui'
import { reactive } from 'vue'
import Cookies from 'js-cookie'
import { useLocalization } from '@/composables/useLocalization'

defineOptions({
  layout: Auth,
})

const props = defineProps({
  email: { type: String, required: false },
  token: { type: String, required: true },
})

const form = reactive(
  Nova.form({
    email: props.email,
    password: '',
    password_confirmation: '',
    token: props.token,
  })
)

const { __ } = useLocalization()

async function attempt() {
  const { message } = await form.post(Nova.url('/password/reset'))
  const redirect = { url: Nova.url('/'), remote: true }

  Cookies.set('token', Math.random().toString(36), { expires: 365 })

  Nova.$toasted.show(message, {
    action: {
      onClick: () => Nova.visit(redirect),
      text: __('Reload'),
    },
    duration: null,
    type: 'success',
  })

  setTimeout(() => Nova.visit(redirect), 5000)
}
</script>
