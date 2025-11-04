import InlineFormData from '@/fields/Form/InlineFormData'

it('test it can generate proper nested attributes name', () => {
  global.FormData = class FormData {}

  let inlineFormData = new InlineFormData('profile', new FormData())

  expect(inlineFormData.name('email')).toEqual('profile[email]')
  expect(inlineFormData.name('email[]')).toEqual('profile[email][]')
  expect(inlineFormData.name('metadata[][filename]')).toEqual(
    'profile[metadata][][filename]'
  )
  expect(inlineFormData.name('metadata[][extension]')).toEqual(
    'profile[metadata][][extension]'
  )
  expect(inlineFormData.name('vaporFile[attribute][filename]')).toEqual(
    'profile[vaporFile][attribute][filename]'
  )
  expect(inlineFormData.name('vaporFile[attribute][extension]')).toEqual(
    'profile[vaporFile][attribute][extension]'
  )
})

it('can generate proper nested attributes slug', () => {
  global.FormData = class FormData {}

  let inlineFormData = new InlineFormData('profile', new FormData())

  expect(inlineFormData.slug('email')).toEqual('profile.email')
  expect(inlineFormData.slug('email[]')).toEqual('profile.email[]')
  expect(inlineFormData.slug('metadata[][filename]')).toEqual(
    'profile.metadata[][filename]'
  )
  expect(inlineFormData.slug('metadata[][extension]')).toEqual(
    'profile.metadata[][extension]'
  )
  expect(inlineFormData.slug('vaporFile[attribute][filename]')).toEqual(
    'profile.vaporFile[attribute][filename]'
  )
  expect(inlineFormData.slug('vaporFile[attribute][extension]')).toEqual(
    'profile.vaporFile[attribute][extension]'
  )
})
