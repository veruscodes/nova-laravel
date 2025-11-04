import { router } from '@inertiajs/vue3'

export function setupInertia(app, store) {
  router.on('before', () => {
    ;(async () => {
      app.debug('Syncing Inertia props to the store via `inertia:before`...')
      await store.dispatch('assignPropsFromInertia')
    })()
  })

  router.on('navigate', () => {
    ;(async () => {
      app.debug('Syncing Inertia props to the store via `inertia:navigate`...')
      await store.dispatch('assignPropsFromInertia')
    })()
  })

  router.on('start', () => app.$progress.start())
  router.on('finish', () => app.$progress.done())
}
