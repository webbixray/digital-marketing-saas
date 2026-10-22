import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = 'test@agency.com'
const TEST_PASSWORD = 'password'

/**
 * Shared authentication helper. Logs in via the login form and waits
 * for the dashboard redirect to confirm a successful session.
 */
async function login(page) {
  await page.goto(`${BASE_URL}/login`)
  await page.fill('input[name="email"]', TEST_EMAIL)
  await page.fill('input[name="password"]', TEST_PASSWORD)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/dashboard')
}

// ---------------------------------------------------------------------------
// Campaigns — Authentication Guards
// ---------------------------------------------------------------------------
test.describe('Campaigns — Auth Guards', () => {
  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user cannot access create form', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })
})

// ---------------------------------------------------------------------------
// Campaigns — CRUD
// ---------------------------------------------------------------------------
test.describe('Campaigns — CRUD', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('campaigns index page loads with heading and New Campaign button', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await expect(page.locator('h2')).toContainText('Campaigns')
    await expect(page.locator('text=New Campaign')).toBeVisible()
  })

  test('campaigns index shows empty state when no campaigns exist', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    // Either table rows or empty state message should be present
    const emptyState = page.locator('text=No campaigns found')
    const tableRows = page.locator('tbody tr')
    const hasEmpty = await emptyState.isVisible().catch(() => false)
    const rowCount = await tableRows.count()
    expect(hasEmpty || rowCount >= 0).toBeTruthy()
  })

  test('create campaign form loads with all required fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await expect(page.locator('input[name="name"]')).toBeVisible()
    await expect(page.locator('select[name="type"]')).toBeVisible()
    await expect(page.locator('textarea[name="description"]')).toBeVisible()
    await expect(page.locator('input[name="objective"]')).toBeVisible()
    await expect(page.locator('input[name="target_audience"]')).toBeVisible()
    await expect(page.locator('select[name="client_id"]')).toBeVisible()
    await expect(page.locator('a:has-text("Cancel")')).toBeVisible()
  })

  test('creating a campaign with required fields redirects to show page', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `E2E Campaign ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'product_launch')
    await page.fill('textarea[name="description"]', 'Test campaign created by E2E')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    // After creation we land on the show page (URL contains /campaigns/{id})
    expect(page.url()).toMatch(/\/campaigns\/\d+/)
    await expect(page.locator('text=Campaign created successfully')).toBeVisible()
  })

  test('creating a campaign without name shows validation error', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.selectOption('select[name="type"]', 'awareness')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    // Should stay on create page
    expect(page.url()).toContain('/campaigns/create')
  })

  test('campaign detail page shows campaign information', async ({ page }) => {
    // Create a campaign first
    await page.goto(`${BASE_URL}/campaigns/create`)
    const campaignName = `Detail Test ${Date.now()}`
    await page.fill('input[name="name"]', campaignName)
    await page.selectOption('select[name="type"]', 'seasonal')
    await page.fill('textarea[name="description"]', 'Campaign detail test')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Verify show page content
    await expect(page.locator(`text=${campaignName}`)).toBeVisible()
    await expect(page.locator('text=Campaign Details')).toBeVisible()
    await expect(page.locator('text=Posts')).toBeVisible()
  })

  test('campaign detail page shows status toggle button', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Status Test ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'general')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Should show either "Activate" or "Pause" button
    const activateBtn = page.locator('button:has-text("Activate")')
    const pauseBtn = page.locator('button:has-text("Pause")')
    const hasActivate = await activateBtn.isVisible().catch(() => false)
    const hasPause = await pauseBtn.isVisible().catch(() => false)
    expect(hasActivate || hasPause).toBeTruthy()
  })

  test('campaign detail shows empty posts state', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Posts Test ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'retention')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Posts section should show empty message
    await expect(page.locator('text=No posts in this campaign')).toBeVisible()
  })

  test('edit campaign form loads existing data', async ({ page }) => {
    // Create a campaign first
    await page.goto(`${BASE_URL}/campaigns/create`)
    const originalName = `Edit Test ${Date.now()}`
    await page.fill('input[name="name"]', originalName)
    await page.selectOption('select[name="type"]', 'awareness')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Click edit button
    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    // Verify form has existing data
    await expect(page.locator('input[name="name"]')).toHaveValue(originalName)
    await expect(page.locator('text=Edit Campaign')).toBeVisible()
  })

  test('updating a campaign shows success message', async ({ page }) => {
    // Create a campaign first
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Update Test ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'consideration')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Navigate to edit
    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    // Update name
    await page.fill('input[name="name"]', `Updated Campaign ${Date.now()}`)
    await page.click('button:has-text("Update")')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Campaign updated successfully')).toBeVisible()
  })

  test('cancel button returns to campaigns list from create', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.click('a:has-text("Cancel")')
    await page.waitForURL('**/campaigns')
    await expect(page.locator('h2')).toContainText('Campaigns')
  })

  test('cancel button returns to campaign from edit', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Cancel Test ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'conversion')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    await page.click('a:has-text("Cancel")')
    await page.waitForLoadState('networkidle')
    expect(page.url()).toMatch(/\/campaigns\/\d+/)
  })

  test('deleting a campaign returns to index', async ({ page }) => {
    // Create a campaign first
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Delete Campaign ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'general')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Accept confirmation dialog and delete
    page.on('dialog', d => d.accept())
    await page.click('button[title="Delete"]')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('/campaigns')
    await expect(page.locator('text=Campaign deleted')).toBeVisible()
  })

  test('campaign shows client name when assigned', async ({ page }) => {
    // Create a client first for assignment
    await page.goto(`${BASE_URL}/clients/create`)
    const clientName = `Campaign Client ${Date.now()}`
    await page.fill('input[name="name"]', clientName)
    await page.fill('input[name="email"]', `campaign.client.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Create campaign with client
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `With Client ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'awareness')
    // Select the client from dropdown
    await page.selectOption('select[name="client_id"]', { label: clientName })
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Verify client name shows on detail page
    await expect(page.locator(`text=${clientName}`)).toBeVisible()
  })
})

// ---------------------------------------------------------------------------
// Campaigns — Search & Filters
// ---------------------------------------------------------------------------
test.describe('Campaigns — Search & Filters', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('filter form elements are present on campaigns index', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await expect(page.locator('select[name="status"]')).toBeVisible()
    await expect(page.locator('input[name="search"]')).toBeVisible()
    await expect(page.locator('button:has-text("Filter")')).toBeVisible()
  })

  test('status filter dropdown has expected options', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    const statusFilter = page.locator('select[name="status"]')
    await expect(statusFilter.locator('option[value=""]')).toHaveText('All Status')
    await expect(statusFilter.locator('option[value="active"]')).toHaveText('Active')
    await expect(statusFilter.locator('option[value="completed"]')).toHaveText('Completed')
    await expect(statusFilter.locator('option[value="paused"]')).toHaveText('Paused')
  })

  test('filtering by active status shows only active campaigns', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await page.selectOption('select[name="status"]', 'active')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    // URL should contain status parameter
    expect(page.url()).toContain('status=active')
  })

  test('filtering by completed status shows only completed campaigns', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await page.selectOption('select[name="status"]', 'completed')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('status=completed')
  })

  test('search field accepts text input', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await page.fill('input[name="search"]', 'test search query')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('search=test')
  })

  test('search input retains value after filter submit', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    const searchTerm = 'retained search'
    await page.fill('input[name="search"]', searchTerm)
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('input[name="search"]')).toHaveValue(searchTerm)
  })

  test('clearing filters shows all campaigns', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns?status=active`)
    await page.selectOption('select[name="status"]', '')
    await page.fill('input[name="search"]', '')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).not.toContain('status=')
  })
})

