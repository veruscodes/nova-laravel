<template>
  <transition
    enter-active-class="transition duration-100 ease-out"
    enter-from-class="transform opacity-0"
    enter-to-class="transform opacity-100"
    leave-active-class="transition duration-200 ease-out"
    leave-from-class="transform opacity-100"
    leave-to-class="transform opacity-0"
    mode="out-in"
  >
    <slot />
  </transition>
</template>
