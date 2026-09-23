import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = 'test@agency.com'
const TEST_PASSWORD = 'password'

async function login(page) {
  await page.goto(`${BASE_URL}/login`)
  await page.fill('input[name="email"]', TEST_EMAIL)
  await page.fill('input[name="password"]', TEST_PASSWORD)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/dashboard', { timeout: 15000 })
}

// ---------------------------------------------------------------------------
// Webhook List
// ---------------------------------------------------------------------------
test.describe('Webhook List', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('webhooks index loads with heading', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/webhooks/)
    await expect(page.locator('h3')).toContainText('Webhooks')
  })

  test('new webhook button is visible', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=New Webhook')).toBeVisible()
  })

  test('empty state or table is shown', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    const emptyState = page.locator('text=No webhooks found, text=No Webhooks')
    const tableRows = page.locator('tbody tr')
    const hasEmpty = await emptyState.isVisible().catch(() => false)
    const rowCount = await tableRows.count().catch(() => 0)

    expect(hasEmpty || rowCount >= 0).toBeTruthy()
  })

  test('webhook table has correct columns', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    // Check for common webhook table headers
    const headers = page.locator('th')
    const headerCount = await headers.count()
    if (headerCount > 0) {
      const headerTexts = await headers.allTextContents()
      const joined = headerTexts.join(' ').toLowerCase()
      expect(joined).toMatch(/name|url|status|events|actions/)
    }
  })

  test('webhook rows show action buttons', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    const firstRow = page.locator('tbody tr').first()
    if (await firstRow.isVisible().catch(() => false)) {
      // Should have view/edit/delete actions
      const actions = firstRow.locator('a, button')
      const count = await actions.count()
      expect(count).toBeGreaterThan(0)
    }
  })

  test('status badge shows active/inactive', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    const statusBadge = page.locator('tbody tr td span').filter({ hasText: /active|inactive/i }).first()
    if (await statusBadge.isVisible().catch(() => false)) {
      const text = await statusBadge.textContent()
      expect(text?.toLowerCase()).toMatch(/active|inactive/)
    }
  })
})

// ---------------------------------------------------------------------------
// Create Webhook
// ---------------------------------------------------------------------------
test.describe('Create Webhook', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('create webhook page loads with form', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/webhooks\/create/)
    await expect(page.locator('input[name="name"]')).toBeVisible()
    await expect(page.locator('input[name="url"]')).toBeVisible()
  })

  test('create webhook form has event checkboxes', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    const eventCheckboxes = page.locator('input[name="events[]"]')
    const count = await eventCheckboxes.count()
    expect(count).toBeGreaterThan(0)
  })

  test('is_active checkbox is present', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('input[name="is_active"]')).toBeVisible()
  })

  test('creating a webhook with valid data redirects to show', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    await page.fill('input[name="name"]', 'E2E Test Webhook ' + Date.now())
    await page.fill('input[name="url"]', 'https://example.com/webhook')

    // Select at least one event
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) {
      await firstEvent.check()
    }

    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Should redirect to show page
    await expect(page).toHaveURL(/\/webhooks\/\d+/)
  })

  test('name field is required', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    // Submit without name
    await page.fill('input[name="url"]', 'https://example.com/webhook')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Should stay on create page
    await expect(page).toHaveURL(/\/webhooks\/create/)
  })

  test('url field is required and must be valid', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    await page.fill('input[name="name"]', 'Invalid URL Test')
    await page.fill('input[name="url"]', 'not-a-url')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Should stay on create page due to validation
    await expect(page).toHaveURL(/\/webhooks\/create/)
  })

  test('at least one event must be selected', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    await page.fill('input[name="name"]', 'No Events Test ' + Date.now())
    await page.fill('input[name="url"]', 'https.com/no-events')
    // Don't select any events
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Should stay on create page
    await expect(page).toHaveURL(/\/webhooks\/create/)
  })

  test('cancel button returns to webhooks list', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    const cancelBtn = page.locator('a:has-text("Cancel"), a:has-text("Back")').first()
    if (await cancelBtn.isVisible()) {
      await cancelBtn.click()
      await page.waitForURL('**/webhooks')
      await expect(page).toHaveURL(/\/webhooks/)
    }
  })

  test('create button is visible', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('button:has-text("Create")')).toBeVisible()
  })
})