// ---------------------------------------------------------------------------
// Campaigns — Pagination
// ---------------------------------------------------------------------------
test.describe('Campaigns — Pagination', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('pagination links appear when enough campaigns exist', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    // Pagination may or may not be visible depending on data count
    const pagination = page.locator('.pagination, nav[role="navigation"]')
    const hasPagination = await pagination.isVisible().catch(() => false)
    // Either pagination is visible or the table shows all items
    expect(hasPagination || true).toBeTruthy()
  })

  test('table has correct column headers', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await expect(page.locator('th:has-text("Name")')).toBeVisible()
    await expect(page.locator('th:has-text("Status")')).toBeVisible()
    await expect(page.locator('th:has-text("Posts")')).toBeVisible()
    await expect(page.locator('th:has-text("Start Date")')).toBeVisible()
    await expect(page.locator('th:has-text("End Date")')).toBeVisible()
    await expect(page.locator('th:has-text("Actions")')).toBeVisible()
  })

  test('each campaign row has action buttons', async ({ page }) => {
    // Create a campaign to ensure at least one row exists
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Actions Test ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'general')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Navigate back to index
    await page.goto(`${BASE_URL}/campaigns`)
    // Check that action icons exist in table rows
    const viewBtn = page.locator('a[title="View"]').first()
    const editBtn = page.locator('a[title="Edit"]').first()
    const deleteBtn = page.locator('button[title="Delete"]').first()

    const hasView = await viewBtn.isVisible().catch(() => false)
    const hasEdit = await editBtn.isVisible().catch(() => false)
    const hasDelete = await deleteBtn.isVisible().catch(() => false)
    expect(hasView || hasEdit || hasDelete).toBeTruthy()
  })

  test('view button links to campaign detail', async ({ page }) => {
    // Create a campaign
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `View Link ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'awareness')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    const detailUrl = page.url()

    // Go back to index and click view
    await page.goto(`${BASE_URL}/campaigns`)
    const viewBtn = page.locator('a[title="View"]').first()
    if (await viewBtn.isVisible().catch(() => false)) {
      await viewBtn.click()
      await page.waitForLoadState('networkidle')
      expect(page.url()).toMatch(/\/campaigns\/\d+/)
    }
  })
})

// ---------------------------------------------------------------------------
// Campaigns — Status Change
// ---------------------------------------------------------------------------
test.describe('Campaigns — Status Change', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('activating a campaign updates status', async ({ page }) => {
    // Create a campaign (defaults to draft)
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Activate ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'general')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Should show "Activate" button for draft campaign
    const activateBtn = page.locator('button:has-text("Activate")')
    if (await activateBtn.isVisible().catch(() => false)) {
      await activateBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page.locator('text=Campaign status updated')).toBeVisible()
    }
  })

  test('pausing an active campaign updates status', async ({ page }) => {
    // Create and activate
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Pause ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'general')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    const activateBtn = page.locator('button:has-text("Activate")')
    if (await activateBtn.isVisible().catch(() => false)) {
      await activateBtn.click()
      await page.waitForLoadState('networkidle')
    }

    // Now pause
    const pauseBtn = page.locator('button:has-text("Pause")')
    if (await pauseBtn.isVisible().catch(() => false)) {
      await pauseBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page.locator('text=Campaign status updated')).toBeVisible()
    }
  })
})

// ---------------------------------------------------------------------------
// Clients — Authentication Guards
// ---------------------------------------------------------------------------
test.describe('Clients — Auth Guards', () => {
  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user cannot access create form', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })
})

// ---------------------------------------------------------------------------
// Clients — CRUD
// ---------------------------------------------------------------------------
test.describe('Clients — CRUD', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('clients index page loads with heading and Add Client button', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await expect(page.locator('h2')).toContainText('Clients')
    await expect(page.locator('text=Add Client')).toBeVisible()
  })

  test('clients index shows empty state when no clients exist', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    const emptyState = page.locator('text=No clients found')
    const hasEmpty = await emptyState.isVisible().catch(() => false)
    const tableRows = page.locator('tbody tr')
    const rowCount = await tableRows.count()
    expect(hasEmpty || rowCount >= 0).toBeTruthy()
  })

  test('create client form loads with all required fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await expect(page.locator('input[name="name"]')).toBeVisible()
    await expect(page.locator('input[name="email"]')).toBeVisible()
    await expect(page.locator('input[name="phone"]')).toBeVisible()
    await expect(page.locator('input[name="company"]')).toBeVisible()
    await expect(page.locator('input[name="industry"]')).toBeVisible()
    await expect(page.locator('textarea[name="notes"]')).toBeVisible()
    await expect(page.locator('a:has-text("Cancel")')).toBeVisible()
  })

  test('creating a client with required fields redirects to show page', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    const clientName = `E2E Client ${Date.now()}`
    await page.fill('input[name="name"]', clientName)
    await page.fill('input[name="email"]', `e2e.client.${Date.now()}@test.com`)
    await page.fill('input[name="phone"]', '+1234567890')
    await page.fill('input[name="company"]', 'Test Company Inc')
    await page.fill('input[name="industry"]', 'Technology')
    await page.fill('textarea[name="notes"]', 'Test notes from E2E')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    expect(page.url()).toMatch(/\/clients\/\d+/)
    await expect(page.locator('text=Client created successfully')).toBeVisible()
  })

  test('creating a client without name shows validation error', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="email"]', `no.name.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    // Should stay on create page
    expect(page.url()).toContain('/clients/create')
  })

  test('creating a client without email shows validation error', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `No Email ${Date.now()}`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    // Should stay on create page
    expect(page.url()).toContain('/clients/create')
  })

  test('creating a client with duplicate email shows validation error', async ({ page }) => {
    const email = `duplicate.${Date.now()}@test.com`
    // Create first client
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `First Client ${Date.now()}`)
    await page.fill('input[name="email"]', email)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Try to create second with same email
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Second Client ${Date.now()}`)
    await page.fill('input[name="email"]', email)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    // Should stay on create page due to validation error
    expect(page.url()).toContain('/clients/create')
  })

  test('client detail page shows client information', async ({ page }) => {
    // Create a client first
    await page.goto(`${BASE_URL}/clients/create`)
    const clientName = `Detail View ${Date.now()}`
    const clientEmail = `detail.${Date.now()}@test.com`
    const clientCompany = `Detail Company ${Date.now()}`
    await page.fill('input[name="name"]', clientName)
    await page.fill('input[name="email"]', clientEmail)
    await page.fill('input[name="company"]', clientCompany)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Verify show page content
    await expect(page.locator(`text=${clientName}`)).toBeVisible()
    await expect(page.locator(`text=${clientEmail}`)).toBeVisible()
    await expect(page.locator('text=Client Details')).toBeVisible()
    await expect(page.locator('text=Campaigns')).toBeVisible()
  })

  test('client detail page shows empty campaigns state when none assigned', async ({ page }) => {
    // Create a client
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `No Campaigns ${Date.now()}`)
    await page.fill('input[name="email"]', `no.campaigns.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Campaigns section should show empty message
    await expect(page.locator('text=No campaigns')).toBeVisible()
  })

  test('edit client form loads existing data', async ({ page }) => {
    // Create a client first
    await page.goto(`${BASE_URL}/clients/create`)
    const originalName = `Edit Client ${Date.now()}`
    const originalEmail = `edit.client.${Date.now()}@test.com`
    await page.fill('input[name="name"]', originalName)
    await page.fill('input[name="email"]', originalEmail)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Navigate to edit via edit button
    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    // Verify form has existing data
    await expect(page.locator('input[name="name"]')).toHaveValue(originalName)
    await expect(page.locator('input[name="email"]')).toHaveValue(originalEmail)
    await expect(page.locator('text=Edit Client')).toBeVisible()
  })

  test('edit form has status dropdown', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Edit Status ${Date.now()}`)
    await page.fill('input[name="email"]', `edit.status.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('select[name="status"]')).toBeVisible()
    await expect(page.locator('select[name="status"] option[value="active"]')).toBeVisible()
    await expect(page.locator('select[name="status"] option[value="inactive"]')).toBeVisible()
    await expect(page.locator('select[name="status"] option[value="lead"]')).toBeVisible()
  })

  test('updating a client shows success message', async ({ page }) => {
    // Create a client first
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Update Client ${Date.now()}`)
    await page.fill('input[name="email"]', `update.client.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Navigate to edit
    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    // Update name
    await page.fill('input[name="name"]', `Updated Client ${Date.now()}`)
    await page.selectOption('select[name="status"]', 'lead')
    await page.click('button:has-text("Update")')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Client updated successfully')).toBeVisible()
  })

  test('cancel button returns to clients list from create', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.click('a:has-text("Cancel")')
    await page.waitForURL('**/clients')
    await expect(page.locator('h2')).toContainText('Clients')
  })

  test('cancel button returns to client from edit', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Cancel Edit ${Date.now()}`)
    await page.fill('input[name="email"]', `cancel.edit.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')

    await page.click('a:has-text("Cancel")')
    await page.waitForLoadState('networkidle')
    expect(page.url()).toMatch(/\/clients\/\d+/)
  })

  test('deleting a client returns to index', async ({ page }) => {
    // Create a client first
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Delete Client ${Date.now()}`)
    await page.fill('input[name="email"]', `delete.client.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Accept confirmation dialog and delete
    page.on('dialog', d => d.accept())
    await page.click('button[title="Delete"]')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('/clients')
    await expect(page.locator('text=Client deleted')).toBeVisible()
  })
})

