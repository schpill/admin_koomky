import { test, expect, type Page } from '@playwright/test'

test.describe('Authentication Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/')
  })

  test('displays login form for unauthenticated users', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible()
  })

  test('shows validation errors for empty form', async ({ page }) => {
    await page.click('button[type="submit"]')

    await expect(page.getByText('Email is required')).toBeVisible()
    await expect(page.getByText('Password is required')).toBeVisible()
  })

  test('shows error for invalid credentials', async ({ page }) => {
    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'wrong-password')
    await page.click('button[type="submit"]')

    await expect(page.getByText(/invalid credentials/i)).toBeVisible()
  })

  test('redirects to dashboard after successful login', async ({ page }) => {
    // Mock API response
    await page.route('**/api/v1/auth/login', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            data: {
              type: 'user',
              id: '123',
              attributes: { name: 'Test User', email: 'test@example.com' }
            },
            meta: {
              token: {
                access_token: 'test-token',
                refresh_token: 'test-refresh'
              }
            }
          }
        })
      })
    })

    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password')
    await page.click('button[type="submit"]')

    await expect(page).toHaveURL('/')
    await expect(page.getByText('Welcome back')).toBeVisible()
  })
})

test.describe('Registration Flow', () => {
  test('validates password requirements', async ({ page }) => {
    await page.goto('/auth/register')
    await page.fill('input[name="password"]', 'short')
    await page.blur('input[name="password"]')

    await expect(page.getByText(/at least 8 characters/i)).toBeVisible()
  })

  test('confirms password match', async ({ page }) => {
    await page.goto('/auth/register')
    await page.fill('input[name="password"]', 'password123')
    await page.fill('input[name="password_confirmation"]', 'password456')
    await page.blur('input[name="password_confirmation"]')

    await expect(page.getByText(/passwords do not match/i)).toBeVisible()
  })

  test('successfully registers new user', async ({ page }) => {
    await page.route('**/api/v1/auth/register', async route => {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            data: {
              type: 'user',
              id: '123',
              attributes: { name: 'New User', email: 'new@example.com' }
            }
          }
        })
      })
    })

    await page.goto('/auth/register')
    await page.fill('input[name="name"]', 'New User')
    await page.fill('input[name="email"]', 'new@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.fill('input[name="password_confirmation"]', 'password123')
    await page.click('button[type="submit"]')

    await expect(page).toHaveURL('/auth/login')
    await expect(page.getByText(/registration successful/i)).toBeVisible()
  })
})
