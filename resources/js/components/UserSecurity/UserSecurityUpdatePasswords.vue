<template>
  <div class="mt-10 sm:mt-0 mb-6">
    <div class="md:grid md:grid-cols-3 md:gap-6">
      <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
          <Heading :level="3" v-text="__('Update Password')" />
          <p class="my-3 text-sm text-gray-600">
            {{
              __(
                'Ensure your account is using a long, random password to stay secure.'
              )
            }}
          </p>
        </div>
      </div>
      <Card class="md:col-span-2 pt-6">
        <form @submit.prevent="updatePassword">
          <div class="mt-6 px-6 grid grid-cols-6 gap-6">
            <div class="col-span-full sm:col-span-4">
              <Heading
                :level="4"
                v-text="__('Update Password')"
                class="text-lg font-medium"
              />

              <p class="mt-6">
                {{
                  __(
                    'Ensure your account is using a long, random password to stay secure.'
                  )
                }}
              </p>
            </div>

            <div class="col-span-6 sm:col-span-4">
              <label class="block mb-2" for="current_password">{{
                __('Current Password')
              }}</label>
              <input
                v-model="form.current_password"
                id="current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
                class="form-control form-input form-control-bordered w-full"
                :class="{
                  'form-control-bordered-error':
                    form.errors.has('current_password'),
                }"
              />
              <HelpText
                v-if="form.errors.has('current_password')"
                class="mt-2 text-red-500"
              >
                {{ form.errors.first('current_password') }}
              </HelpText>
            </div>

            <div class="col-span-6 sm:col-span-4">
              <label class="block mb-2" for="password">{{
                __('Password')
              }}</label>
              <input
                v-model="form.password"
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                class="form-control form-input form-control-bordered w-full"
                :class="{
                  'form-control-bordered-error': form.errors.has('password'),
                }"
              />
              <HelpText
                v-if="form.errors.has('password')"
                class="mt-2 text-red-500"
              >
                {{ form.errors.first('password') }}
              </HelpText>
            </div>

            <div class="col-span-6 sm:col-span-4">
              <label class="block mb-2" for="password_confirmation">{{
                __('Confirm Password')
              }}</label>
              <input
                v-model="form.password_confirmation"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                class="form-control form-input form-control-bordered w-full"
                :class="{
                  'form-control-bordered-error': form.errors.has(
                    'password_confirmation'
                  ),
                }"
              />
              <HelpText
                v-if="form.errors.has('password_confirmation')"
                class="mt-2 text-red-500"
              >
                {{ form.errors.first('password_confirmation') }}
              </HelpText>
            </div>
          </div>
          <div
            class="bg-gray-100 dark:bg-gray-700 px-6 py-3 mt-6 flex justify-end"
          >
            <Button
              type="submit"
              :loading="form.processing"
              :label="__('Save')"
            />
          </div>
        </form>
      </Card>
    </div>
  </div>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  name: 'UserSecurityUpdatePasswords',

  components: {
    Button,
  },

  data: () => ({
    form: Nova.form({
      current_password: '',
      password: '',
      password_confirmation: '',
    }),
  }),

  methods: {
    updatePassword() {
      this.form
        .put(Nova.url('/user-security/password'))
        .then(response => {
          Nova.$toasted.show(this.__('Your password has been updated.'), {
            duration: null,
            type: 'success',
          })
        })
        .catch(error => {
          if (error.response?.status === 500) {
            Nova.error(this.__('There was a problem submitting the form.'))
          }
        })
    },
  },
}
</script>