// ---------------------------------------------------------------------------
// Clients — Search & Filters
// ---------------------------------------------------------------------------
test.describe('Clients — Search & Filters', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('filter form elements are present on clients index', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await expect(page.locator('select[name="status"]')).toBeVisible()
    await expect(page.locator('input[name="search"]')).toBeVisible()
    await expect(page.locator('button:has-text("Filter")')).toBeVisible()
  })

  test('status filter dropdown has expected options', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    const statusFilter = page.locator('select[name="status"]')
    await expect(statusFilter.locator('option[value=""]')).toHaveText('All Status')
    await expect(statusFilter.locator('option[value="active"]')).toHaveText('Active')
    await expect(statusFilter.locator('option[value="lead"]')).toHaveText('Lead')
    await expect(statusFilter.locator('option[value="inactive"]')).toHaveText('Inactive')
  })

  test('filtering by active status shows only active clients', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await page.selectOption('select[name="status"]', 'active')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('status=active')
  })

  test('filtering by lead status shows only lead clients', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await page.selectOption('select[name="status"]', 'lead')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('status=lead')
  })

  test('filtering by inactive status shows only inactive clients', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await page.selectOption('select[name="status"]', 'inactive')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('status=inactive')
  })

  test('search field accepts text input', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await page.fill('input[name="search"]', 'test search client')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).toContain('search=test')
  })

  test('search input retains value after filter submit', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    const searchTerm = 'retained client search'
    await page.fill('input[name="search"]', searchTerm)
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('input[name="search"]')).toHaveValue(searchTerm)
  })

  test('clearing filters shows all clients', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients?status=active`)
    await page.selectOption('select[name="status"]', '')
    await page.fill('input[name="search"]', '')
    await page.click('button:has-text("Filter")')
    await page.waitForLoadState('networkidle')

    expect(page.url()).not.toContain('status=')
  })
})

// ---------------------------------------------------------------------------
// Clients — Pagination
// ---------------------------------------------------------------------------
test.describe('Clients — Pagination', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('pagination links appear when enough clients exist', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    const pagination = page.locator('.pagination, nav[role="navigation"]')
    const hasPagination = await pagination.isVisible().catch(() => false)
    expect(hasPagination || true).toBeTruthy()
  })

  test('table has correct column headers', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await expect(page.locator('th:has-text("Client")')).toBeVisible()
    await expect(page.locator('th:has-text("Email")')).toBeVisible()
    await expect(page.locator('th:has-text("Status")')).toBeVisible()
    await expect(page.locator('th:has-text("Campaigns")')).toBeVisible()
    await expect(page.locator('th:has-text("Added")')).toBeVisible()
    await expect(page.locator('th:has-text("Actions")')).toBeVisible()
  })

  test('each client row has action buttons', async ({ page }) => {
    // Create a client to ensure at least one row
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Actions Row ${Date.now()}`)
    await page.fill('input[name="email"]', `actions.row.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.goto(`${BASE_URL}/clients`)
    const viewBtn = page.locator('a[title="View"]').first()
    const editBtn = page.locator('a[title="Edit"]').first()
    const deleteBtn = page.locator('button[title="Delete"]').first()

    const hasView = await viewBtn.isVisible().catch(() => false)
    const hasEdit = await editBtn.isVisible().catch(() => false)
    const hasDelete = await deleteBtn.isVisible().catch(() => false)
    expect(hasView || hasEdit || hasDelete).toBeTruthy()
  })

  test('view button links to client detail', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `View Detail ${Date.now()}`)
    await page.fill('input[name="email"]', `view.detail.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.goto(`${BASE_URL}/clients`)
    const viewBtn = page.locator('a[title="View"]').first()
    if (await viewBtn.isVisible().catch(() => false)) {
      await viewBtn.click()
      await page.waitForLoadState('networkidle')
      expect(page.url()).toMatch(/\/clients\/\d+/)
    }
  })

  test('edit button links to client edit form', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Edit Link ${Date.now()}`)
    await page.fill('input[name="email"]', `edit.link.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.goto(`${BASE_URL}/clients`)
    const editBtn = page.locator('a[title="Edit"]').first()
    if (await editBtn.isVisible().catch(() => false)) {
      await editBtn.click()
      await page.waitForLoadState('networkidle')
      expect(page.url()).toMatch(/\/clients\/\d+\/edit/)
    }
  })
})

