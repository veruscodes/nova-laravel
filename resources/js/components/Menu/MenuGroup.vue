<template>
  <div v-if="item.items.length > 0">
    <h4
      @click.prevent="handleClick"
      class="flex items-center px-1 py-1 rounded text-left text-gray-500"
      :class="{
        'cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800':
          displayAsButton,
        'font-bold text-primary-500 dark:text-primary-500': item.active,
      }"
    >
      <span class="inline-block shrink-0 w-6 h-6" />

      <span
        class="flex-1 flex items-center w-full tracking-wide uppercase font-bold text-left text-xs px-3 py-1"
      >
        {{ item.name }}
      </span>

      <span
        v-if="item.collapsable"
        class="inline-flex items-center justify-center shrink-0 w-6 h-6"
      >
        <CollapseButton :collapsed="collapsed" :to="item.path" />
      </span>
    </h4>

    <div v-if="!collapsed">
      <component
        :key="item.name"
        v-for="item in item.items"
        :is="item.component"
        :item="item"
      />
    </div>
  </div>
</template>

<script>
import { Collapsable } from '@/mixins'

export default {
  mixins: [Collapsable],

  props: ['item'],

  methods: {
    handleClick() {
      if (this.item.collapsable) {
        this.toggleCollapse()
      }
    },
  },

  computed: {
    component() {
      if (this.item.items.length > 0) {
        return 'div'
      }

      return 'h3'
    },

    displayAsButton() {
      return this.item.items.length > 0 && this.item.collapsable
    },

    collapsedByDefault() {
      return this.item?.collapsedByDefault ?? false
    },
  },
}
</script>
