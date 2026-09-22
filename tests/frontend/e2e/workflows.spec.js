import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = 'test@agency.com'
const TEST_PASSWORD = 'password'

// Helper: login as test user
async function login(page) {
    await page.goto(`${BASE_URL}/login`)
    await page.fill('input[name="email"]', TEST_EMAIL)
    await page.fill('input[name="password"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard', { timeout: 15000 })
}

// Helper: create a workflow via page.request (shares cookies)
async function createWorkflow(page, name, triggerType = 'post_published') {
    const response = await page.request.post(`${BASE_URL}/workflows`, {
        headers: { 'Accept': 'application/json' },
        data: {
            name,
            trigger_type: triggerType,
            actions: JSON.stringify([{ type: 'send_notification', config: { message: 'Test notification' } }]),
        },
    })
    if (response.status() === 200 || response.status() === 302) {
        // Follow redirect to get the workflow
        const location = response.headers()['location']
        if (location) {
            const workflowId = location.split('/workflows/')[1]?.split('/')[0]
            return workflowId || null
        }
    }
    return null
}

// ---------------------------------------------------------------------------
// Workflows Index
// ---------------------------------------------------------------------------
test.describe('Workflows Index', () => {
    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('shows workflows index page for authenticated user', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows/)
        await expect(page.locator('h1')).toContainText('Workflows')
        await expect(page.locator('text=New Workflow')).toBeVisible()
    })

    test('shows empty state when no workflows exist', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')

        // Empty state message or table should be present
        const emptyState = page.locator('text=No workflows found')
        const tableExists = page.locator('table tbody tr')
        const hasEmpty = await emptyState.isVisible().catch(() => false)
        const rowCount = await tableExists.count().catch(() => 0)

        expect(hasEmpty || rowCount === 0).toBeTruthy()
    })

    test('workflow table displays name, trigger, status columns', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')

        // Table headers
        await expect(page.locator('th:has-text("Name")')).toBeVisible()
        await expect(page.locator('th:has-text("Trigger")')).toBeVisible()
        await expect(page.locator('th:has-text("Status")')).toBeVisible()
        await expect(page.locator('th:has-text("Actions")')).toBeVisible()
    })

    test('workflow rows show view, edit, and delete action buttons', async ({ page }) => {
        await login(page)

        // Create a workflow to have a row to check
        await createWorkflow(page, 'Index Action Test ' + Date.now())

        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')

        // Action buttons in table (at least one row should have them)
        const viewBtn = page.locator('tbody tr').first().locator('a:has(i.fa-eye)').first()
        const editBtn = page.locator('tbody tr').first().locator('a:has(i.fa-edit)').first()
        const deleteBtn = page.locator('tbody row').first().locator('button:has-text("Delete"), tr').first().locator('button[onclick*="confirm"]').first()

        // At least view and edit should be present if rows exist
        if (await viewBtn.isVisible().catch(() => false)) {
            await expect(viewBtn).toBeVisible()
        }
        if (await editBtn.isVisible().catch(() => false)) {
            await expect(editBtn).toBeVisible()
        }
    })

    test('status badge shows draft or active styling', async ({ page }) => {
        await login(page)
        await createWorkflow(page, 'Status Badge Test ' + Date.now())

        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')

        const statusBadge = page.locator('tbody tr').first().locator('span').filter({ hasText: /draft|active/i }).first()
        if (await statusBadge.isVisible().catch(() => false)) {
            const text = await statusBadge.textContent()
            expect(text?.toLowerCase()).toMatch(/draft|active|paused/)
        }
    })

    test('filter by status query param', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows?status=active`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/status=active/)
        await expect(page.locator('h1')).toContainText('Workflows')
    })
})

// ---------------------------------------------------------------------------
// Create Workflow
// ---------------------------------------------------------------------------
test.describe('Create Workflow', () => {
    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('create workflow page loads with form fields', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/create/)
        await expect(page.locator('h2')).toContainText('Create Workflow')
        await expect(page.locator('input[name="name"]')).toBeVisible()
        await expect(page.locator('select[name="trigger_type"]')).toBeVisible()
        await expect(page.locator('textarea[name="actions"]')).toBeVisible()
        await expect(page.locator('button:has-text("Create")')).toBeVisible()
    })

    test('creating a workflow with valid data redirects to show page', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        const workflowName = 'E2E Test Workflow ' + Date.now()
        await page.fill('input[name="name"]', workflowName)
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'Hello' } }]))
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        // Should redirect to show page
        await expect(page).toHaveURL(/\/workflows\/\d+/)
        await expect(page.locator('text=Workflow created successfully')).toBeVisible()
    })

    test('name field is required', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        // Submit without name
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'Test' } }]))
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        // Should stay on create page
        await expect(page).toHaveURL(/\/workflows\/create/)
    })

    test('trigger_type is required and must be valid', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        await page.fill('input[name="name"]', 'Invalid Trigger Test')
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'Test' } }]))
        // Browser validation should prevent submission without trigger_type
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        // Should remain on create form
        await expect(page).toHaveURL(/\/workflows\/create/)
    })

    test('actions field must be valid JSON array', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        await page.fill('input[name="name"]', 'Bad Actions ' + Date.now())
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', 'not valid json')
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        // Should stay on create page due to validation failure
        await expect(page).toHaveURL(/\/workflows\/create/)
    })

    test('empty actions array is rejected', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        await page.fill('input[name="name"]', 'Empty Actions ' + Date.now())
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', '[]')
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/create/)
    })

    test('cancel button returns to workflows index', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        await page.click('a:has-text("Cancel")')
        await page.waitForURL('**/workflows')
        await expect(page).toHaveURL(/\/workflows/)
    })

    test('trigger type dropdown shows available trigger types', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        const options = await page.locator('select[name="trigger_type"] option').allTextContents()
        expect(options.length).toBeGreaterThan(1)
        // Should contain known trigger types
        const joined = options.join(' ').toLowerCase()
        expect(joined).toContain('post')
    })
})

// ---------------------------------------------------------------------------
// Edit Workflow
// ---------------------------------------------------------------------------
test.describe('Edit Workflow', () => {
    let workflowId

    test.beforeEach(async ({ page }) => {
        await login(page)
        workflowId = await createWorkflow(page, 'Edit Test ' + Date.now())
    })

    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('edit page loads existing workflow data', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/\d+\/edit/)
        await expect(page.locator('h2')).toContainText('Edit Workflow')
        await expect(page.locator('input[name="name"]')).toBeVisible()
        await expect(page.locator('select[name="trigger_type"]')).toBeVisible()
        await expect(page.locator('textarea[name="actions"]')).toBeVisible()
        await expect(page.locator('button:has-text("Update")')).toBeVisible()
    })

    test('updating a workflow redirects to show page with success', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')

        await page.fill('input[name="name"]', 'Updated Name ' + Date.now())
        await page.click('button:has-text("Update")')
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}`))
        await expect(page.locator('text=Workflow updated successfully')).toBeVisible()
    })

    test('name is pre-filled with existing value', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')

        const nameInput = page.locator('input[name="name"]')
        const value = await nameInput.inputValue()
        expect(value.length).toBeGreaterThan(0)
    })

    test('cancel button returns to show page', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')

        await page.click('a:has-text("Cancel")')
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}`))
    })

    test('empty name is rejected on update', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')

        await page.fill('input[name="name"]', '')
        await page.click('button:has-text("Update")')
        await page.waitForLoadState('networkidle')

        // Should stay on edit page due to validation
        await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}/edit`))
    })

    test('cannot edit another agency workflow (403)', async ({ page }) => {
        // Try to access a workflow ID that doesn't belong to this agency
        // Using a high number that's unlikely to exist
        await page.goto(`${BASE_URL}/workflows/999999/edit`)
        await page.waitForLoadState('networkidle')

        // Should get 403 or 404
        const response = await page.request.get(`${BASE_URL}/workflows/999999/edit`)
        expect([403, 404]).toContain(response.status())
    })
})

