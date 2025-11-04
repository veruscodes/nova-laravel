<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <IconBoolean :value="field.value" :nullable="field.nullable" />
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  computed: {
    label() {
      return this.field.value == true ? this.__('Yes') : this.__('No')
    },
  },
}
</script>
