<template>
  <component
    :is="component"
    v-bind="defaultAttributes"
    class="block w-full text-left px-3 focus:outline-none rounded truncate whitespace-nowrap"
    :class="{
      'text-sm py-1.5': size === 'small',
      'text-sm py-2': size === 'large',
      'hover:bg-gray-50 dark:hover:bg-gray-800 focus:ring cursor-pointer':
        !disabled,
      'text-gray-400 dark:text-gray-700 cursor-default': disabled,
      'text-gray-500 active:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400 dark:active:text-gray-600':
        !disabled,
    }"
  >
    <slot />
  </component>
</template>

<script>
export default {
  props: {
    as: {
      type: String,
      default: 'external',
      validator: v => ['button', 'external', 'form-button', 'link'].includes(v),
    },
    disabled: { type: Boolean, default: false },
    size: {
      type: String,
      default: 'small',
      validator: v => ['small', 'large'].includes(v),
    },
  },

  computed: {
    component() {
      return {
        button: 'button',
        external: 'a',
        link: 'Link',
        'form-button': 'FormButton',
      }[this.as]
    },

    defaultAttributes() {
      return {
        ...this.$attrs,
        ...{
          disabled:
            this.as === 'button' && this.disabled === true ? true : null,
          type: this.as === 'button' ? 'button' : null,
        },
      }
    },
  },
}
</script>
