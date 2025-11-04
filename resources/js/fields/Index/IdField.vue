<template>
  <div :class="`text-${field.textAlign}`">
    <Link
      @click.stop
      v-if="fieldHasValue && !isPivot && authorizedToView"
      :href="$url(`/resources/${resourceName}/${field.value}`)"
      class="link-default"
    >
      {{ fieldValue }}
    </Link>
    <p v-else-if="fieldHasValue || isPivot">
      {{ field.pivotValue || fieldValue }}
    </p>
    <p v-else>&mdash;</p>
  </div>
</template>

<script>
import { FieldValue } from '@/mixins'

export default {
  mixins: [FieldValue],

  props: ['resource', 'resourceName', 'field'],

  computed: {
    isPivot() {
      return this.field.pivotValue != null
    },

    authorizedToView() {
      return this.resource?.authorizedToView ?? false
    },
  },
}
</script>
