import { Page, expect } from '@playwright/test';

/**
 * Test Helper Functions
 */

/**
 * Wait for a specific time
 */
export async function wait(ms: number) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Wait for network to be idle
 */
export async function waitForNetwork(page: Page) {
  await page.waitForLoadState('networkidle');
}

/**
 * Retry an action until it succeeds or timeout
 */
export async function retry<T>(
  fn: () => Promise<T>,
  options: { retries?: number; delay?: number } = {}
): Promise<T> {
  const { retries = 3, delay = 1000 } = options;

  for (let i = 0; i < retries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (i === retries - 1) throw error;
      await wait(delay);
    }
  }

  throw new Error('Retry failed');
}

/**
 * Take a screenshot with timestamp
 */
export async function screenshotWithTimestamp(page: Page, name: string) {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  await page.screenshot({
    path: `test-results/screenshots/${name}-${timestamp}.png`,
    fullPage: true
  });
}

/**
 * Get text content safely
 */
export async function getTextContent(page: Page, selector: string): Promise<string> {
  const element = page.locator(selector);
  await expect(element).toBeVisible();
  const text = await element.textContent();
  return text?.trim() || '';
}

/**
 * Check if element exists (without throwing)
 */
export async function elementExists(page: Page, selector: string): Promise<boolean> {
  return page.locator(selector).count().then(count => count > 0);
}

/**
 * Click and wait for navigation
 */
export async function clickAndWaitForNavigation(page: Page, selector: string) {
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.locator(selector).click()
  ]);
}

/**
 * Fill form field safely
 */
export async function fillFormField(page: Page, label: string, value: string) {
  const field = page.getByLabel(new RegExp(label, 'i'));
  await expect(field).toBeVisible();
  await field.clear();
  await field.fill(value);
}

/**
 * Get table row count
 */
export async function getTableRowCount(page: Page, tableSelector: string = 'table'): Promise<number> {
  const table = page.locator(tableSelector).first();
  await expect(table).toBeVisible();
  return table.locator('tbody tr').count();
}

/**
 * Get current tenant from URL
 */
export function getTenantFromUrl(url: string): string | null {
  const match = url.match(/\/admin\/([^/]+)/);
  return match ? match[1] : null;
}

/**
 * Format currency
 */
export function formatCurrency(amount: number, currency: string = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
  }).format(amount);
}

/**
 * Generate random string
 */
export function randomString(length: number = 10): string {
  return Math.random().toString(36).substring(2, length + 2);
}

/**
 * Generate random email
 */
export function randomEmail(): string {
  return `test-${randomString()}@example.com`;
}

/**
 * Parse date from string
 */
export function parseDate(dateString: string): Date {
  return new Date(dateString);
}

/**
 * Check if date is recent (within last N days)
 */
export function isRecentDate(date: Date, days: number = 7): boolean {
  const now = new Date();
  const diff = now.getTime() - date.getTime();
  const daysDiff = diff / (1000 * 3600 * 24);
  return daysDiff <= days;
}
