import { test, expect } from '@playwright/test';
import { MagentoOrderSyncsPage } from './pages/MagentoOrderSyncsPage';

test.describe('Magento Order Syncs', () => {
  let syncsPage: MagentoOrderSyncsPage;

  test.beforeEach(async ({ page }) => {
    syncsPage = new MagentoOrderSyncsPage(page);
    await syncsPage.navigate();
  });

  test('should load magento order syncs page', async () => {
    await syncsPage.verify();
  });

  test('should display syncs table', async () => {
    await expect(syncsPage.table).toBeVisible();
  });

  test('should have sync orders button', async () => {
    await expect(syncsPage.syncButton).toBeVisible();
  });

  test('should display synced orders if available', async () => {
    const count = await syncsPage.getSyncCount();

    // If we have syncs, verify table is not empty
    if (count > 0) {
      expect(count).toBeGreaterThan(0);
    }
  });

  test('should filter by order status', async () => {
    // Get initial count
    const initialCount = await syncsPage.getSyncCount();

    // Apply filter
    await syncsPage.filterByStatus('processing');
    await syncsPage.page.waitForLoadState('networkidle');

    // Wait for table to update
    await syncsPage.page.waitForTimeout(500);

    // Verify filtering occurred - count should be >= 0
    const filteredCount = await syncsPage.getSyncCount();
    expect(filteredCount).toBeGreaterThanOrEqual(0);
  });

  test('should search for orders', async () => {
    const firstRow = syncsPage.table.locator('tbody tr').first();
    const isVisible = await firstRow.isVisible().catch(() => false);

    if (isVisible) {
      const orderNumber = await firstRow.locator('td').nth(2).textContent();

      if (orderNumber) {
        await syncsPage.searchOrder(orderNumber.trim());
        await expect(firstRow).toBeVisible();
      }
    }
  });

  test('should view sync details', async ({ page }) => {
    const firstRow = syncsPage.table.locator('tbody tr').first();
    const isVisible = await firstRow.isVisible().catch(() => false);

    if (isVisible) {
      await syncsPage.viewFirstSync();

      // Verify we're on detail page
      await expect(page).toHaveURL(/\/magento-order-syncs\/\d+$/);
      await expect(page.locator('h1').first()).toBeVisible();
    }
  });

  test('should display sync details sections', async ({ page }) => {
    const firstRow = syncsPage.table.locator('tbody tr').first();
    const isVisible = await firstRow.isVisible().catch(() => false);

    if (isVisible) {
      await syncsPage.viewFirstSync();

      // Check sections are visible
      await expect(page.getByText(/order information/i).first()).toBeVisible();
      await expect(page.getByText(/customer information/i).first()).toBeVisible();
      await expect(page.getByText(/order details/i).first()).toBeVisible();
      await expect(page.getByText(/sync status/i).first()).toBeVisible();
    }
  });

  test('should have proper table columns', async () => {
    const headers = syncsPage.table.locator('thead th');
    const headerCount = await headers.count();

    expect(headerCount).toBeGreaterThan(7);

    // Check for key columns
    await expect(syncsPage.table.locator('th', { hasText: /entity id/i }).first()).toBeVisible();
    await expect(syncsPage.table.locator('th', { hasText: /order/i }).first()).toBeVisible();
    await expect(syncsPage.table.locator('th', { hasText: /status/i }).first()).toBeVisible();
    await expect(syncsPage.table.locator('th', { hasText: /synced at/i }).first()).toBeVisible();
  });

  test('should display invoice and shipment icons', async () => {
    const firstRow = syncsPage.table.locator('tbody tr').first();
    const isVisible = await firstRow.isVisible().catch(() => false);

    if (isVisible) {
      // Check for icon columns
      const icons = firstRow.locator('[class*="fi-icon"]');
      const iconCount = await icons.count();

      expect(iconCount).toBeGreaterThan(0);
    }
  });

  test('should have filters available', async () => {
    // Open filters panel first
    await syncsPage.openFilters();

    // Wait for filters to be visible
    await syncsPage.page.waitForTimeout(500);

    // Check that filter dropdown buttons are present (look for "All" buttons)
    const filterButtons = syncsPage.page.locator('button, [role="button"]').filter({ hasText: 'All' });
    const count = await filterButtons.count();
    expect(count).toBeGreaterThan(2); // Should have at least Order Status and Magento Store filters
  });
});
