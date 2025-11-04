<template>
  <div class="sidebar-list">
    <MenuItem :key="item.key" v-for="item in item.items" :item="item" />
  </div>
</template>

<script setup>
defineProps({
  item: { type: Object },
})
</script>
