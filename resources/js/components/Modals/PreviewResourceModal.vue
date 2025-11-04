<template>
  <Modal
    :show="show && canBePreviewed"
    @close-via-escape="$emit('close')"
    role="alertdialog"
    size="2xl"
    :use-focus-trap="false"
  >
    <LoadingView
      :loading="loading"
      class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
    >
      <slot>
        <ModalHeader class="flex items-center">
          <span>
            {{ modalTitle }}
            <span
              v-if="resource && resource.softDeleted"
              class="ml-auto bg-red-50 text-red-500 py-0.5 px-2 rounded-full text-xs"
            >
              {{ __('Soft Deleted') }}
            </span>
          </span>

          <Link
            dusk="detail-preview-button"
            :href="$url(`/resources/${resourceName}/${resourceId}`)"
            class="ml-auto"
            :alt="viewResourceDetailTitle"
          >
            <Icon name="arrow-right" />
          </Link>
        </ModalHeader>
        <ModalContent
          class="px-8 divide-y divide-gray-100 dark:divide-gray-800"
        >
          <template v-if="resource">
            <component
              :key="index"
              v-for="(field, index) in resource.fields"
              :index="index"
              :is="componentName(field)"
              :resource-name="resourceName"
              :resource-id="resourceId"
              :resource="resource"
              :field="field"
            />

            <div v-if="resource.fields.length == 0">
              {{ __('There are no fields to display.') }}
            </div>
          </template>
        </ModalContent>
      </slot>

      <ModalFooter>
        <div class="ml-auto">
          <Button
            v-if="resource"
            dusk="confirm-preview-button"
            @click.prevent="$emit('close')"
            :label="__('Close')"
          />
        </div>
      </ModalFooter>
    </LoadingView>
  </Modal>
</template>

<script>
import { Button, Icon } from 'laravel-nova-ui'
import { mapProps } from '@/mixins'
import { minimum } from '@/util'

export default {
  components: {
    Button,
    Icon,
  },

  emits: ['close'],

  props: {
    show: { type: Boolean, default: false },

    ...mapProps(['resourceName', 'resourceId']),
  },

  data: () => ({
    loading: true,
    title: null,
    resource: null,
    canBePreviewed: false,
  }),

  async created() {
    await this.getResource()
  },

  mounted() {
    Nova.$emit('close-dropdowns')
  },

  methods: {
    getResource() {
      this.resource = null

      return minimum(
        Nova.request().get(
          `/nova-api/${this.resourceName}/${this.resourceId}/preview`
        )
      )
        .then(({ data: { title, resource } }) => {
          this.title = title
          this.resource = resource
          this.loading = false
          this.canBePreviewed = true
        })
        .catch(error => {
          if (
            error.response.status >= 500 ||
            [403, 404].includes(error.response.status)
          ) {
            Nova.debug(error.response.data.message)
            return
          }

          if (error.response.status === 401) return Nova.redirectToLogin()

          Nova.error(this.__('This resource no longer exists'))

          Nova.visit(`/resources/${this.resourceName}`)
        })
    },

    componentName(field) {
      if (Nova.hasComponent(`preview-${field.component}`)) {
        return `preview-${field.component}`
      }

      return `detail-${field.component}`
    },
  },

  computed: {
    modalTitle() {
      return `${this.__('Previewing')} ${this.title}`
    },

    viewResourceDetailTitle() {
      return this.__('View :resource', { resource: this.title ?? '' })
    },
  },
}
</script>
