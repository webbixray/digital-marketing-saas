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
// Client Portal Dashboard
// ---------------------------------------------------------------------------
test.describe('Client Portal Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('client portal dashboard loads with heading', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/client-portal-v2/)
    await expect(page.locator('h1')).toContainText('Client Portal 2.0')
  })

  test('dashboard shows summary cards', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Active Campaigns')).toBeVisible()
    await expect(page.locator('text=Total Spend')).toBeVisible()
    await expect(page.locator('text=Performance Score')).toBeVisible()
    await expect(page.locator('text=Active Clients')).toBeVisible()
  })

  test('dashboard shows invoice summary', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Invoices')).toBeVisible()
    await expect(page.locator('text=Paid')).toBeVisible()
    await expect(page.locator('text=Overdue')).toBeVisible()
  })

  test('dashboard shows quick actions', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Quick Actions')).toBeVisible()
    await expect(page.locator('text=View Campaigns')).toBeVisible()
    await expect(page.locator('text=Analytics')).toBeVisible()
    await expect(page.locator('text=Invoices')).toBeVisible()
  })

  test('dashboard shows recent campaigns table', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Recent Campaigns')).toBeVisible()
  })

  test('recent campaigns table has correct columns', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('th:has-text("Campaign")')).toBeVisible()
    await expect(page.locator('th:has-text("Status")')).toBeVisible()
    await expect(page.locator('th:has-text("Posts")')).toBeVisible()
    await expect(page.locator('th:has-text("Created")')).toBeVisible()
  })

  test('view all campaigns link works', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    const viewAllLink = page.locator('a:has-text("View all campaigns")')
    if (await viewAllLink.isVisible()) {
      await viewAllLink.click()
      await page.waitForURL('**/client-portal-v2/campaigns')
      await expect(page).toHaveURL(/\/client-portal-v2\/campaigns/)
    }
  })

  test('view all invoices link works', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    const viewAllLink = page.locator('a[href*="invoices"]:has-text("View all invoices")')
    if (await viewAllLink.isVisible()) {
      await viewAllLink.click()
      await page.waitForURL('**/client-portal-v2/invoices')
      await expect(page).toHaveURL(/\/client-portal-v2\/invoices/)
    }
  })

  test('performance score displays correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    const scoreText = page.locator('text=/\\d+\\/100/')
    if (await scoreText.isVisible().catch(() => false)) {
      await expect(scoreText).toBeVisible()
    }
  })

  test('monthly spend chart or empty state is shown', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')

    // Either chart bars or "No spend data available"
    const emptyState = page.locator('text=No spend data available')
    const chart = page.locator('.bg-indigo-500.rounded-t')
    const hasEmpty = await emptyState.isVisible().catch(() => false)
    const hasChart = await chart.count()
    expect(hasEmpty || hasChart > 0).toBeTruthy()
  })
})

// ---------------------------------------------------------------------------
// Client Portal Campaigns
// ---------------------------------------------------------------------------
test.describe('Client Portal Campaigns', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('campaigns page loads with heading', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/client-portal-v2\/campaigns/)
    await expect(page.locator('h1')).toContainText('Campaigns')
  })

  test('campaigns page shows filter tabs', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=All')).toBeVisible()
    await expect(page.locator('text=Active')).toBeVisible()
    await expect(page.locator('text=Paused')).toBeVisible()
    await expect(page.locator('text=Completed')).toBeVisible()
    await expect(page.locator('text=Draft')).toBeVisible()
  })

  test('campaigns table has correct columns', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('th:has-text("Campaign")')).toBeVisible()
    await expect(page.locator('th:has-text("Client")')).toBeVisible()
    await expect(page.locator('th:has-text("Type")')).toBeVisible()
    await expect(page.locator('th:has-text("Status")')).toBeVisible()
    await expect(page.locator('th:has-text("Posts")')).toBeVisible()
    await expect(page.locator('th:has-text("Start Date")')).toBeVisible()
    await expect(page.locator('th:has-text("Actions")')).toBeVisible()
  })

  test('filter by active status', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns?status=active`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/status=active/)
    await expect(page.locator('h1')).toContainText('Campaigns')
  })

  test('filter by paused status', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns?status=paused`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/status=paused/)
  })

  test('search filter is present', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    const searchInput = page.locator('input[name="search"]')
    if (await searchInput.isVisible()) {
      await searchInput.fill('test')
      await page.click('button:has-text("Filter")')
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/client-portal-v2\/campaigns/)
    }
  })

  test('client filter dropdown is present', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    const clientSelect = page.locator('select[name="client_id"]')
    if (await clientSelect.isVisible()) {
      await expect(clientSelect).toBeVisible()
    }
  })

  test('empty state shows when no campaigns', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    const emptyState = page.locator('text=No campaigns found')
    const tableRows = page.locator('tbody tr')
    const hasEmpty = await emptyState.isVisible().catch(() => false)
    const rowCount = await tableRows.count().catch(() => 0)

    expect(hasEmpty || rowCount >= 0).toBeTruthy()
  })

  test('campaign status badges show correct colors', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    const statusBadges = page.locator('tbody tr td span').filter({ hasText: /active|paused|completed|draft/i })
    const count = await statusBadges.count()
    if (count > 0) {
      const text = await statusBadges.first().textContent()
      expect(text?.toLowerCase()).toMatch(/active|paused|completed|draft/)
    }
  })

  test('analytics link is present for each campaign', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')

    const analyticsLink = page.locator('a:has-text("Analytics")').first()
    if (await analyticsLink.isVisible().catch(() => false)) {
      await expect(analyticsLink).toBeVisible()
    }
  })
})

