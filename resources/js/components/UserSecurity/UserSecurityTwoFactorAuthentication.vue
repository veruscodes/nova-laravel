<template>
  <div class="mt-10 sm:mt-0 mb-6">
    <div class="md:grid md:grid-cols-3 md:gap-6">
      <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
          <Heading :level="3" v-text="__('Two Factor Authentication')" />
          <p class="my-3 text-sm text-gray-600">
            {{
              __(
                'Add additional security to your account using two factor authentication.'
              )
            }}
          </p>
        </div>
      </div>
      <Card class="md:col-span-2 p-6">
        <div class="grid grid-cols-6 gap-6">
          <div class="col-span-full sm:col-span-4">
            <Heading
              v-if="twoFactorEnabled && !confirming"
              :level="4"
              v-text="__('You have enabled two factor authentication.')"
              class="text-lg font-medium"
            />
            <Heading
              v-else-if="twoFactorEnabled && confirming"
              :level="4"
              v-text="__('Finish enabling two factor authentication.')"
              class="text-lg font-medium"
            />
            <Heading
              v-else
              :level="4"
              v-text="__('You have not enabled two factor authentication.')"
              class="text-lg font-medium"
            />

            <p class="mt-6">
              {{
                __(
                  "When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application."
                )
              }}
            </p>
          </div>

          <div v-if="twoFactorEnabled" class="col-span-6 sm:col-span-4">
            <div v-if="qrCode">
              <div class="mt-4 max-w-xl text-sm">
                <p v-if="confirming || disabling" class="font-semibold">
                  {{
                    __(
                      "To finish enabling two factor authentication, scan the following QR code using your phone's authenticator application or enter the setup key and provide the generated OTP code."
                    )
                  }}
                </p>

                <p v-else>
                  {{
                    __(
                      "Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application or enter the setup key."
                    )
                  }}
                </p>
              </div>

              <div class="mt-4 p-2 inline-block bg-white" v-html="qrCode" />

              <div v-if="setupKey" class="mt-4 max-w-xl text-sm">
                <p class="font-semibold">
                  {{ __('Setup Key') }}: <span v-html="setupKey"></span>
                </p>
              </div>

              <div v-if="confirming" class="mt-4">
                <label class="block mb-2" for="code">{{ __('Code') }}</label>
                <input
                  id="code"
                  v-model="confirmationForm.code"
                  type="text"
                  name="code"
                  class="form-control form-input form-control-bordered w-full"
                  :class="{
                    'form-control-bordered-error':
                      confirmationForm.errors.has('code'),
                  }"
                  inputmode="numeric"
                  autofocus
                  autocomplete="one-time-code"
                  @keyup.enter="confirmTwoFactorAuthentication"
                />
                <HelpText
                  class="mt-2 text-red-500"
                  v-if="confirmationForm.errors.has('code')"
                >
                  {{ confirmationForm.errors.first('code') }}
                </HelpText>
              </div>
            </div>

            <div v-if="recoveryCodes.length > 0 && !confirming && !disabling">
              <div class="mt-4 max-w-xl text-sm">
                <p class="font-semibold">
                  {{
                    __(
                      'Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.'
                    )
                  }}
                </p>
              </div>

              <div
                class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 dark:bg-gray-900 dark:text-gray-100 rounded-lg"
              >
                <div v-for="code in recoveryCodes" :key="code">
                  {{ code }}
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-full sm:col-span-4">
            <template v-if="!twoFactorEnabled">
              <ConfirmsPassword
                :required="requiresConfirmPassword"
                @confirmed="enableTwoFactorAuthentication"
              >
                <Button
                  :loading="enabling"
                  :disabled="enabling"
                  :label="__('Enable')"
                  class="inline-flex items-center me-3"
                />
              </ConfirmsPassword>
            </template>
            <template v-else>
              <Button
                v-if="confirming"
                :loading="confirmationForm.processing || enabling"
                :disabled="enabling"
                :label="__('Confirm')"
                @click="confirmTwoFactorAuthentication"
                class="inline-flex items-center me-3"
              />

              <ConfirmsPassword
                :required="requiresConfirmPassword"
                @confirmed="regenerateRecoveryCodes"
              >
                <Button
                  v-if="recoveryCodes.length > 0 && !confirming"
                  variant="outline"
                  :label="__('Regenerate Recovery Codes')"
                  class="inline-flex items-center me-3"
                />
              </ConfirmsPassword>

              <ConfirmsPassword
                :required="requiresConfirmPassword"
                @confirmed="showRecoveryCodes"
              >
                <Button
                  v-if="recoveryCodes.length === 0 && !confirming"
                  variant="outline"
                  :label="__('Show Recovery Codes')"
                  class="inline-flex items-center me-3"
                />
              </ConfirmsPassword>

              <Button
                v-if="confirming"
                :loading="disabling"
                :disabled="disabling"
                variant="ghost"
                :label="__('Cancel')"
                @click="disableTwoFactorAuthentication"
                class="inline-flex items-center me-3"
              />

              <ConfirmsPassword
                :required="requiresConfirmPassword"
                @confirmed="disableTwoFactorAuthentication"
              >
                <Button
                  v-if="!confirming"
                  :loading="disabling"
                  :disabled="disabling"
                  state="danger"
                  :label="__('Disable')"
                  class="inline-flex items-center me-3"
                />
              </ConfirmsPassword>
            </template>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>

