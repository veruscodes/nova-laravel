<template>
  <div :class="`text-${field.textAlign}`">
    <Dropdown v-if="field.value.length > 0">
      <Button variant="link">
        {{ __('View') }}
      </Button>

      <template #menu>
        <DropdownMenu width="auto">
          <div class="p-2">
            <TagList
              v-if="field.style === 'list'"
              :tags="field.value"
              :resource-name="field.resourceName"
              :editable="false"
              :with-preview="field.withPreview"
            />
            <TagGroup
              v-if="field.style === 'group'"
              :tags="field.value"
              :resource-name="field.resourceName"
              :editable="false"
              :with-preview="field.withPreview"
            />
          </div>
        </DropdownMenu>
      </template>
    </Dropdown>
    <p v-else>&mdash;</p>
  </div>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
}
</script>
