<template>
  <Dropdown
    v-if="hasUserMenu"
    @menu-closed="handleUserMenuClosed"
    :placement="dropdownPlacement"
  >
    <Button
      class="block shrink-0"
      variant="ghost"
      padding="tight"
      trailing-icon="chevron-down"
    >
      <span class="inline-flex items-center shrink-0 gap-2">
        <span class="hidden lg:inline-block">
          <Icon
            v-if="currentUser.impersonating"
            name="finger-print"
            type="solid"
            class="!w-7 !h-7"
          />
          <img
            v-else-if="currentUser.avatar"
            :alt="__(':name\'s Avatar', { name: userName })"
            :src="currentUser.avatar"
            class="rounded-full w-7 h-7"
          />
        </span>

        <span class="whitespace-nowrap">
          {{ userName }}
        </span>
      </span>
    </Button>

    <template #menu>
      <DropdownMenu width="200" class="px-1">
        <nav class="py-1">
          <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-if="formattedItems.length > 0">
              <component
                :is="item.component"
                v-for="item in formattedItems"
                :key="item.path"
                v-bind="item.props"
                v-on="item.on"
              >
                <span v-if="item.badge" class="mr-1">
                  <Badge :extra-classes="item.badge.typeClass">
                    {{ item.badge.value }}
                  </Badge>
                </span>

                {{ item.name }}
              </component>
            </div>

            <DropdownMenuItem
              as="button"
              v-if="currentUser.impersonating"
              @click="handleStopImpersonating"
            >
              {{ __('Stop Impersonating') }}
            </DropdownMenuItem>

            <DropdownMenuItem
              as="button"
              v-if="supportsUserSecurity"
              @click="visitUserSecurityPage"
            >
              {{ __('User Security') }}
            </DropdownMenuItem>

            <DropdownMenuItem
              as="button"
              v-if="supportsAuthentication"
              @click="attempt"
            >
              {{ __('Logout') }}
            </DropdownMenuItem>
          </div>
        </nav>
      </DropdownMenu>
    </template>
  </Dropdown>
  <div v-else-if="currentUser" class="flex items-center">
    <img
      v-if="currentUser.avatar"
      :alt="__(':name\'s Avatar', { name: userName })"
      :src="currentUser.avatar"
      class="rounded-full w-8 h-8 mr-3"
    />

    <span class="whitespace-nowrap">
      {{ userName }}
    </span>
  </div>
</template>

<script>
import { Button, Icon } from 'laravel-nova-ui'
import { mapActions, mapGetters, mapMutations } from 'vuex'
import { router } from '@inertiajs/vue3'
import identity from 'lodash/identity'
import omitBy from 'lodash/omitBy'
import pickBy from 'lodash/pickBy'

export default {
  components: {
    Button,
    Icon,
  },

  props: {
    mobile: { type: Boolean, default: false },
  },

  methods: {
    ...mapActions(['logout', 'stopImpersonating']),
    ...mapMutations(['toggleMainMenu']),

    async attempt() {
      if (confirm(this.__('Are you sure you want to log out?'))) {
        this.logout(Nova.config('customLogoutPath'))
          .then(redirect => {
            if (redirect !== null) {
              location.href = redirect
              return
            }

            Nova.redirectToLogin()
          })
          .catch(e => {
            router.reload()
          })
      }
    },

    visitUserSecurityPage() {
      Nova.visit('/user-security')
    },

    handleStopImpersonating() {
      if (confirm(this.__('Are you sure you want to stop impersonating?'))) {
        this.stopImpersonating()
      }
    },

    handleUserMenuClosed() {
      if (this.mobile === true) {
        this.toggleMainMenu()
      }
    },
  },

  computed: {
    ...mapGetters(['currentUser', 'userMenu']),

    userName() {
      return (
        this.currentUser.name || this.currentUser.email || this.__('Nova User')
      )
    },

    formattedItems() {
      return this.userMenu.map(i => {
        let method = i.method || 'GET'
        let props = { href: i.path }

        if (i.external && method == 'GET') {
          return {
            component: 'DropdownMenuItem',
            props: {
              ...props,
              target: i.target || null,
            },
            name: i.name,
            external: i.external,
            on: {},
          }
        }

        return {
          component: 'DropdownMenuItem',
          props: pickBy(
            omitBy(
              {
                ...props,
                method: method !== 'GET' ? method : null,
                data: i.data || null,
                headers: i.headers || null,
                as: method === 'GET' ? 'link' : 'form-button',
              },
              value => value === null
            ),
            identity
          ),
          external: i.external,
          name: i.name,
          on: {},
          badge: i.badge,
        }
      })
    },

    hasUserMenu() {
      return (
        this.currentUser &&
        (this.formattedItems.length > 0 ||
          this.supportsAuthentication ||
          this.currentUser.impersonating)
      )
    },

    supportsAuthentication() {
      return (
        Nova.config('withAuthentication') === true ||
        this.customLogoutPath !== false
      )
    },

    supportsUserSecurity() {
      return Nova.hasSecurityFeatures()
    },

    customLogoutPath() {
      return Nova.config('customLogoutPath')
    },

    dropdownPlacement() {
      return this.mobile === true ? 'top-start' : 'bottom-end'
    },
  },
}
</script>
