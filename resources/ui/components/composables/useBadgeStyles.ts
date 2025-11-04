export function useBadgeStyles() {
  // prettier-ignore
  return {
    common: {
      wrapper: 'min-h-6 inline-flex items-center space-x-1 whitespace-nowrap px-2 text-xs font-bold uppercase',
      button: '',
      buttonStroke: '',
    },

    variants: {
      wrapper: {
        default: 'bg-gray-100 text-gray-700 dark:bg-gray-400 dark:text-gray-900',
        info: 'bg-blue-100 text-blue-700 dark:bg-blue-400 dark:text-blue-900',
        warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-400 dark:text-yellow-900',
        success: 'bg-green-100 text-green-700 dark:bg-green-300 dark:text-green-900',
        danger: 'bg-red-100 text-red-700 dark:bg-red-400 dark:text-red-900',
      },
      button: {
          default: 'hover:bg-gray-500/20',
          info: 'hover:bg-blue-600/20',
          warning: 'hover:bg-yellow-600/20',
          success: 'hover:bg-green-600/20',
          danger: 'hover:bg-red-600/20',
      },
      buttonStroke: {
          default: 'stroke-gray-600/50 dark:stroke-gray-800 group-hover:stroke-gray-600/75 dark:group-hover:stroke-gray-800',
          info: 'stroke-blue-700/50 dark:stroke-blue-800 group-hover:stroke-blue-700/75 dark:group-hover:stroke-blue-800',
          warning: 'stroke-yellow-700/50 dark:stroke-yellow-800 group-hover:stroke-yellow-700/75 dark:group-hover:stroke-yellow-800',
          success: 'stroke-green-700/50 dark:stroke-green-800 group-hover:stroke-green-700/75 dark:group-hover:stroke-green-800',
          danger: 'stroke-red-600/50 dark:stroke-red-800 group-hover:stroke-red-600/75 dark:group-hover:stroke-red-800',
      },
    },

    types: {
      pill: 'rounded-full',
      brick: 'rounded-md',
    },
  }
}
