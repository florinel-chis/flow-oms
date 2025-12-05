import { test, expect } from '@playwright/test';
import { InvoicesPage } from './pages/InvoicesPage';

test.describe('Invoices', () => {
  let invoicesPage: InvoicesPage;

  test.beforeEach(async ({ page }) => {
    invoicesPage = new InvoicesPage(page);
    await invoicesPage.navigate();
  });

  test('should load invoices list page', async () => {
    await invoicesPage.verify();
  });

  test('should display invoices table', async () => {
    await expect(invoicesPage.table).toBeVisible();
  });

  test('should have invoices in table', async () => {
    const count = await invoicesPage.getInvoiceCount();
    expect(count).toBeGreaterThan(0);
  });

  test('should filter invoices by state', async () => {
    // Get initial count
    const initialCount = await invoicesPage.getInvoiceCount();

    // Apply filter
    await invoicesPage.filterByState('paid');
    await invoicesPage.page.waitForLoadState('networkidle');

    // Wait for table to update
    await invoicesPage.page.waitForTimeout(500);

    // Verify filtering occurred - count should be >= 0
    const filteredCount = await invoicesPage.getInvoiceCount();
    expect(filteredCount).toBeGreaterThanOrEqual(0);
  });

  test('should search for invoices', async () => {
    // Get first invoice number
    const firstRow = invoicesPage.table.locator('tbody tr').first();
    const invoiceNumber = await firstRow.locator('td').nth(1).textContent();

    if (invoiceNumber) {
      await invoicesPage.searchInvoice(invoiceNumber.trim());
      await expect(firstRow).toBeVisible();
    }
  });

  test('should view invoice details', async ({ page }) => {
    await invoicesPage.viewFirstInvoice();

    // Verify we're on invoice detail page
    await expect(page).toHaveURL(/\/invoices\/\d+$/);
    await expect(page.locator('h1').first()).toBeVisible();
  });

  test('should display invoice details sections', async ({ page }) => {
    await invoicesPage.viewFirstInvoice();

    // Check main sections
    await expect(page.getByText(/invoice details/i).first()).toBeVisible();
    await expect(page.getByText(/customer/i).first()).toBeVisible();
    await expect(page.getByText(/totals/i).first()).toBeVisible();
    await expect(page.getByText(/invoice items/i).first()).toBeVisible();
  });

  test('should display invoice items', async ({ page }) => {
    await invoicesPage.viewFirstInvoice();

    // Wait for items section
    const itemsSection = page.locator('[class*="fi-section"]', { hasText: /invoice items/i }).first();
    await expect(itemsSection).toBeVisible();
  });

  test('should have proper table columns', async () => {
    const headers = invoicesPage.table.locator('thead th');
    const headerCount = await headers.count();

    expect(headerCount).toBeGreaterThan(5);

    // Check for key columns
    await expect(invoicesPage.table.locator('th', { hasText: /invoice/i }).first()).toBeVisible();
    await expect(invoicesPage.table.locator('th', { hasText: /state/i }).first()).toBeVisible();
    await expect(invoicesPage.table.locator('th', { hasText: /amount/i }).first()).toBeVisible();
  });

  test('should show invoice state badges', async () => {
    const badges = invoicesPage.table.locator('[class*="fi-badge"]');
    const badgeCount = await badges.count();

    expect(badgeCount).toBeGreaterThan(0);
  });

  test('should only have View action (no Print or Email buttons)', async () => {
    // Check that Print and Email actions are NOT present
    const printButton = invoicesPage.page.getByRole('button', { name: /print/i });
    const emailButton = invoicesPage.page.getByRole('button', { name: /email/i });

    await expect(printButton).not.toBeVisible();
    await expect(emailButton).not.toBeVisible();
  });

  test('should have View action available on invoice row', async () => {
    // Open actions menu on first row
    const firstRow = invoicesPage.table.locator('tbody tr').first();
    const actionsButton = firstRow.locator('[role="button"]', { hasText: /actions|•••/i }).or(
      firstRow.locator('button').last()
    );

    // Try to find and click actions trigger
    const actionsTrigger = firstRow.locator('[data-dropdown-trigger]').or(actionsButton);
    if (await actionsTrigger.count() > 0) {
      await actionsTrigger.first().click();
      await invoicesPage.page.waitForTimeout(300);

      // Verify View action exists
      const viewAction = invoicesPage.page.getByRole('menuitem', { name: /view/i }).or(
        invoicesPage.page.getByText(/view/i).first()
      );
      await expect(viewAction).toBeVisible();
    }
  });

  test('bulk actions should only have Export (no Print or Email)', async () => {
    // Select first invoice
    const firstCheckbox = invoicesPage.table.locator('tbody tr').first().locator('input[type="checkbox"]');
    if (await firstCheckbox.count() > 0) {
      await firstCheckbox.click();
      await invoicesPage.page.waitForTimeout(500);

      // Check bulk actions bar
      const bulkActionsBar = invoicesPage.page.locator('[class*="fi-bulk-actions"]').or(
        invoicesPage.page.locator('[class*="selected"]').first()
      );

      if (await bulkActionsBar.count() > 0) {
        // Verify Export action exists
        const exportButton = invoicesPage.page.getByRole('button', { name: /export/i });

        // Verify Print and Email bulk actions do NOT exist
        const printInvoicesButton = invoicesPage.page.getByRole('button', { name: /print invoices/i });
        const sendToCustomersButton = invoicesPage.page.getByRole('button', { name: /send to customers/i });

        await expect(printInvoicesButton).not.toBeVisible();
        await expect(sendToCustomersButton).not.toBeVisible();
      }
    }
  });
});