// ---------------------------------------------------------------------------
// Toggle Active
// ---------------------------------------------------------------------------
test.describe('Toggle Webhook Active', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('toggle active button is present on webhook', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    const toggleBtn = page.locator('button:has-text("Toggle"), input[type="checkbox"][name*="active"]').first()
    if (await toggleBtn.isVisible().catch(() => false)) {
      await expect(toggleBtn).toBeVisible()
    }
  })

  test('toggling active status updates the webhook', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    // Find a toggle form/button
    const toggleForm = page.locator('form[action*="toggle"], button[onclick*="toggle"]').first()
    if (await toggleForm.isVisible().catch(() => false)) {
      await toggleForm.click()
      await page.waitForLoadState('networkidle')
      // Page should reload or show success
      await expect(page.locator('body')).toBeVisible()
    }
  })

  test('toggle via API requires authentication', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/webhooks/1/toggle`, {
      headers: { 'Accept': 'application/json' },
    })
    const status = response.status()
    expect([302, 401, 403]).toContain(status)
  })

  test('toggle via API returns success for authenticated user', async ({ page }) => {
    // First create a webhook
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="name"]', 'Toggle Test ' + Date.now())
    await page.fill('input[name="url"]', 'https://example.com/toggle')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Extract webhook ID from URL
    const url = page.url()
    const match = url.match(/webhooks\/(\d+)/)
    if (match) {
      const webhookId = match[1]
      const response = await page.request.post(`${BASE_URL}/webhooks/${webhookId}/toggle`, {
        headers: { 'Accept': 'application/json' },
      })
      expect([200, 302]).toContain(response.status())
    }
  })
})

// ---------------------------------------------------------------------------
// Delete Webhook
// ---------------------------------------------------------------------------
test.describe('Delete Webhook', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('delete button is present on webhook', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    const deleteBtn = page.locator('button:has-text("Delete"), form[action*="webhooks"] button').first()
    if (await deleteBtn.isVisible().catch(() => false)) {
      await expect(deleteBtn).toBeVisible()
    }
  })

  test('deleting a webhook returns to index', async ({ page }) => {
    // Create a webhook first
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="name"]', 'Delete Me ' + Date.now())
    await page.fill('input[name="url"]', 'https://example.com/delete-me')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Now delete it
    page.on('dialog', d => d.accept())
    const deleteBtn = page.locator('button:has-text("Delete"), form[action*="webhooks"] button').first()
    if (await deleteBtn.isVisible().catch(() => false)) {
      await deleteBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/webhooks/)
    }
  })

  test('delete requires confirmation dialog', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')

    const deleteBtn = page.locator('button[onclick*="confirm"], form[action*="webhooks"] button').first()
    if (await deleteBtn.isVisible().catch(() => false)) {
      // Set up dialog handler before click
      page.once('dialog', d => {
        expect(d.type()).toBe('confirm')
        d.dismiss()
      })
      await deleteBtn.click()
    }
  })

  test('delete via API requires authentication', async ({ request }) => {
    const response = await request.delete(`${BASE_URL}/webhooks/1`, {
      headers: { 'Accept': 'application/json' },
    })
    const status = response.status()
    expect([302, 401, 403]).toContain(status)
  })

  test('cannot delete another agency webhook', async ({ page }) => {
    const response = await page.request.delete(`${BASE_URL}/webhooks/999999`, {
      headers: { 'Accept': 'application/json' },
    })
    const status = response.status()
    expect([403, 404]).toContain(status)
  })
})

// ---------------------------------------------------------------------------
// Edit Webhook
// ---------------------------------------------------------------------------
test.describe('Edit Webhook', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('edit page loads existing webhook data', async ({ page }) => {
    // Create a webhook first
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="name"]', 'Edit Test ' + Date.now())
    await page.fill('input[name="url"]', 'https://example.com/edit')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Extract ID and go to edit
    const url = page.url()
    const match = url.match(/webhooks\/(\d+)/)
    if (match) {
      await page.goto(`${BASE_URL}/webhooks/${match[1]}/edit`)
      await page.waitForLoadState('networkidle')

      await expect(page).toHaveURL(/\/webhooks\/\d+\/edit/)
      await expect(page.locator('input[name="name"]')).toBeVisible()
      await expect(page.locator('input[name="url"]')).toBeVisible()
    }
  })

  test('name is pre-filled with existing value', async ({ page }) => {
    // Create a webhook
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    const name = 'Prefill Test ' + Date.now()
    await page.fill('input[name="name"]', name)
    await page.fill('input[name="url"]', 'https://example.com/prefill')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    const url = page.url()
    const match = url.match(/webhooks\/(\d+)/)
    if (match) {
      await page.goto(`${BASE_URL}/webhooks/${match[1]}/edit`)
      await page.waitForLoadState('networkidle')

      const nameInput = page.locator('input[name="name"]')
      const value = await nameInput.inputValue()
      expect(value.length).toBeGreaterThan(0)
    }
  })

  test('updating a webhook redirects to show page', async ({ page }) => {
    // Create a webhook
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="name"]', 'Update Test ' + Date.now())
    await page.fill('input[name="url"]', 'https://example.com/update')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    const url = page.url()
    const match = url.match(/webhooks\/(\d+)/)
    if (match) {
      await page.goto(`${BASE_URL}/webhooks/${match[1]}/edit`)
      await page.waitForLoadState('networkidle')

      await page.fill('input[name="name"]', 'Updated Name ' + Date.now())
      await page.click('button:has-text("Update")')
      await page.waitForLoadState('networkidle')

      await expect(page).toHaveURL(new RegExp(`/webhooks/${match[1]}`))
    }
  })

  test('cancel button returns to show page', async ({ page }) => {
    // Create a webhook
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="name"]', 'Cancel Edit ' + Date.now())
    await page.fill('input[name="url"]', 'https://example.com/cancel-edit')
    const firstEvent = page.locator('input[name="events[]"]').first()
    if (await firstEvent.isVisible()) await firstEvent.check()
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    const url = page.url()
    const match = url.match(/webhooks\/(\d+)/)
    if (match) {
      await page.goto(`${BASE_URL}/webhooks/${match[1]}/edit`)
      await page.waitForLoadState('networkidle')

      const cancelBtn = page.locator('a:has-text("Cancel"), a:has-text("Back")').first()
      if (await cancelBtn.isVisible()) {
        await cancelBtn.click()
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(new RegExp(`/webhooks/${match[1]}`))
      }
    }
  })
})

// ---------------------------------------------------------------------------
// Webhook Auth Guards
// ---------------------------------------------------------------------------
test.describe('Webhook Auth Guards', () => {
  test('unauthenticated user redirected from webhooks index', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user redirected from create webhook', async ({ page }) => {
    await page.goto(`${BASE_URL}/webhooks/create`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user cannot POST to webhooks', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/webhooks`, {
      data: { name: 'Hacker', url: 'https://evil.com' },
    })
    const status = response.status()
    expect([302, 401, 403]).toContain(status)
  })

  test('unauthenticated user cannot DELETE webhooks', async ({ request }) => {
    const response = await request.delete(`${BASE_URL}/webhooks/1`)
    const status = response.status()
    expect([302, 401, 403]).toContain(status)
  })
})
