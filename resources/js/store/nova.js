import { router, usePage } from '@inertiajs/vue3'
import filled from '@/util/filled'

export default {
  state: () => ({
    baseUri: '/nova',
    currentUser: null,
    currentUserPasswordConfirmed: null,
    mainMenu: [],
    userMenu: [],
    breadcrumbs: [],
    resources: [],
    version: '5.x',
    mainMenuShown: false,
    canLeaveModal: true,
    validLicense: true,
    queryStringParams: {},
    compiledQueryStringParams: '',
  }),

  getters: {
    currentUser: s => s.currentUser,
    currentUserPasswordConfirmed: s => s.currentUserPasswordConfirmed ?? false,
    currentVersion: s => s.version,
    mainMenu: s => s.mainMenu,
    userMenu: s => s.userMenu,
    breadcrumbs: s => s.breadcrumbs,
    mainMenuShown: s => s.mainMenuShown,
    canLeaveModal: s => s.canLeaveModal,
    validLicense: s => s.validLicense,
    queryStringParams: s => s.queryStringParams,
  },

  mutations: {
    allowLeavingModal(state) {
      state.canLeaveModal = true
    },

    preventLeavingModal(state) {
      state.canLeaveModal = false
    },

    toggleMainMenu(state) {
      state.mainMenuShown = !state.mainMenuShown
      localStorage.setItem('nova.mainMenu.open', state.mainMenuShown)
    },
  },

  actions: {
    async login({ commit, dispatch }, { email, password, remember }) {
      await Nova.request().post(Nova.url('/login'), {
        email,
        password,
        remember,
      })
    },

    async logout({ state }, customLogoutPath) {
      let response = null

      if (!Nova.config('withAuthentication') && customLogoutPath) {
        response = await Nova.request().post(customLogoutPath)
      } else {
        response = await Nova.request().post(Nova.url('/logout'))
      }

      return response?.data?.redirect || null
    },

    async startImpersonating({}, { resource, resourceId }) {
      let response = null

      response = await Nova.request().post(`/nova-api/impersonate`, {
        resource,
        resourceId,
      })

      let redirect = response?.data?.redirect || null

      if (redirect !== null) {
        location.href = redirect
        return
      }

      Nova.visit('/')
    },

    async stopImpersonating({}) {
      let response = null

      response = await Nova.request().delete(`/nova-api/impersonate`)

      let redirect = response?.data?.redirect || null

      if (redirect !== null) {
        location.href = redirect
        return
      }

      Nova.visit('/')
    },

    async confirmedPasswordStatus({ state, dispatch }) {
      const {
        data: { confirmed },
      } = await Nova.request().get(
        Nova.url('/user-security/confirmed-password-status')
      )

      dispatch(confirmed ? 'passwordConfirmed' : 'passwordUnconfirmed')
    },

    async passwordConfirmed({ state, dispatch }) {
      state.currentUserPasswordConfirmed = true

      setTimeout(() => dispatch('passwordUnconfirmed'), 500000)
    },

    async passwordUnconfirmed({ state }) {
      state.currentUserPasswordConfirmed = false
    },

    async assignPropsFromInertia({ state, dispatch }) {
      const props = usePage().props

      let config = props.novaConfig || Nova.appConfig
      let { resources, base, version, mainMenu, userMenu } = config

      let user = props.currentUser
      let validLicense = props.validLicense
      let breadcrumbs = props.breadcrumbs

      Nova.appConfig = config
      state.breadcrumbs = breadcrumbs || []
      state.currentUser = user
      state.validLicense = validLicense
      state.resources = resources
      state.baseUri = base
      state.version = version
      state.mainMenu = mainMenu
      state.userMenu = userMenu

      dispatch('syncQueryString')
    },

    async fetchPolicies({ state, dispatch }) {
      await dispatch('assignPropsFromInertia')
    },

    async syncQueryString({ state }) {
      let searchParams = new URLSearchParams(window.location.search)

      state.queryStringParams = Object.fromEntries(searchParams.entries())
      state.compiledQueryStringParams = searchParams.toString()
    },

    async updateQueryString({ state }, value) {
      let searchParams = new URLSearchParams(window.location.search)
      let page = await router.decryptHistory()
      let nextUrl = null

      Object.entries(value).forEach(([i, v]) => {
        if (!filled(v)) {
          searchParams.delete(i)
        } else {
          searchParams.set(i, v || '')
        }
      })

      if (state.compiledQueryStringParams !== searchParams.toString()) {
        if (page.url !== `${window.location.pathname}?${searchParams}`) {
          nextUrl = `${window.location.pathname}?${searchParams}`
        }

        state.compiledQueryStringParams = searchParams.toString()
      }

      Nova.$emit('query-string-changed', searchParams)
      state.queryStringParams = Object.fromEntries(searchParams.entries())

      return new Promise((resolve, reject) => {
        resolve({ searchParams, nextUrl, page })
      })
    },
  },
}
