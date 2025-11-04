<template>
  <nav
    v-if="hasItems"
    class="text-gray-500 font-semibold"
    aria-label="breadcrumb"
    dusk="breadcrumbs"
  >
    <ol>
      <li
        v-for="(item, index) in breadcrumbs"
        :key="index"
        v-bind="{
          'aria-current': index === breadcrumbs.length - 1 ? 'page' : null,
        }"
        class="inline-block"
      >
        <div class="flex items-center">
          <Link
            :href="$url(item.path)"
            v-if="item.path !== null && index < breadcrumbs.length - 1"
            class="link-default"
          >
            {{ item.name }}
          </Link>
          <span v-else>{{ item.name }}</span>
          <Icon
            v-if="index < breadcrumbs.length - 1"
            name="chevron-right"
            type="micro"
            class="mx-2 text-gray-300 dark:text-gray-700"
          />
        </div>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { Icon } from 'laravel-nova-ui'
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()

const breadcrumbs = computed(() => store.getters.breadcrumbs)
const hasItems = computed(() => breadcrumbs.value.length > 0)
</script>
