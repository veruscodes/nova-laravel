<template>
  <Dropdown placement="bottom-end">
    <Button
      variant="ghost"
      padding="tight"
      icon="queue-list"
      trailing-icon="chevron-down"
      :aria-label="__('Lens Dropdown')"
    />

    <template #menu>
      <DropdownMenu
        class="divide-y divide-gray-100 dark:divide-gray-800 divide-solid px-1"
        width="auto"
      >
        <ScrollWrap :height="250">
          <div class="py-1">
            <DropdownMenuItem
              v-for="lens in lenses"
              :key="lens.uriKey"
              :href="$url(`/resources/${resourceName}/lens/${lens.uriKey}`)"
              as="link"
              class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800"
            >
              {{ lens.name }}
            </DropdownMenuItem>
          </div>
        </ScrollWrap>
      </DropdownMenu>
    </template>
  </Dropdown>
</template>

<script setup>
import { Button } from 'laravel-nova-ui'

defineProps(['resourceName', 'lenses'])
</script>
