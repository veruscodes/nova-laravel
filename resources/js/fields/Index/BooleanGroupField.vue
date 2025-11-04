<template>
  <div :class="`text-${field.textAlign}`">
    <Dropdown>
      <Button variant="link">
        {{ __('View') }}
      </Button>

      <template #menu>
        <DropdownMenu width="auto">
          <ul v-if="value.length > 0" class="max-w-xxs space-y-2 py-3 px-4">
            <li
              v-for="(option, index) in value"
              :key="index"
              class="flex items-center rounded-full font-bold text-sm leading-tight space-x-2"
              :class="classes[option.checked]"
            >
              <IconBoolean class="flex-none" :value="option.checked" />
              <span class="ml-1">{{ option.label }}</span>
            </li>
          </ul>
          <span
            v-else
            class="block max-w-xxs space-2 my-3 mx-4 rounded-full text-sm leading-tight"
          >
            {{ field.noValueText }}
          </span>
        </DropdownMenu>
      </template>
    </Dropdown>
  </div>
</template>

<script>
import { Button } from 'laravel-nova-ui'

export default {
  components: {
    Button,
  },

  props: ['resourceName', 'field'],

  data: () => ({
    value: [],
    classes: {
      true: 'text-green-500',
      false: 'text-red-500',
    },
  }),

  created() {
    this.field.value = this.field.value || {}

    const hideTrueValues = this.field.hideTrueValues
    const hideFalseValues = this.field.hideFalseValues

    this.value = this.field.options
      .map(o => {
        return {
          name: o.name,
          label: o.label,
          checked: this.field.value[o.name] || false,
        }
      })
      .filter(o => {
        if (hideFalseValues === true && o.checked === false) {
          return false
        } else if (hideTrueValues === true && o.checked === true) {
          return false
        }

        return true
      })
  },
}
</script>