<script>
import { Button } from 'laravel-nova-ui'
import isNil from 'lodash/isNil'

export default {
  name: 'UserSecurityTwoFactorAuthentication',

  components: {
    Button,
  },

  props: {
    options: { type: Object, required: true },
    user: { type: Object, required: true },
  },

  data() {
    return {
      confirmationForm: Nova.form({
        code: '',
      }),
      confirming: false,
      enabling: false,
      disabling: false,
      qrCode: null,
      setupKey: null,
      recoveryCodes: [],
    }
  },

  watch: {
    twoFactorEnabled(newValue) {
      if (!newValue) {
        confirmationForm.reset()
        confirmationForm.errors.clear()
      }
    },
  },

  methods: {
    enableTwoFactorAuthentication() {
      this.enabling = true

      Nova.$router.post(
        Nova.url('/user-security/two-factor-authentication'),
        {},
        {
          preserveScroll: true,
          onSuccess: () =>
            Promise.all([
              this.showQrCode(),
              this.showSetupKey(),
              this.showRecoveryCodes(),
            ]),
          onFinish: () => {
            this.enabling = false
            this.confirming = this.requiresConfirmation
          },
        }
      )
    },

    showQrCode() {
      return Nova.request()
        .get(Nova.url('/user-security/two-factor-qr-code'))
        .then(response => {
          this.qrCode = response.data.svg
        })
    },

    showSetupKey() {
      return Nova.request()
        .get(Nova.url('/user-security/two-factor-secret-key'))
        .then(response => {
          this.setupKey = response.data.secretKey
        })
    },

    showRecoveryCodes() {
      return Nova.request()
        .get(Nova.url('/user-security/two-factor-recovery-codes'))
        .then(response => {
          this.recoveryCodes = response.data
        })
    },

    confirmTwoFactorAuthentication() {
      this.confirmationForm
        .post(Nova.url('/user-security/confirmed-two-factor-authentication'))
        .then(response => {
          this.confirming = false
          this.qrCode = null
          this.setupKey = null
        })
    },

    regenerateRecoveryCodes() {
      Nova.request()
        .post(Nova.url('/user-security/two-factor-recovery-codes'))
        .then(() => this.showRecoveryCodes())
    },

    disableTwoFactorAuthentication() {
      this.disabling = true

      Nova.$router.delete(
        Nova.url('/user-security/two-factor-authentication'),
        {
          preserveScroll: true,
          onSuccess: () => {
            this.disabling = false
            this.confirming = false
          },
        }
      )
    },
  },

  computed: {
    twoFactorEnabled() {
      return !this.enabling && this.user.two_factor_enabled
    },

    requiresConfirmPassword() {
      return this.options?.confirmPassword ?? false
    },

    requiresConfirmation() {
      return this.options?.confirm ?? false
    },
  },
}
</script>
