import { test, expect } from '@playwright/test'

test.describe('Client Management', () => {
  test.beforeEach(async ({ page }) => {
    // Mock authentication
    await page.addInitScript(() => {
      localStorage.setItem('auth_token', 'test-token')
    })

    // Mock API responses
    await page.route('**/api/v1/clients*', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            data: [
              {
                type: 'client',
                id: '1',
                attributes: {
                  reference: 'CLI-20240101-0001',
                  name: 'Acme Corporation',
                  email: 'contact@acme.com',
                  status: 'active'
                }
              }
            ],
            meta: {
              total: 1,
              per_page: 15,
              current_page: 1,
              last_page: 1
            }
          }
        })
      })
    })

    await page.goto('/clients')
  })

  test('displays clients list', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Clients' })).toBeVisible()
    await expect(page.getByText('Acme Corporation')).toBeVisible()
  })

  test('searches clients', async ({ page }) => {
    const searchInput = page.getByPlaceholder('Search clients')
    await searchInput.fill('Acme')

    // Should trigger API call with search parameter
    await expect(page.getByText('Acme Corporation')).toBeVisible()
  })

  test('navigates to new client form', async ({ page }) => {
    await page.click('a:has-text("Add Client")')
    await expect(page).toHaveURL('/clients/new')
    await expect(page.getByRole('heading', { name: 'New Client' })).toBeVisible()
  })

  test('filters by status', async ({ page }) => {
    await page.selectOption('select[name="status"]', 'active')

    // Should trigger API call with status filter
    await expect(page.getByText('Acme Corporation')).toBeVisible()
  })
})
