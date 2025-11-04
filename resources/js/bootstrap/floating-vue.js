import FloatingVue from 'floating-vue'

import 'floating-vue/dist/style.css'

export function setupFloatingVue(app) {
  app.app.use(FloatingVue, {
    preventOverflow: true,
    flip: true,
    themes: {
      Nova: {
        $extend: 'tooltip',
        triggers: ['click'],
        autoHide: true,
        placement: 'bottom',
        html: true,
      },
    },
  })
}
