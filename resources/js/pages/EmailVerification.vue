<template>
  <div>
    <Head :title="__('Email Verification')" />

    <form
      @submit.prevent="submit"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6">
        {{ __('Email Verification') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <p class="block mb-2">
          {{
            __(
              "Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another."
            )
          }}
        </p>
      </div>

      <Button
        type="submit"
        :loading="form.processing"
        :disabled="completed"
        class="w-full flex justify-center"
      >
        {{ __('Resend Verification Email') }}
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

  props: {
    status: { type: String },
  },

  data() {
    return {
      form: Nova.form({}),
      verificationStatus: this.status,
    }
  },

  watch: {
    status(value) {
      this.verificationStatus = value
    },

    verificationStatus(status) {
      if (status === 'verification-link-sent') {
        Nova.$toasted.show(
          this.__(
            'A new verification link has been sent to the email address you provided in your profile settings.'
          ),
          { duration: null, type: 'success' }
        )
      }
    },
  },

  methods: {
    async submit() {
      let { status } = await this.form.post(
        Nova.url('/email/verification-notification')
      )

      this.verificationStatus = status
    },
  },

  computed: {
    completed() {
      return this.verificationStatus === 'verification-link-sent'
    },
  },
}
</script>
