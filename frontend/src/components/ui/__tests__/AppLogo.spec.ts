import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import AppLogo from '@/components/ui/AppLogo.vue'

describe('AppLogo', () => {
  it('mostra o nome do produto com o ícone decorativo', () => {
    const logo = mount(AppLogo)

    expect(logo.text()).toBe('Fintech Wallet')
    expect(logo.find('svg').attributes('aria-hidden')).toBe('true')
  })
})
