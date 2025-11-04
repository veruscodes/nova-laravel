<template>
  <a
    href="https://nova.laravel.com/licenses"
    v-if="!validLicense"
    class="inline-block text-red-500 text-xs font-bold mt-1 text-center uppercase"
  >
    {{ __(`Unregistered`) }}
  </a>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()

const validLicense = computed(() => store.getters.validLicense)
</script>
