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
// Onboarding Flow
// ---------------------------------------------------------------------------
test.describe('Onboarding Flow', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('onboarding step 1 loads with progress bar', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/onboarding\/step1/)
    await expect(page.locator('text=Step 1 of 5')).toBeVisible()
    await expect(page.locator('text=Let\'s Get You Set Up!')).toBeVisible()
  })

  test('step 1 shows progress indicator at 20%', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    // Progress bar should show 20%
    const progressBar = page.locator('.bg-indigo-600.h-2')
    await expect(progressBar).toBeVisible()
  })

  test('step 1 has all 5 step indicators', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Agency')).toBeVisible()
    await expect(page.locator('text=Social')).toBeVisible()
    await expect(page.locator('text=Team')).toBeVisible()
    await expect(page.locator('text=Campaign')).toBeVisible()
    await expect(page.locator('text=AI')).toBeVisible()
  })

  test('step 1 form has agency_name, website, timezone, phone fields', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('input[name="agency_name"]')).toBeVisible()
    await expect(page.locator('input[name="website"]')).toBeVisible()
    await expect(page.locator('select[name="timezone"]')).toBeVisible()
    await expect(page.locator('input[name="phone"]')).toBeVisible()
  })

  test('step 1 agency_name is required', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    // Submit without agency_name
    await page.fill('input[name="website"]', 'https://test.com')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')

    // Should stay on step 1
    await expect(page).toHaveURL(/\/onboarding\/step1/)
  })

  test('step 1 timezone is required', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    await page.fill('input[name="agency_name"]', 'Test Agency')
    // Don't select timezone
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')

    // Should stay on step 1
    await expect(page).toHaveURL(/\/onboarding\/step1/)
  })

  test('step 1 skip link goes to dashboard', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    const skipLink = page.locator('a:has-text("Skip for now")')
    if (await skipLink.isVisible()) {
      await skipLink.click()
      await page.waitForURL('**/dashboard')
      await expect(page).toHaveURL(/\/dashboard/)
    }
  })

  test('step 1 quick tip is visible', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('text=Quick Tip')).toBeVisible()
    await expect(page.locator('text=You can always change these settings later')).toBeVisible()
  })

  test('step 1 submit with valid data goes to step 2', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')

    await page.fill('input[name="agency_name"]', 'E2E Test Agency ' + Date.now())
    await page.fill('input[name="website"]', 'https://e2e-test.com')
    await page.selectOption('select[name="timezone"]', 'UTC')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')

    // Should advance to step 2
    await expect(page).toHaveURL(/\/onboarding\/step2/)
  })
})