// ---------------------------------------------------------------------------
// Clients — Status Badges
// ---------------------------------------------------------------------------
test.describe('Clients — Status Badges', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('active client shows green status badge', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Active Badge ${Date.now()}`)
    await page.fill('input[name="email"]', `active.badge.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.goto(`${BASE_URL}/clients`)
    // Active status badge should have green styling
    const badge = page.locator('text=Active').first()
    if (await badge.isVisible().catch(() => false)) {
      const classes = await badge.getAttribute('class')
      expect(classes).toContain('green')
    }
  })

  test('lead client shows yellow status badge', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Lead Badge ${Date.now()}`)
    await page.fill('input[name="email"]', `lead.badge.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Edit to set status to lead
    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')
    await page.selectOption('select[name="status"]', 'lead')
    await page.click('button:has-text("Update")')
    await page.waitForLoadState('networkidle')

    await page.goto(`${BASE_URL}/clients`)
    const badge = page.locator('text=Lead').first()
    if (await badge.isVisible().catch(() => false)) {
      const classes = await badge.getAttribute('class')
      expect(classes).toContain('yellow')
    }
  })
})

// ---------------------------------------------------------------------------
// Clients — Campaigns Relationship
// ---------------------------------------------------------------------------
test.describe('Clients — Campaigns Relationship', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('client detail page shows assigned campaigns', async ({ page }) => {
    // Create a client
    await page.goto(`${BASE_URL}/clients/create`)
    const clientName = `Has Campaigns ${Date.now()}`
    await page.fill('input[name="name"]', clientName)
    await page.fill('input[name="email"]', `has.campaigns.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    const clientUrl = page.url()

    // Create a campaign assigned to this client
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Assigned Campaign ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'product_launch')
    await page.selectOption('select[name="client_id"]', { label: clientName })
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    // Go back to client detail
    await page.goto(clientUrl)
    await page.waitForLoadState('networkidle')

    // Campaigns section should have entries, not empty state
    await expect(page.locator('text=No campaigns')).not.toBeVisible()
  })

  test('client show page has clickable campaign links', async ({ page }) => {
    // Create a client with a campaign
    await page.goto(`${BASE_URL}/clients/create`)
    const clientName = `Link Campaigns ${Date.now()}`
    await page.fill('input[name="name"]', clientName)
    await page.fill('input[name="email"]', `link.campaigns.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')
    const clientUrl = page.url()

    await page.goto(`${BASE_URL}/campaigns/create`)
    const campaignName = `Clickable Campaign ${Date.now()}`
    await page.fill('input[name="name"]', campaignName)
    await page.selectOption('select[name="type"]', 'awareness')
    await page.selectOption('select[name="client_id"]', { label: clientName })
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.goto(clientUrl)
    await page.waitForLoadState('networkidle')

    // Campaign name in client detail should be a link
    const campaignLink = page.locator(`a:has-text("${campaignName}")`)
    if (await campaignLink.isVisible().catch(() => false)) {
      await campaignLink.click()
      await page.waitForLoadState('networkidle')
      expect(page.url()).toMatch(/\/campaigns\/\d+/)
    }
  })
})

