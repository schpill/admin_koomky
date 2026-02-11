import { test, expect } from '@playwright/test'

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Mock authentication
    await page.addInitScript(() => {
      localStorage.setItem('auth_token', 'test-token')
    })

    // Mock API responses
    await page.route('**/api/v1/dashboard', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            type: 'dashboard',
            attributes: {
              stats: {
                total_clients: 5,
                active_projects: 3,
                pending_tasks: 12,
                monthly_revenue: 5000
              }
            }
          }
        })
      })
    })

    await page.goto('/')
  })

  test('displays welcome message', async ({ page }) => {
    await expect(page.getByText(/Welcome back/i)).toBeVisible()
  })

  test('shows statistics cards', async ({ page }) => {
    await expect(page.getByText('Total Clients')).toBeVisible()
    await expect(page.getByText('5')).toBeVisible()
    await expect(page.getByText('Active Projects')).toBeVisible()
    await expect(page.getByText('Pending Tasks')).toBeVisible()
  })

  test('opens command palette with Ctrl+K', async ({ page }) => {
    await page.keyboard.press('Control+k')
    await expect(page.getByPlaceholder('Type a command or search')).toBeVisible()
  })

  test('navigates to clients page', async ({ page }) => {
    await page.click('a:has-text("View Clients")')
    await expect(page).toHaveURL('/clients')
  })

  test('toggles dark mode', async ({ page }) => {
    const darkModeToggle = page.getByLabel(/toggle dark mode/i)

    if (await darkModeToggle.isVisible()) {
      await darkModeToggle.click()
      await expect(page.locator('html')).toHaveClass(/dark/)
    }
  })
})
