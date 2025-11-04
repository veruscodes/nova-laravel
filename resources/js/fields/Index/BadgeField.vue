<template>
  <div>
    <Badge :label="field.label" :extra-classes="field.typeClass">
      <template #icon>
        <span v-if="field.icon" class="mr-1 -ml-1">
          <Icon :name="field.icon" type="solid" class="inline-block" />
        </span>
      </template>
    </Badge>
  </div>
</template>

<script>
import { Icon } from 'laravel-nova-ui'

export default {
  components: {
    Icon,
  },

  props: ['resourceName', 'viaResource', 'viaResourceId', 'field'],
}
</script>
