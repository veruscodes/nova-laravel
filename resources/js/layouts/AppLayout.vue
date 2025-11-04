<template>
  <div id="nova">
    <MainHeader />

    <!-- Content -->
    <div dusk="content">
      <div
        class="hidden lg:block lg:absolute left-0 bottom-0 lg:top-[56px] lg:bottom-auto w-60 px-3 py-8"
      >
        <!-- The Main Menu on desktop gets extra padding to keep the bottom of the sidebar from feeling crowded -->
        <MainMenu class="pb-24" data-screen="desktop" />
      </div>

      <div class="p-4 md:py-8 md:px-12 lg:ml-60 space-y-8">
        <Breadcrumbs v-if="breadcrumbsEnabled" />

        <FadeTransition>
          <slot />
        </FadeTransition>

        <Footer />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useInteractsWithErrors } from '@/composables/useInteractsWithErrors'
import { computed } from 'vue'
import MainHeader from '@/layouts/MainHeader'
import Footer from '@/layouts/Footer'

defineOptions({
  name: 'AppLayout',
})

useInteractsWithErrors()

const breadcrumbsEnabled = computed(() => Nova.config('breadcrumbsEnabled'))
</script>
