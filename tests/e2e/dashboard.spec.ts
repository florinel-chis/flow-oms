import { test, expect } from '@playwright/test';
import { DashboardPage } from './pages/DashboardPage';

test.describe('Dashboard', () => {
  let dashboardPage: DashboardPage;

  test.beforeEach(async ({ page }) => {
    dashboardPage = new DashboardPage(page);
    await dashboardPage.navigate();
  });

  test('should load dashboard page', async () => {
    await dashboardPage.verify();
  });

  test('should display navigation sidebar', async () => {
    await expect(dashboardPage.sidebar).toBeVisible();
  });

  test('should have dashboard heading', async () => {
    await expect(dashboardPage.heading).toContainText(/dashboard/i);
  });

  test('should display widgets', async ({ page }) => {
    await dashboardPage.verify();

    // Check that widgets are visible
    const widgetCount = await dashboardPage.getWidgetCount();
    expect(widgetCount).toBeGreaterThan(0);
  });

  test('should navigate to orders from sidebar', async () => {
    await dashboardPage.clickNavigationItem('Orders');
    await expect(dashboardPage.page).toHaveURL(/\/orders$/);
  });

  test('should navigate to invoices from sidebar', async () => {
    await dashboardPage.clickNavigationItem('Invoices');
    await expect(dashboardPage.page).toHaveURL(/\/invoices$/);
  });

  test('should navigate to order syncs from sidebar', async () => {
    await dashboardPage.clickNavigationItem('Order Syncs');
    await expect(dashboardPage.page).toHaveURL(/\/magento-order-syncs$/);
  });

  test('should navigate to magento stores from sidebar', async () => {
    await dashboardPage.clickNavigationItem('Magento Stores');
    await expect(dashboardPage.page).toHaveURL(/\/magento-stores$/);
  });
});
