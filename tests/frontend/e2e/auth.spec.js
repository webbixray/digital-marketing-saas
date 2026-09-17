/**
 * E2E tests for critical user journeys
 * Tests the actual application in a real browser
 */
import { test, expect } from '@playwright/test'

// Test configuration
const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'

test.describe('Authentication', () => {
    test('shows login page', async ({ page }) => {
        await page.goto(`${BASE_URL}/login`);
        await expect(page.locator('h1')).toContainText('Login');
        await expect(page.locator('[name="email"]')).toBeVisible();
        await expect(page.locator('[name="password"]')).toBeVisible();
    });

    test('shows registration page', async ({ page }) => {
        await page.goto(`${BASE_URL}/register`);
        await expect(page.locator('h1')).toContainText('Register');
        await expect(page.locator('[name="name"]')).toBeVisible();
        await expect(page.locator('[name="email"]')).toBeVisible();
        await expect(page.locator('[name="password"]')).toBeVisible();
        await expect(page.locator('[name="password_confirmation"]')).toBeVisible();
    });

    test('redirects to login when accessing dashboard unauthenticated', async ({ page }) => {
        await page.goto(`${BASE_URL}/dashboard`);
        await expect(page).toHaveURL(/\/login/);
    });
});

test.describe('Public Pages', () => {
    test('shows landing page', async ({ page }) => {
        await page.goto(BASE_URL);
        await expect(page.locator('nav')).toBeVisible();
    });

    test('shows pricing page', async ({ page }) => {
        await page.goto(`${BASE_URL}/pricing`);
        await expect(page.locator('h1')).toBeVisible();
    });
});

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Login as test user
        await page.goto(`${BASE_URL}/login`);
        await page.fill('[name="email"]', 'test@agency.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/);
    });

    test('shows dashboard for authenticated user', async ({ page }) => {
        await expect(page.locator('h1')).toContainText('Dashboard');
    });

    test('shows main navigation', async ({ page }) => {
        await expect(page.locator('nav')).toBeVisible();
    });
});

test.describe('Health Check', () => {
    test('health endpoint returns 200', async ({ page }) => {
        const response = await page.goto(`${BASE_URL}/up`);
        expect(response.status()).toBe(200);
    });
});

test.describe('API', () => {
    test('API status endpoint returns ok', async ({ page }) => {
        const response = await page.goto(`${BASE_URL}/api/v1/status`);
        expect(response.status()).toBe(200);
        const body = await response.json();
        expect(body.status).toBe('ok');
    });
});

test.describe('Social Posts', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/login`);
        await page.fill('[name="email"]', 'test@agency.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/);
    });

    test('shows posts page', async ({ page }) => {
        await page.goto(`${BASE_URL}/social/posts`);
        await expect(page.locator('h1')).toContainText('Posts');
    });

    test('shows create post page', async ({ page }) => {
        await page.goto(`${BASE_URL}/social/posts/create`);
        await expect(page.locator('h1')).toContainText('Create');
    });
});

test.describe('Campaigns', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/login`);
        await page.fill('[name="email"]', 'test@agency.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/);
    });

    test('shows campaigns page', async ({ page }) => {
        await page.goto(`${BASE_URL}/campaigns`);
        await expect(page.locator('h1')).toContainText('Campaign');
    });
});

test.describe('Analytics', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/login`);
        await page.fill('[name="email"]', 'test@agency.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/);
    });

    test('shows analytics page', async ({ page }) => {
        await page.goto(`${BASE_URL}/analytics`);
        await expect(page.locator('h1')).toContainText('Analytics');
    });
});

test.describe('Billing', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/login`);
        await page.fill('[name="email"]', 'test@agency.com');
        await page.fill('[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/);
    });

    test('shows billing page', async ({ page }) => {
        await page.goto(`${BASE_URL}/agency/billing`);
        await expect(page.locator('body')).toBeVisible();
    });
});
