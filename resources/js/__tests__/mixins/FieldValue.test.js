import FieldValue from '@/mixins/FieldValue'

class DummyComponent {
  constructor(value) {
    this.field = {
      value: value,
      displayedAs: null,
    }
  }
}

test('it can validate given value as integer', () => {
  let form = new DummyComponent(5)

  expect(FieldValue.methods.isEqualsToValue.call(form, 5)).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, '5')).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, 0)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '0')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, null)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'laravel')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'nova')).toBe(false)
})

test('it can validate given value as integer (string)', () => {
  let form = new DummyComponent('5')

  expect(FieldValue.methods.isEqualsToValue.call(form, 5)).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, '5')).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, 0)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '0')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, null)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'laravel')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'nova')).toBe(false)
})

test('it can validate given value as string', () => {
  let form = new DummyComponent('laravel')

  expect(FieldValue.methods.isEqualsToValue.call(form, 5)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '5')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 0)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '0')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, null)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'laravel')).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'nova')).toBe(false)
})

test('it can validate given value as empty string', () => {
  let form = new DummyComponent('')

  expect(FieldValue.methods.isEqualsToValue.call(form, 5)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '5')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 0)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '0')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, null)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '')).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'laravel')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'nova')).toBe(false)
})

test('it can validate given value as null', () => {
  let form = new DummyComponent(null)

  expect(FieldValue.methods.isEqualsToValue.call(form, 5)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '5')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 0)).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, '0')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, null)).toBe(true)
  expect(FieldValue.methods.isEqualsToValue.call(form, '')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'laravel')).toBe(false)
  expect(FieldValue.methods.isEqualsToValue.call(form, 'nova')).toBe(false)
})
