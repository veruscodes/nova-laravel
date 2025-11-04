<template>
  <form :action="href" method="POST" @submit="handleSubmit" dusk="form-button">
    <input
      v-for="(value, key) in data"
      type="hidden"
      :name="key"
      :value="value"
    />

    <input
      v-if="method !== 'POST'"
      type="hidden"
      name="_method"
      :value="method"
    />

    <component :is="component" v-bind="$attrs" type="submit">
      <slot />
    </component>
  </form>
</template>

<script setup>
defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  href: { type: String, required: true },
  method: { type: String, required: true },
  data: { type: Object, required: false, default: {} },
  headers: { type: Object, required: false, default: null },
  component: { type: String, default: 'button' },
})

function handleSubmit(event) {
  if (props.headers == null) {
    return
  }

  event.preventDefault()

  Nova.$router.visit(props.href, {
    method: props.method,
    data: props.data,
    headers: props.headers,
  })
}
</script>