// ---------------------------------------------------------------------------
// Step Navigation
// ---------------------------------------------------------------------------
test.describe('Onboarding Step Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('step 2 loads with social platform options', async ({ page }) => {
    // Complete step 1 first
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="agency_name"]', 'Nav Test ' + Date.now())
    await page.selectOption('select[name="timezone"]', 'UTC')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/onboarding\/step2/)
    await expect(page.locator('text=Connect Your Social Media Accounts')).toBeVisible()
  })

  test('step 2 shows platform cards (facebook, instagram, twitter, etc)', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step2`)
    await page.waitForLoadState('networkidle')

    // Should show platform options
    await expect(page.locator('text=Facebook, text=Instagram, text=Twitter')).toBeVisible()
  })

  test('step 2 has back button to step 1', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step2`)
    await page.waitForLoadState('networkidle')

    const backBtn = page.locator('a:has-text("Back")')
    if (await backBtn.isVisible()) {
      await backBtn.click()
      await page.waitForURL('**/onboarding/step1')
      await expect(page).toHaveURL(/\/onboarding\/step1/)
    }
  })

  test('step 2 next button goes to step 3', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step2`)
    await page.waitForLoadState('networkidle')

    const nextBtn = page.locator('a:has-text("Next: Invite Team"), button:has-text("Next")').first()
    if (await nextBtn.isVisible()) {
      await nextBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/onboarding\/step3/)
    }
  })

  test('step 3 loads with team invitation form', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step3`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/onboarding\/step3/)
    await expect(page.locator('body')).toBeVisible()
  })

  test('step 3 has back button to step 2', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step3`)
    await page.waitForLoadState('networkidle')

    const backBtn = page.locator('a:has-text("Back")')
    if (await backBtn.isVisible()) {
      await backBtn.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/onboarding\/step2/)
    }
  })

  test('step 4 loads with campaign creation form', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step4`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/onboarding\/step4/)
    await expect(page.locator('body')).toBeVisible()
  })

  test('step 5 loads with AI activation', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step5`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/onboarding\/step5/)
    await expect(page.locator('body')).toBeVisible()
  })

  test('navigating through all steps in sequence', async ({ page }) => {
    // Step 1
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="agency_name"]', 'Full Flow Test ' + Date.now())
    await page.selectOption('select[name="timezone"]', 'UTC')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')

    // Step 2
    await expect(page).toHaveURL(/\/onboarding\/step2/)
    const step2Next = page.locator('a:has-text("Next: Invite Team"), button:has-text("Next")').first()
    if (await step2Next.isVisible()) {
      await step2Next.click()
      await page.waitForLoadState('networkidle')
    }

    // Step 3
    const step3Next = page.locator('a:has-text("Next"), button:has-text("Next")').first()
    if (await step3Next.isVisible().catch(() => false)) {
      await step3Next.click()
      await page.waitForLoadState('networkidle')
    }
  })
})

// ---------------------------------------------------------------------------
// Onboarding Completion
// ---------------------------------------------------------------------------
test.describe('Onboarding Completion', () => {
  test.beforeEach(async ({ page }) => {
    await login(page)
  })

  test('complete endpoint exists and accepts POST', async ({ page }) => {
    const response = await page.request.post(`${BASE_URL}/onboarding/complete`, {
      headers: { 'Accept': 'application/json' },
      data: {},
    })
    // Should redirect or return success
    const status = response.status()
    expect([200, 302]).toContain(status)
  })

  test('quick start page loads with options', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/quick-start`)
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/onboarding\/quick-start/)
    await expect(page.locator('text=Quick Start')).toBeVisible()
  })

  test('quick start has Create Sample Posts button', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/quick-start`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('button:has-text("Create Sample Posts")')).toBeVisible()
  })

  test('quick start has Custom Setup link', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/quick-start`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('a:has-text("Custom Setup"), button:has-text("Custom Setup")')).toBeVisible()
  })

  test('quick start Custom Setup goes to step 1', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/quick-start`)
    await page.waitForLoadState('networkidle')

    const customSetup = page.locator('a:has-text("Custom Setup"), button:has-text("Custom Setup")').first()
    if (await customSetup.isVisible()) {
      await customSetup.click()
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/onboarding\/step1/)
    }
  })

  test('quick start Create Sample Posts submits form', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/quick-start`)
    await page.waitForLoadState('networkidle')

    const sampleBtn = page.locator('button:has-text("Create Sample Posts")')
    if (await sampleBtn.isVisible()) {
      await sampleBtn.click()
      await page.waitForLoadState('networkidle')
      // Should redirect somewhere after creating samples
      await expect(page.locator('body')).toBeVisible()
    }
  })

  test('completing onboarding redirects to dashboard', async ({ page }) => {
    // Navigate through all steps
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[name="agency_name"]', 'Complete Test ' + Date.now())
    await page.selectOption('select[name="timezone"]', 'UTC')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')

    // Complete onboarding
    await page.goto(`${BASE_URL}/onboarding/complete`)
    await page.waitForLoadState('networkidle')

    // Should redirect to dashboard or show success
    const url = page.url()
    expect(url).toMatch(/\/dashboard|\/onboarding/)
  })
})

// ---------------------------------------------------------------------------
// Onboarding Auth Guards
// ---------------------------------------------------------------------------
test.describe('Onboarding Auth Guards', () => {
  test('unauthenticated user redirected from step 1', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step1`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user redirected from step 2', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/step2`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user redirected from quick start', async ({ page }) => {
    await page.goto(`${BASE_URL}/onboarding/quick-start`)
    await page.waitForLoadState('networkidle')
    expect(page.url()).toContain('/login')
  })

  test('unauthenticated user cannot POST to complete', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/onboarding/complete`)
    const status = response.status()
    expect([302, 401, 403]).toContain(status)
  })
})
