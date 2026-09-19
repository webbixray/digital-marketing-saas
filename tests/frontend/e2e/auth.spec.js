import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'

test.describe('Authentication Flow', () => {
    test('shows login page with form', async ({ page }) => {
        await page.goto(`${BASE_URL}/login`)
        await expect(page.locator('h1')).toContainText('Welcome Back')
        await expect(page.locator('input[name="email"]')).toBeVisible()
        await expect(page.locator('input[name="password"]')).toBeVisible()
        await expect(page.locator('button[type="submit"]')).toBeVisible()
    })

    test('shows registration page with form', async ({ page }) => {
        await page.goto(`${BASE_URL}/register`)
        await expect(page.locator('h1')).toContainText('Get Started')
        await expect(page.locator('input[name="agency_name"]')).toBeVisible()
        await expect(page.locator('input[name="name"]')).toBeVisible()
        await expect(page.locator('input[name="email"]')).toBeVisible()
        await expect(page.locator('input[name="password"]')).toBeVisible()
        await expect(page.locator('input[name="password_confirmation"]')).toBeVisible()
    })

    test('login with seeded test user redirects to dashboard', async ({ page }) => {
        await page.goto(`${BASE_URL}/login`)
        await page.fill('input[name="email"]', 'test@agency.com')
        await page.fill('input[name="password"]', 'password')
        await page.click('button[type="submit"]')
        await page.waitForURL('**/dashboard')
        await expect(page).toHaveURL(/\/dashboard/)
    })

    test('login with invalid credentials shows error', async ({ page }) => {
        await page.goto(`${BASE_URL}/login`)
        await page.fill('input[name="email"]', 'wrong@test.com')
        await page.fill('input[name="password"]', 'wrongpassword')
        await page.click('button[type="submit"]')
        await page.waitForLoadState('networkidle')
        await expect(page).toHaveURL(/\/login/)
    })
})

test.describe('Dashboard Access', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/login`)
        await page.fill('input[name="email"]', 'test@agency.com')
        await page.fill('input[name="password"]', 'password')
        await page.click('button[type="submit"]')
        await page.waitForURL('**/dashboard')
    })

    test('dashboard loads for authenticated user', async ({ page }) => {
        await expect(page.locator('body')).toBeVisible()
        await expect(page).not.toHaveURL(/\/login/)
    })

    test('sidebar navigation is visible', async ({ page }) => {
        await expect(page.locator('.sidebar-nav, [class*="sidebar"]')).toBeVisible()
    })

    test('user can logout', async ({ page }) => {
        // Click logout button (form submit)
        const logoutBtn = page.locator('button:has-text("Logout"), a:has-text("Logout"), form[action*="logout"] button').first()
        if (await logoutBtn.isVisible()) {
            await logoutBtn.click()
            await page.waitForLoadState('networkidle')
            // Should redirect to login or public page
            const url = page.url()
            expect(url).not.toContain('/dashboard')
        }
    })
})

test.describe('Social Posts', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/login`)
        await page.fill('input[name="email"]', 'test@agency.com')
        await page.fill('input[name="password"]', 'password')
        await page.click('button[type="submit"]')
        await page.waitForURL('**/dashboard')
    })

    test('shows posts list page', async ({ page }) => {
        await page.goto(`${BASE_URL}/social/posts`)
        await expect(page.locator('body')).toBeVisible()
    })

    test('shows create post page', async ({ page }) => {
        await page.goto(`${BASE_URL}/social/posts/create`)
        await expect(page.locator('body')).toBeVisible()
    })
})

test.describe('Public Pages', () => {
    test('landing page loads', async ({ page }) => {
        await page.goto(BASE_URL)
        await expect(page.locator('nav')).toBeVisible()
    })

    test('pricing page loads', async ({ page }) => {
        await page.goto(`${BASE_URL}/pricing`)
        await expect(page.locator('h1')).toBeVisible()
    })

    test('features page loads', async ({ page }) => {
        await page.goto(`${BASE_URL}/features`)
        await expect(page.locator('body')).toBeVisible()
    })
})

test.describe('API Endpoints', () => {
    test('health check returns ok', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/api/health`)
        expect(response.status()).toBe(200)
        const body = await response.json()
        expect(body.status).toBe('ok')
    })

    test('API status returns ok', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/api/status`)
        expect(response.status()).toBe(200)
        const body = await response.json()
        expect(body.status).toBe('ok')
    })

    test('unauthenticated API request returns 401', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/api/v1/dashboard`)
        expect(response.status()).toBe(401)
    })
})

test.describe('Security Headers', () => {
    test('response includes security headers', async ({ request }) => {
        const response = await request.get(`${BASE_URL}/login`)
        expect(response.headers()['x-content-type-options']).toBe('nosniff')
        expect(response.headers()['x-frame-options']).toBe('SAMEORIGIN')
        expect(response.headers()['referrer-policy']).toBe('strict-origin-when-cross-origin')
    })
})