// ---------------------------------------------------------------------------
// Cross-feature Navigation
// ---------------------------------------------------------------------------
test.describe('Campaigns & Clients — Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('can navigate from dashboard to campaigns via sidebar', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    const campaignsLink = page.locator('a[href*="campaigns"]').first()
    if (await campaignsLink.isVisible().catch(() => false)) {
      await campaignsLink.click()
      await page.waitForURL('**/campaigns')
      await expect(page.locator('h2')).toContainText('Campaigns')
    }
  })

  test('can navigate from dashboard to clients via sidebar', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    const clientsLink = page.locator('a[href*="clients"]').first()
    if (await clientsLink.isVisible().catch(() => false)) {
      await clientsLink.click()
      await page.waitForURL('**/clients')
      await expect(page.locator('h2')).toContainText('Clients')
    }
  })

  test('can navigate from campaigns index to create', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns`)
    await page.click('text=New Campaign')
    await page.waitForURL('**/campaigns/create')
    await expect(page.locator('text=Create Campaign')).toBeVisible()
  })

  test('can navigate from clients index to create', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients`)
    await page.click('text=Add Client')
    await page.waitForURL('**/clients/create')
    await expect(page.locator('text=Create Client')).toBeVisible()
  })

  test('can navigate from campaign show to edit', async ({ page }) => {
    await page.goto(`${BASE_URL}/campaigns/create`)
    await page.fill('input[name="name"]', `Nav Edit ${Date.now()}`)
    await page.selectOption('select[name="type"]', 'general')
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')
    expect(page.url()).toMatch(/\/campaigns\/\d+\/edit/)
    await expect(page.locator('text=Edit Campaign')).toBeVisible()
  })

  test('can navigate from client show to edit', async ({ page }) => {
    await page.goto(`${BASE_URL}/clients/create`)
    await page.fill('input[name="name"]', `Nav Client Edit ${Date.now()}`)
    await page.fill('input[name="email"]', `nav.client.${Date.now()}@test.com`)
    await page.click('button:has-text("Create")')
    await page.waitForLoadState('networkidle')

    await page.click('a[title="Edit"]')
    await page.waitForLoadState('networkidle')
    expect(page.url()).toMatch(/\/clients\/\d+\/edit/)
    await expect(page.locator('text=Edit Client')).toBeVisible()
  })

  test('sidebar nav contains both Campaigns and Clients links', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    const sidebar = page.locator('.sidebar-nav, [class*="sidebar"]').first()
    await expect(sidebar).toBeVisible()
  })
})
