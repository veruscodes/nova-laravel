import type {
  ButtonPadding,
  ButtonPaddingMap,
  ButtonSize,
  ButtonSizeMap,
  ButtonVariant,
  ButtonVariantMap,
} from '../types'

const outlineVariant = {
  base: '',
  baseAs: '',

  class:
    'bg-transparent border-gray-300 hover:[&:not(:disabled)]:text-primary-500 dark:border-gray-600',

  sizes: {
    small: 'h-7 text-xs',
    large: 'h-9',
  },

  padding: {
    default: { small: 'px-2', large: 'px-3' },
  },

  states: {},

  loaderSize: { small: 28, large: 32 },
}

export function useButtonStyles() {
  // variants have different sizes and states
  const variants: ButtonVariantMap = {
    solid: {
      base: '',
      baseAs: '',

      class: 'shadow',

      sizes: {
        small: 'h-7 text-xs',
        large: 'h-9',
      },

      padding: {
        default: { small: 'px-2', large: 'px-3' },
        tight: { small: 'px-2', large: 'px-1.5' },
      },

      states: {
        default: {
          small:
            'bg-primary-500 border-primary-500 hover:[&:not(:disabled)]:bg-primary-400 hover:[&:not(:disabled)]:border-primary-400 text-white dark:text-gray-900',
          large:
            'bg-primary-500 border-primary-500 hover:[&:not(:disabled)]:bg-primary-400 hover:[&:not(:disabled)]:border-primary-400 text-white dark:text-gray-900',
        },
        danger: {
          small:
            'bg-red-500 border-red-500 hover:[&:not(:disabled)]:bg-red-400 hover:[&:not(:disabled)]:border-red-400 text-white dark:text-red-950',
          large:
            'bg-red-500 border-red-500 hover:[&:not(:disabled)]:bg-red-400 hover:[&:not(:disabled)]:border-red-400 text-white dark:text-red-950',
        },
      },

      loaderSize: { small: 28, large: 32 },
    },

    ghost: {
      base: '',
      baseAs: '',

      class: 'bg-transparent border-transparent',

      sizes: {
        small: 'h-7 text-xs',
        large: 'h-9',
      },

      padding: {
        default: { small: 'px-2', large: 'px-3' },
        tight: { small: 'px-2', large: 'px-1.5' },
      },

      states: {
        default: {
          small:
            'text-gray-600 dark:text-gray-400 hover:[&:not(:disabled)]:bg-gray-700/5 dark:hover:[&:not(:disabled)]:bg-gray-950',
          large:
            'text-gray-600 dark:text-gray-400 hover:[&:not(:disabled)]:bg-gray-700/5 dark:hover:[&:not(:disabled)]:bg-gray-950',
        },
      },

      loaderSize: { small: 28, large: 32 },
    },

    outline: outlineVariant,

    icon: outlineVariant,

    link: {
      base: '',
      baseAs: '',

      class: 'border-transparent ',
      sizes: {
        small: 'h-7 text-xs',
        large: 'h-9',
      },

      alignment: {
        left: 'text-left',
        center: 'text-center',
        // right: 'text-right',
      },

      padding: {
        default: { small: 'px-2', large: 'px-3' },
      },

      states: {
        default: {
          small: 'text-primary-500 hover:[&:not(:disabled)]:text-primary-400',
          large: 'text-primary-500 hover:[&:not(:disabled)]:text-primary-400',
        },

        mellow: {
          small:
            'text-gray-500 hover:[&:not(:disabled)]:text-gray-400 dark:enabled:text-gray-400 dark:enabled:hover:text-gray-300',
          large:
            'text-gray-500 hover:[&:not(:disabled)]:text-gray-400 dark:enabled:text-gray-400 dark:enabled:hover:text-gray-300',
        },

        danger: {
          small: 'text-red-500 hover:[&:not(:disabled)]:text-red-400',
          large: 'text-red-500 hover:[&:not(:disabled)]:text-red-400',
        },
      },
    },

    action: {
      base: '',
      baseAs: '',

      class:
        'bg-transparent border-transparent text-gray-500 dark:text-gray-400 hover:[&:not(:disabled)]:text-primary-500',

      sizes: {
        large: 'h-9 w-9',
      },

      padding: {
        default: { small: '', large: '' },
      },

      states: {},

      loaderSize: { small: 28, large: 32 },
    },
  }

  const availableSizes = (): ButtonSizeMap => {
    return Object.keys(variants)
      .map(variant => {
        const sizes = variants[<keyof ButtonVariantMap>variant].sizes

        return { [variant]: Object.keys(sizes) }
      })
      .reduce((carry, obj) => {
        return { ...carry, ...obj }
      }, {})
  }

  // function firstVariant(variant: ButtonVariant) {
  //   return Object.keys(variants[variant]['sizes'])[0]
  // }

  function iconType(variant: ButtonVariant, size: ButtonSize) {
    if (variant === 'icon') {
      return 'outline'
    }

    return 'outline'
  }

  function checkSize(variant: ButtonVariant, size: ButtonSize) {
    const sizeMap = availableSizes()
    return sizeMap[variant]?.includes(size) ?? false
  }

  function validateSize(variant: ButtonVariant, size: ButtonSize) {
    if (!checkSize(variant, size)) {
      throw new Error(`Invalid variant/size combination: ${variant}/${size}`)
    }
  }

  const availablePadding = (): ButtonPaddingMap => {
    return Object.keys(variants)
      .map(variant => {
        const padding = variants[variant]?.padding

        return { [variant]: Object.keys(padding ?? []) }
      })
      .reduce((carry, obj) => {
        return { ...carry, ...obj }
      }, {})
  }

  function checkPadding(variant: ButtonVariant, padding: ButtonPadding) {
    const paddingMap = availablePadding()
    return paddingMap[variant]?.includes(padding) ?? false
  }

  function validatePadding(variant: ButtonVariant, padding: ButtonPadding) {
    if (!checkPadding(variant, padding)) {
      throw new Error(
        `Invalid variant/padding combination: ${variant}/${padding}`
      )
    }
  }

  return {
    base: 'border text-left appearance-none cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring ring-primary-200 dark:ring-gray-600 relative disabled:cursor-not-allowed',
    baseAs: 'inline-flex items-center justify-center',
    disabled: 'disabled:opacity-50',
    variants,
    availableSizes,
    checkSize,
    validateSize,
    validatePadding,
    iconType,
  }
}
