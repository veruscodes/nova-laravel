<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <Badge class="mt-1" :label="field.label" :extra-classes="field.typeClass">
        <template #icon>
          <span v-if="field.icon" class="mr-1 -ml-1">
            <Icon :name="field.icon" type="solid" class="inline-block" />
          </span>
        </template>
      </Badge>
    </template>
  </PanelItem>
</template>

<script>
import { Icon } from 'laravel-nova-ui'

export default {
  components: {
    Icon,
  },

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
}
</script>