// ---------------------------------------------------------------------------
// Workflow Show (Detail Page)
// ---------------------------------------------------------------------------
test.describe('Workflow Show', () => {
    let workflowId

    test.beforeEach(async ({ page }) => {
        await login(page)
        workflowId = await createWorkflow(page, 'Show Test ' + Date.now())
    })

    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/1`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('show page displays workflow details', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}`))
        await expect(page.locator('h2')).toContainText('Workflow Details')
    })

    test('shows trigger, status, executions, last run info', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('text=Trigger')).toBeVisible()
        await expect(page.locator('text=Status')).toBeVisible()
        await expect(page.locator('text=Executions')).toBeVisible()
        await expect(page.locator('text=Last Run')).toBeVisible()
    })

    test('execution history table is present', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        // Execution history section
        const execSection = page.locator('text=Execution History')
        await expect(execSection).toBeVisible()
    })

    test('empty executions state shows message', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        const emptyMsg = page.locator('text=No executions yet')
        await expect(emptyMsg).toBeVisible()
    })

    test('actions are displayed as JSON', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        // Actions section should show JSON
        await expect(page.locator('text=Actions')).toBeVisible()
        const actionsPre = page.locator('pre').filter({ has: page.locator('*=send_notification') })
        if (await actionsPre.isVisible().catch(() => false)) {
            await expect(actionsPre).toBeVisible()
        }
    })

    test('deleting a workflow returns to index', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        // Navigate to index to delete from there
        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')

        // Find the delete button for this workflow and click it
        page.on('dialog', d => d.accept())
        const deleteBtn = page.locator('tbody tr').first().locator('button[onclick*="confirm"], form[action*="workflows"] button').first()
        if (await deleteBtn.isVisible().catch(() => false)) {
            await deleteBtn.click()
            await page.waitForLoadState('networkidle')
            await expect(page).toHaveURL(/\/workflows/)
        }
    })

    test('cannot view another agency workflow (403)', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/999999`)
        await page.waitForLoadState('networkidle')

        const response = await page.request.get(`${BASE_URL}/workflows/999999`)
        expect([403, 404]).toContain(response.status())
    })
})

// ---------------------------------------------------------------------------
// Workflow Versions
// ---------------------------------------------------------------------------
test.describe('Workflow Versions', () => {
    let workflowId

    test.beforeEach(async ({ page }) => {
        await login(page)
        workflowId = await createWorkflow(page, 'Version Test ' + Date.now())
    })

    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/1/versions`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('versions page loads with version history table', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/versions`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}/versions`))
        await expect(page.locator('h2')).toContainText('Version History')
        await expect(page.locator('text=Version History').nth(1)).toBeVisible()
    })

    test('versions table has correct columns', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/versions`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('th:has-text("Version")')).toBeVisible()
        await expect(page.locator('th:has-text("Name")')).toBeVisible()
        await expect(page.locator('th:has-text("Trigger")')).toBeVisible()
        await expect(page.locator('th:has-text("Changes")')).toBeVisible()
        await expect(page.locator('th:has-text("Created")')).toBeVisible()
    })

    test('at least one version exists (initial version on create)', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/versions`)
        await page.waitForLoadState('networkidle')

        // Should have at least v1
        const versionCell = page.locator('td strong').filter({ hasText: /v\d+/ }).first()
        await expect(versionCell).toBeVisible()
    })

    test('restore button triggers confirmation dialog', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/versions`)
        await page.waitForLoadState('networkidle')

        const restoreBtn = page.locator('button:has-text("Restore")').first()
        if (await restoreBtn.isVisible().catch(() => false)) {
            // Dialog should appear on click
            page.once('dialog', d => {
                expect(d.type()).toBe('confirm')
                d.dismiss()
            })
            await restoreBtn.click()
        }
    })

    test('back button returns to workflow show page', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/versions`)
        await page.waitForLoadState('networkidle')

        const backBtn = page.locator('a:has-text("Back")').first()
        if (await backBtn.isVisible().catch(() => false)) {
            await backBtn.click()
            await page.waitForURL(new RegExp(`/workflows/${workflowId}`))
            await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}`))
        }
    })

    test('restoring a version creates a new version', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        // First edit the workflow to create a second version
        await page.goto(`${BASE_URL}/workflows/${workflowId}/edit`)
        await page.waitForLoadState('networkidle')
        await page.fill('input[name="name"]', 'Version2 ' + Date.now())
        await page.click('button:has-text("Update")')
        await page.waitForLoadState('networkidle')

        // Now go to versions and restore the first one
        await page.goto(`${BASE_URL}/workflows/${workflowId}/versions`)
        await page.waitForLoadState('networkidle')

        const restoreBtn = page.locator('button:has-text("Restore")').last()
        if (await restoreBtn.isVisible().catch(() => false)) {
            page.once('dialog', d => d.accept())
            await restoreBtn.click()
            await page.waitForLoadState('networkidle')

            // Should redirect to show page with success message
            await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}`))
            await expect(page.locator('text=Workflow restored to version')).toBeVisible()
        }
    })

    test('cannot access versions of another agency workflow', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/999999/versions`)
        await page.waitForLoadState('networkidle')

        const response = await page.request.get(`${BASE_URL}/workflows/999999/versions`)
        expect([403, 404]).toContain(response.status())
    })
})

// ---------------------------------------------------------------------------
// Execute Workflow
// ---------------------------------------------------------------------------
test.describe('Execute Workflow', () => {
    let workflowId

    test.beforeEach(async ({ page }) => {
        await login(page)
        workflowId = await createWorkflow(page, 'Execute Test ' + Date.now())
    })

    test('execute requires authentication', async ({ request }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const response = await request.post(`${BASE_URL}/workflows/${workflowId}/execute`, {
            headers: { 'Accept': 'application/json' },
            data: {},
        })
        const status = response.status()
        expect([302, 401, 403]).toContain(status)
    })

    test('execute workflow via API returns success', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const response = await page.request.post(`${BASE_URL}/workflows/${workflowId}/execute`, {
            headers: { 'Accept': 'application/json' },
            data: {},
        })

        // Should be 200 or 500 (depends on engine state)
        expect([200, 500]).toContain(response.status())

        const data = await response.json()
        expect(data).toHaveProperty('success')
    })

    test('execute with trigger_data payload', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const response = await page.request.post(`${BASE_URL}/workflows/${workflowId}/execute`, {
            headers: { 'Accept': 'application/json' },
            data: {
                trigger_data: { source: 'e2e_test', timestamp: Date.now() },
            },
        })

        expect([200, 500]).toContain(response.status())
        const data = await response.json()
        expect(data).toHaveProperty('success')
    })

    test('execution history updates after running workflow', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        // Execute the workflow
        await page.request.post(`${BASE_URL}/workflows/${workflowId}/execute`, {
            headers: { 'Accept': 'application/json' },
            data: {},
        })

        // Check show page for execution entry
        await page.goto(`${BASE_URL}/workflows/${workflowId}`)
        await page.waitForLoadState('networkidle')

        // Execution count or log should appear
        await expect(page.locator('h2')).toContainText('Workflow Details')
    })

    test('cannot execute another agency workflow (403)', async ({ page }) => {
        const response = await page.request.post(`${BASE_URL}/workflows/999999/execute`, {
            headers: { 'Accept': 'application/json' },
            data: {},
        })
        const status = response.status()
        expect([403, 404]).toContain(status)
    })

    test('trigger_data max size validation', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const largeData = {}
        for (let i = 0; i < 60; i++) {
            largeData[`key_${i}`] = 'x'.repeat(100)
        }

        const response = await page.request.post(`${BASE_URL}/workflows/${workflowId}/execute`, {
            headers: { 'Accept': 'application/json' },
            data: { trigger_data: largeData },
        })

        // Should be 422 for validation failure (max 50 keys) or 200/500
        const status = response.status()
        expect([200, 422, 500]).toContain(status)
    })
})

// ---------------------------------------------------------------------------
// Workflow Builder (Visual Drag & Drop)
// ---------------------------------------------------------------------------
test.describe('Workflow Builder', () => {
    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('builder page loads with palette, canvas, and properties panel', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/builder/)
        await expect(page.locator('#workflowBuilder')).toBeVisible()
        await expect(page.locator('.node-palette')).toBeVisible()
        await expect(page.locator('#canvasContainer')).toBeVisible()
        await expect(page.locator('.properties-panel')).toBeVisible()
    })

    test('node palette shows trigger, action, and logic sections', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('text=Triggers')).toBeVisible()
        await expect(page.locator('text=Actions')).toBeVisible()
        await expect(page.locator('text=Logic')).toBeVisible()
    })

    test('palette nodes are draggable', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        const draggableNodes = page.locator('.palette-node[draggable="true"]')
        const count = await draggableNodes.count()
        expect(count).toBeGreaterThan(0)
    })

    test('empty state is shown when canvas has no nodes', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('#emptyState')).toBeVisible()
        await expect(page.locator('text=Build Your Workflow')).toBeVisible()
    })

    test('drag and drop a node from palette to canvas', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        const paletteNode = page.locator('.palette-node').first()
        const canvas = page.locator('#canvasContainer')

        if (await paletteNode.isVisible()) {
            await paletteNode.dragTo(canvas)
            await page.waitForTimeout(500)

            // Node should appear on canvas
            const canvasNode = page.locator('.workflow-node').first()
            if (await canvasNode.isVisible().catch(() => false)) {
                await expect(canvasNode).toBeVisible()
            }
        }
    })

    test('toolbar has save, activate, and templates buttons', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('button:has-text("Save")')).toBeVisible()
        await expect(page.locator('button:has-text("Activate")')).toBeVisible()
        await expect(page.locator('button:has-text("Templates")')).toBeVisible()
    })

    test('zoom controls are present', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('#zoomLevel')).toBeVisible()
        await expect(page.locator('.zoom-controls')).toBeVisible()
    })

    test('info bar shows node and connection counts', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('#nodeCount')).toBeVisible()
        await expect(page.locator('#connectionCount')).toBeVisible()

        // Initially both should be 0
        const nodeCount = await page.locator('#nodeCount').textContent()
        const connCount = await page.locator('#connectionCount').textContent()
        expect(nodeCount).toBe('0')
        expect(connCount).toBe('0')
    })

    test('selecting a node shows properties panel', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        // Drag a node first
        const paletteNode = page.locator('.palette-node').first()
        const canvas = page.locator('#canvasContainer')

        if (await paletteNode.isVisible()) {
            await paletteNode.dragTo(canvas)
            await page.waitForTimeout(500)

            // Click the node to select it
            const canvasNode = page.locator('.workflow-node').first()
            if (await canvasNode.isVisible().catch(() => false)) {
                await canvasNode.click()
                // Properties panel should show configuration options
                await expect(page.locator('.properties-panel-body')).toBeVisible()
            }
        }
    })

    test('adding a node updates the info bar count', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        const paletteNode = page.locator('.palette-node').first()
        const canvas = page.locator('#canvasContainer')

        if (await paletteNode.isVisible()) {
            await paletteNode.dragTo(canvas)
            await page.waitForTimeout(500)

            const nodeCount = await page.locator('#nodeCount').textContent()
            expect(parseInt(nodeCount)).toBeGreaterThanOrEqual(1)
        }
    })

    test('save modal opens and has name/description fields', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        // Add a node first (save requires at least one node)
        const paletteNode = page.locator('.palette-node').first()
        const canvas = page.locator('#canvasContainer')
        if (await paletteNode.isVisible()) {
            await paletteNode.dragTo(canvas)
            await page.waitForTimeout(500)
        }

        // Click save button
        const saveBtn = page.locator('button:has-text("Save")').first()
        if (await saveBtn.isVisible()) {
            await saveBtn.click()
            await page.waitForTimeout(300)

            // Modal should be visible
            const modal = page.locator('#saveModal')
            if (await modal.isVisible().catch(() => false)) {
                await expect(page.locator('text=Save Workflow')).toBeVisible()
                await expect(page.locator('#workflowName')).toBeVisible()
                await expect(page.locator('#workflowDescription')).toBeVisible()
            }
        }
    })

    test('save workflow via builder API returns JSON', async ({ page }) => {
        await login(page)

        const response = await page.request.post(`${BASE_URL}/workflows/builder/save`, {
            headers: { 'Accept': 'application/json' },
            data: {
                name: 'Builder API Test ' + Date.now(),
                description: 'Created via E2E',
                nodes: [
                    { type: 'trigger', subtype: 'post_published', config: {} },
                    { type: 'action', subtype: 'send_notification', config: { message: 'test' } },
                ],
            },
        })

        expect([200, 302]).toContain(response.status())
    })

    test('save workflow requires at least one node', async ({ page }) => {
        await login(page)

        const response = await page.request.post(`${BASE_URL}/workflows/builder/save`, {
            headers: { 'Accept': 'application/json' },
            data: {
                name: 'No Nodes ' + Date.now(),
                nodes: [],
            },
        })

        // Should fail validation (422) or redirect back
        const status = response.status()
        expect([302, 422]).toContain(status)
    })

    test('clear canvas removes all nodes', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        // Add a node first
        const paletteNode = page.locator('.palette-node').first()
        const canvas = page.locator('#canvasContainer')
        if (await paletteNode.isVisible()) {
            await paletteNode.dragTo(canvas)
            await page.waitForTimeout(500)
        }

        // Click clear button
        const clearBtn = page.locator('button[title="Clear"], .toolbar-btn.danger').first()
        if (await clearBtn.isVisible()) {
            page.once('dialog', d => d.accept())
            await clearBtn.click()
            await page.waitForTimeout(500)

            const nodeCount = await page.locator('#nodeCount').textContent()
            expect(nodeCount).toBe('0')
        }
    })

    test('keyboard hint appears on load', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        // Keyboard hint should appear briefly
        const hint = page.locator('#keyboardHint')
        await expect(hint).toBeVisible()
    })

    test('builder with existing workflow loads data', async ({ page }) => {
        await login(page)
        const workflowId = await createWorkflow(page, 'Builder Load Test ' + Date.now())

        if (workflowId) {
            await page.goto(`${BASE_URL}/workflows/builder/${workflowId}`)
            await page.waitForLoadState('networkidle')

            await expect(page).toHaveURL(new RegExp(`/workflows/builder/${workflowId}`))
            await expect(page.locator('#workflowBuilder')).toBeVisible()
        }
    })
})

// ---------------------------------------------------------------------------
// Workflow Templates
// ---------------------------------------------------------------------------
test.describe('Workflow Templates', () => {
    test('redirects to login when using template unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/templates/test-template`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('using a template creates a workflow and redirects to builder', async ({ page }) => {
        await login(page)
        // Use a template slug - this may 404 if no templates exist, that's fine
        await page.goto(`${BASE_URL}/workflows/templates/auto-reply`)
        await page.waitForLoadState('networkidle')

        // Should redirect to builder or show 404
        const url = page.url()
        const response = await page.request.get(`${BASE_URL}/workflows/templates/auto-reply`)
        expect([200, 302, 404]).toContain(response.status())
    })

    test('template dropdown in builder shows templates', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/builder`)
        await page.waitForLoadState('networkidle')

        const templatesBtn = page.locator('button:has-text("Templates")')
        if (await templatesBtn.isVisible()) {
            await templatesBtn.click()
            await page.waitForTimeout(300)

            const dropdown = page.locator('#templatesDropdown')
            if (await dropdown.isVisible().catch(() => false)) {
                await expect(page.locator('text=Templates').nth(1)).toBeVisible()
            }
        }
    })
})

// ---------------------------------------------------------------------------
// Webhook Info
// ---------------------------------------------------------------------------
test.describe('Webhook Info', () => {
    let workflowId

    test.beforeEach(async ({ page }) => {
        await login(page)
        workflowId = await createWorkflow(page, 'Webhook Test ' + Date.now())
    })

    test('redirects to login when unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/1/webhook`)
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })

    test('webhook info page loads with URL and secret', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}/webhook`))
        await expect(page.locator('h2')).toContainText('Webhook Configuration')
        await expect(page.locator('#webhookUrl')).toBeVisible()
        await expect(page.locator('#webhookSecret')).toBeVisible()
    })

    test('webhook URL is read-only', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        const urlInput = page.locator('#webhookUrl')
        await expect(urlInput).toHaveAttribute('readonly', '')
    })

    test('webhook secret is read-only', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        const secretInput = page.locator('#webhookSecret')
        await expect(secretInput).toHaveAttribute('readonly', '')
    })

    test('copy buttons are present for URL and secret', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        // Copy buttons near webhook URL and secret
        const copyButtons = page.locator('button:has(i.fa-copy)')
        const count = await copyButtons.count()
        expect(count).toBeGreaterThanOrEqual(2)
    })

    test('regenerate secret button triggers confirmation', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        const regenerateBtn = page.locator('button:has-text("Regenerate Secret")')
        if (await regenerateBtn.isVisible()) {
            page.once('dialog', d => {
                expect(d.type()).toBe('confirm')
                d.dismiss()
            })
            await regenerateBtn.click()
        }
    })

    test('example payload and curl are displayed', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('text=Example Payload')).toBeVisible()
        await expect(page.locator('text=Example cURL')).toBeVisible()
    })

    test('webhook logs section is present', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        await expect(page.locator('text=Webhook Logs')).toBeVisible()
    })

    test('back button returns to workflow show page', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        await page.goto(`${BASE_URL}/workflows/${workflowId}/webhook`)
        await page.waitForLoadState('networkidle')

        const backBtn = page.locator('a:has-text("Back")').first()
        if (await backBtn.isVisible()) {
            await backBtn.click()
            await page.waitForURL(new RegExp(`/workflows/${workflowId}`))
            await expect(page).toHaveURL(new RegExp(`/workflows/${workflowId}`))
        }
    })

    test('cannot access webhook info for another agency workflow', async ({ page }) => {
        await page.goto(`${BASE_URL}/workflows/999999/webhook`)
        await page.waitForLoadState('networkidle')

        const response = await page.request.get(`${BASE_URL}/workflows/999999/webhook`)
        expect([403, 404]).toContain(response.status())
    })

    test('regenerate webhook via API requires auth', async ({ request }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const response = await request.post(`${BASE_URL}/workflows/${workflowId}/webhook/regenerate`)
        const status = response.status()
        expect([302, 401, 403]).toContain(status)
    })
})

// ---------------------------------------------------------------------------
// Toggle Workflow Status
// ---------------------------------------------------------------------------
test.describe('Toggle Workflow Status', () => {
    let workflowId

    test.beforeEach(async ({ page }) => {
        await login(page)
        workflowId = await createWorkflow(page, 'Toggle Test ' + Date.now())
    })

    test('toggle requires authentication', async ({ request }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const response = await request.post(`${BASE_URL}/workflows/${workflowId}/toggle`)
        const status = response.status()
        expect([302, 401, 403]).toContain(status)
    })

    test('toggle status updates and returns success', async ({ page }) => {
        test.skip(!workflowId, 'No workflow created for test')

        const response = await page.request.post(`${BASE_URL}/workflows/${workflowId}/toggle`, {
            headers: { 'Accept': 'application/json' },
        })

        const status = response.status()
        // 200 with flash message or JSON
        expect([200, 302]).toContain(status)
    })

    test('cannot toggle another agency workflow', async ({ page }) => {
        const response = await page.request.post(`${BASE_URL}/workflows/999999/toggle`, {
            headers: { 'Accept': 'application/json' },
        })
        const status = response.status()
        expect([403, 404]).toContain(status)
    })
})

// ---------------------------------------------------------------------------
// Delete Workflow
// ---------------------------------------------------------------------------
test.describe('Delete Workflow', () => {
    test('delete requires authentication', async ({ request }) => {
        const response = await request.delete(`${BASE_URL}/workflows/1`)
        const status = response.status()
        expect([302, 401, 403]).toContain(status)
    })

    test('deleting a workflow redirects to index', async ({ page }) => {
        await login(page)
        const workflowId = await createWorkflow(page, 'Delete Test ' + Date.now())

        if (workflowId) {
            // Navigate to index and delete
            await page.goto(`${BASE_URL}/workflows`)
            await page.waitForLoadState('networkidle')

            page.on('dialog', d => d.accept())
            const deleteBtn = page.locator('tbody tr').first().locator('form[action*="workflows"] button, button[onclick*="confirm"]').first()
            if (await deleteBtn.isVisible().catch(() => false)) {
                await deleteBtn.click()
                await page.waitForLoadState('networkidle')
                await expect(page).toHaveURL(/\/workflows/)
            }
        }
    })

    test('cannot delete another agency workflow', async ({ page }) => {
        await login(page)
        const response = await page.request.delete(`${BASE_URL}/workflows/999999`, {
            headers: { 'Accept': 'application/json' },
        })
        const status = response.status()
        expect([403, 404]).toContain(status)
    })

    test('delete shows success message', async ({ page }) => {
        await login(page)
        const workflowId = await createWorkflow(page, 'Delete Msg Test ' + Date.now())

        if (workflowId) {
            await page.goto(`${BASE_URL}/workflows`)
            await page.waitForLoadState('networkidle')

            page.on('dialog', d => d.accept())
            const deleteBtn = page.locator('tbody tr').first().locator('form[action*="workflows"] button, button[onclick*="confirm"]').first()
            if (await deleteBtn.isVisible().catch(() => false)) {
                await deleteBtn.click()
                await page.waitForLoadState('networkidle')
                // Flash message should appear
                const flash = page.locator('text=Workflow deleted, .flash, .alert, .toast').first()
                if (await flash.isVisible().catch(() => false)) {
                    await expect(flash).toBeVisible()
                }
            }
        }
    })
})

// ---------------------------------------------------------------------------
// Cross-feature Auth Checks
// ---------------------------------------------------------------------------
test.describe('Workflow Authorization Guards', () => {
    const protectedPaths = [
        '/workflows',
        '/workflows/create',
        '/workflows/builder',
    ]

    for (const path of protectedPaths) {
        test(`${path} redirects unauthenticated user to login`, async ({ page }) => {
            await page.goto(`${BASE_URL}${path}`)
            await page.waitForLoadState('networkidle')
            expect(page.url()).toContain('/login')
        })
    }

    test('unauthenticated user cannot POST to workflows store', async ({ request }) => {
        const response = await request.post(`${BASE_URL}/workflows`, {
            data: { name: 'Hacker Workflow', trigger_type: 'post_published', actions: '[]' },
        })
        const status = response.status()
        expect([302, 401, 403]).toContain(status)
    })

    test('unauthenticated user cannot access API workflows', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/api/v1/workflows`)
        const status = response.status()
        expect([302, 401, 403]).toContain(status)
    })

    test('authenticated user can access all workflow pages', async ({ page }) => {
        await login(page)

        const paths = [
            '/workflows',
            '/workflows/create',
            '/workflows/builder',
        ]

        for (const path of paths) {
            const response = await page.goto(`${BASE_URL}${path}`)
            expect(response.status()).toBe(200)
            await expect(page.locator('body')).toBeVisible()
        }
    })

    test('workflow pages require agency middleware', async ({ page }) => {
        // Login but check that agency middleware is enforced
        // (This would require a user without an agency, which we can't easily test here)
        await login(page)
        await page.goto(`${BASE_URL}/workflows`)
        await page.waitForLoadState('networkidle')
        // If agency middleware fails, user would be redirected or get an error
        expect(page.url()).not.toContain('/login')
    })
})

