import { test as setup, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const authFile = join(__dirname, '.auth/user.json');

/**
 * Authentication Setup
 *
 * This runs before all tests to authenticate once and reuse the session.
 * See https://playwright.dev/docs/auth
 */
setup('authenticate', async ({ page }) => {
  // Navigate to login page
  await page.goto('/admin/login');

  // Wait for page to load
  await page.waitForLoadState('networkidle');

  console.log('On login page:', page.url());

  // Check if form exists
  const formExists = await page.locator('form').count();
  console.log('Forms found:', formExists);

  // Fill in login form - try multiple selector strategies
  const emailField = page.locator('input[type="email"]').or(page.locator('input[name="email"]')).or(page.getByLabel(/email/i)).first();
  await emailField.waitFor({ state: 'visible', timeout: 5000 });
  await emailField.fill('test@example.com');
  console.log('✓ Email filled (test@example.com)');

  const passwordField = page.locator('input[type="password"]').or(page.locator('input[name="password"]')).or(page.getByLabel(/password/i)).first();
  await passwordField.waitFor({ state: 'visible', timeout: 5000 });
  await passwordField.fill('password');
  console.log('✓ Password filled');

  // Click login button
  const loginButton = page.locator('button[type="submit"]').or(page.getByRole('button', { name: /sign in/i })).first();
  await loginButton.waitFor({ state: 'visible', timeout: 5000 });
  console.log('✓ Login button found, clicking...');

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 }).catch(() => console.log('Navigation timeout')),
    loginButton.click(),
  ]);

  // Wait for navigation - be more flexible with URL pattern
  try {
    await page.waitForURL(/\/admin\//, { timeout: 15000 });
  } catch (e) {
    console.log('Current URL:', page.url());
    throw e;
  }

  // Wait for page to be ready
  await page.waitForLoadState('networkidle');

  console.log('✓ Navigated to:', page.url());

  // Verify we're logged in - check if we're not on login page anymore
  const currentUrl = page.url();
  const isOnLoginPage = currentUrl.includes('/admin/login');

  if (isOnLoginPage) {
    console.log('❌ Still on login page. Login might have failed.');
    await page.screenshot({ path: 'test-results/login-failed.png', fullPage: true });
    throw new Error('Login verification failed - still on login page');
  }

  // Additional check - verify sidebar navigation exists
  const sidebar = page.locator('.fi-sidebar-nav').or(page.locator('aside')).first();
  await sidebar.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {
    console.log('Warning: Sidebar not found, but login succeeded');
  });

  // Save signed-in state to file
  await page.context().storageState({ path: authFile });

  console.log('✓ Authentication successful, session saved');
  console.log('✓ Final URL:', page.url());
});
