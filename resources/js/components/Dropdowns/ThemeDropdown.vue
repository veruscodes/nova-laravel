<template>
  <Dropdown v-if="themeSwitcherEnabled" placement="bottom-end">
    <Button variant="action" :icon="themeIcon" :class="themeColor" />

    <template #menu>
      <DropdownMenu width="auto">
        <nav class="flex flex-col py-1 px-1">
          <DropdownMenuItem
            as="button"
            size="small"
            class="flex items-center gap-2"
            @click="toggleLightTheme"
          >
            <Icon name="sun" type="micro" />
            <span>{{ __('Light') }}</span>
          </DropdownMenuItem>

          <DropdownMenuItem
            as="button"
            class="flex items-center gap-2"
            @click="toggleDarkTheme"
          >
            <Icon name="moon" type="micro" />
            <span>{{ __('Dark') }}</span>
          </DropdownMenuItem>

          <DropdownMenuItem
            as="button"
            class="flex items-center gap-2"
            @click="toggleSystemTheme"
          >
            <Icon name="computer-desktop" type="micro" />
            <span>{{ __('System') }}</span>
          </DropdownMenuItem>
        </nav>
      </DropdownMenu>
    </template>
  </Dropdown>
</template>

<script>
import { Button, Icon } from 'laravel-nova-ui'

export default {
  components: {
    Button,
    Icon,
  },

  data() {
    return {
      theme: 'system',
      listener: null,
      matcher: window.matchMedia('(prefers-color-scheme: dark)'),
      themes: ['light', 'dark'],
    }
  },

  mounted() {
    if (Nova.config('themeSwitcherEnabled')) {
      if (this.themes.includes(localStorage.novaTheme)) {
        this.theme = localStorage.novaTheme
      }

      this.listener = () => {
        if (this.theme === 'system') {
          this.applyColorScheme()
        }
      }
      this.matcher.addEventListener('change', this.listener)
    } else {
      localStorage.removeItem('novaTheme')
    }
  },

  beforeUnmount() {
    if (Nova.config('themeSwitcherEnabled')) {
      this.matcher.removeEventListener('change', this.listener)
    }
  },

  watch: {
    theme(theme) {
      if (theme === 'light') {
        localStorage.novaTheme = 'light'
        document.documentElement.classList.remove('dark')
      }

      if (theme === 'dark') {
        localStorage.novaTheme = 'dark'
        document.documentElement.classList.add('dark')
      }

      if (theme === 'system') {
        localStorage.removeItem('novaTheme')
        this.applyColorScheme()
      }
    },
  },

  methods: {
    applyColorScheme() {
      if (Nova.config('themeSwitcherEnabled')) {
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
          document.documentElement.classList.add('dark')
        } else {
          document.documentElement.classList.remove('dark')
        }
      }
    },

    toggleLightTheme() {
      this.theme = 'light'
    },

    toggleDarkTheme() {
      this.theme = 'dark'
    },

    toggleSystemTheme() {
      this.theme = 'system'
    },
  },

  computed: {
    themeSwitcherEnabled() {
      return Nova.config('themeSwitcherEnabled')
    },

    themeIcon() {
      // if (this.theme === 'system') {
      //   return 'desktop-computer'
      // }

      return {
        light: 'sun',
        dark: 'moon',
        system: 'computer-desktop',
      }[this.theme]
    },

    themeColor() {
      return {
        light: 'text-primary-500',
        dark: 'dark:text-primary-500',
        system: '',
      }[this.theme]
    },
  },
}
</script>