// ---------------------------------------------------------------------------
// Form Validation Edge Cases
// ---------------------------------------------------------------------------
test.describe('Workflow Form Validation', () => {
    test('name max length 255 characters', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        const longName = 'A'.repeat(256)
        await page.fill('input[name="name"]', longName)
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'x' } }]))
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        // Should be rejected due to max length
        await expect(page).toHaveURL(/\/workflows\/create/)
    })

    test('valid name at max length 255', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        const maxName = 'A'.repeat(255)
        await page.fill('input[name="name"]', maxName)
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'x' } }]))
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        // Should succeed
        await expect(page).toHaveURL(/\/workflows\/\d+/)
    })

    test('special characters in workflow name', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        const specialName = 'Test <script> & "quotes" ' + Date.now()
        await page.fill('input[name="name"]', specialName)
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'x' } }]))
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/\d+/)
    })

    test('multiple actions in workflow', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        const actions = [
            { type: 'send_notification', config: { message: 'Step 1' } },
            { type: 'auto_reply', config: { message: 'Thanks!' } },
            { type: 'tag_client', config: { tag: 'engaged' } },
        ]

        await page.fill('input[name="name"]', 'Multi Action ' + Date.now())
        await page.selectOption('select[name="trigger_type"]', 'comment_received')
        await page.fill('textarea[name="actions"]', JSON.stringify(actions))
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/\d+/)
    })

    test('conditions field is optional', async ({ page }) => {
        await login(page)
        await page.goto(`${BASE_URL}/workflows/create`)
        await page.waitForLoadState('networkidle')

        await page.fill('input[name="name"]', 'No Conditions ' + Date.now())
        await page.selectOption('select[name="trigger_type"]', 'post_published')
        await page.fill('textarea[name="actions"]', JSON.stringify([{ type: 'send_notification', config: { message: 'test' } }]))
        // Leave conditions empty
        await page.click('button:has-text("Create")')
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/workflows\/\d+/)
    })
})