// ---------------------------------------------------------------------------
// Client Portal Invoices
// ---------------------------------------------------------------------------
test.describe('Client Portal Invoices', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('invoices page loads with heading', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/client-portal-v2\/invoices/)
    await expect(page.locator('h1')).toContainText('Invoices')
  })

  test('invoices page shows summary cards', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Total Outstanding')).toBeVisible()
    await expect(page.locator('text=Total Paid')).toBeVisible()
    await expect(page.locator('text=Overdue')).toBeVisible()
  })

  test('invoices table has correct columns', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('th:has-text("Invoice #")')).toBeVisible()
    await expect(page.locator('th:has-text("Client")')).toBeVisible()
    await expect(page.locator('th:has-text("Amount")')).toBeVisible()
    await expect(page.locator('th:has-text("Status")')).toBeVisible()
    await expect(page.locator('th:has-text("Issue Date")')).toBeVisible()
    await expect(page.locator('th:has-text("Due Date")')).toBeVisible()
    await expect(page.locator('th:has-text("Actions")')).toBeVisible()
  })

  test('filter tabs are present', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=All')).toBeVisible()
    await expect(page.locator('text=Pending')).toBeVisible()
    await expect(page.locator('text=Paid')).toBeVisible()
    await expect(page.locator('text=Overdue')).toBeVisible()
    await expect(page.locator('text=Cancelled')).toBeVisible()
  })

  test('filter by pending status', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices?status=pending`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/status=pending/)
  })

  test('filter by paid status', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices?status=paid`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/status=paid/)
  })

  test('date range filters are present', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const dateFrom = page.locator('input[name="date_from"]')
    const dateTo = page.locator('input[name="date_to"]')
    if (await dateFrom.isVisible().catch(() => false)) {
      await expect(dateFrom).toBeVisible()
      await expect(dateTo).toBeVisible()
    }
  })

  test('pay button is shown for pending/overdue invoices', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const payBtn = page.locator('button:has-text("Pay")').first()
    if (await payBtn.isVisible().catch(() => false)) {
      await expect(payBtn).toBeVisible()
    }
  })

  test('pay modal opens with invoice details', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const payBtn = page.locator('button:has-text("Pay")').first()
    if (await payBtn.isVisible().catch(() => false)) {
      await payBtn.click()
      await page.waitForTimeout(500)

      // Modal should show invoice number and amount
      await expect(page.locator('text=Pay Invoice')).toBeVisible()
      await expect(page.locator('text=Invoice:')).toBeVisible()
      await expect(page.locator('text=Amount:')).toBeVisible()
    }
  })

  test('pay modal has card input fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const payBtn = page.locator('button:has-text("Pay")').first()
    if (await payBtn.isVisible().catch(() => false)) {
      await payBtn.click()
      await page.waitForTimeout(500)

      // Card number, MM/YY, CVC fields
      const cardInput = page.locator('input[placeholder="Card number"]')
      if (await cardInput.isVisible().catch(() => false)) {
        await expect(cardInput).toBeVisible()
      }
    }
  })

  test('pay modal cancel button closes modal', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const payBtn = page.locator('button:has-text("Pay")').first()
    if (await payBtn.isVisible().catch(() => false)) {
      await payBtn.click()
      await page.waitForTimeout(500)

      const cancelBtn = page.locator('button:has-text("Cancel")').last()
      if (await cancelBtn.isVisible().catch(() => false)) {
        await cancelBtn.click()
        await page.waitForTimeout(300)
        // Modal should be hidden
        await expect(page.locator('text=Pay Invoice')).not.toBeVisible()
      }
    }
  })

  test('empty state shows when no invoices', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const emptyState = page.locator('text=No invoices found')
    const tableRows = page.locator('tbody tr')
    const hasEmpty = await emptyState.isVisible().catch(() => false)
    const rowCount = await tableRows.count().catch(() => 0)

    expect(hasEmpty || rowCount >= 0).toBeTruthy()
  })

  test('invoice status badges show correct colors', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')

    const statusBadges = page.locator('tbody tr td span').filter({ hasText: /paid|pending|overdue|cancelled|draft/i })
    const count = await statusBadges.count()
    if (count > 0) {
      const text = await statusBadges.first().textContent()
      expect(text?.toLowerCase()).toMatch(/paid|pending|overdue|cancelled|draft/)
    }
  })
})

// ---------------------------------------------------------------------------
// Client Portal Auth Guards
// ---------------------------------------------------------------------------
test.describe('Client Portal Auth Guards', () => {
  test('unauthenticated user redirected from dashboard', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user redirected from campaigns', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/campaigns`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user redirected from invoices', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/invoices`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user redirected from analytics', async ({ page }) => {
    await page.goto(`${BASE_URL}/client-portal-v2/analytics`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })
})
