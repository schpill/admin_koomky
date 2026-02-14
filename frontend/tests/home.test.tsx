import { render, screen } from '@testing-library/react'
import { expect, test } from 'vitest'
import Page from '../app/page'

test('Home page renders welcome message', () => {
  render(<Page />)
  expect(screen.getByRole('heading', { name: /Koomky/i })).toBeDefined()
})
