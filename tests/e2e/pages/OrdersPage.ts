import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

/**
 * Orders Page Object
 */
export class OrdersPage extends BasePage {
  readonly heading: Locator;
  readonly createButton: Locator;
  readonly table: Locator;
  readonly searchInput: Locator;
  readonly filterButtons: Locator;

  constructor(page: Page) {
    super(page);
    this.heading = this.getHeading();
    this.createButton = page.getByRole('button', { name: /new order/i }).first();
    this.table = page.locator('table').first();
    this.searchInput = page.getByPlaceholder(/search/i).first();
    this.filterButtons = page.locator('[role="button"]').filter({ hasText: /filter/i });
  }

  /**
   * Navigate to orders list
   */
  async navigate(tenant: string = 'demo') {
    await this.goto('/orders', tenant);
  }

  /**
   * Verify page is loaded
   */
  async verify() {
    await expect(this.heading).toContainText(/orders/i);
    await expect(this.table).toBeVisible();
  }

  /**
   * Search for an order
   */
  async searchOrder(query: string) {
    await this.search(query);
    await this.page.waitForTimeout(500); // Wait for table to update
  }

  /**
   * Filter by status
   */
  async filterByStatus(status: string) {
    // Open filters panel first (Filament 4 may hide filters behind toggle)
    try {
      await this.openFilters();
      await this.page.waitForTimeout(300);
    } catch (e) {
      // Filters might already be open or not toggleable
    }

    // Filament 4 SelectFilter renders as native <select> by default
    // Look for select element with options matching order statuses
    const statusSelect = this.page.locator('select').filter({
      has: this.page.locator('option', { hasText: /pending|processing|complete|canceled|holded/i })
    }).first();

    await statusSelect.waitFor({ state: 'visible', timeout: 5000 });
    await statusSelect.selectOption({ label: status.charAt(0).toUpperCase() + status.slice(1) });
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * View order details
   */
  async viewOrder(incrementId: string) {
    await this.clickTableRow(incrementId);
  }

  /**
   * Get order count from table
   */
  async getOrderCount(): Promise<number> {
    const rows = await this.table.locator('tbody tr').count();
    return rows;
  }

  /**
   * Verify order exists in table
   */
  async expectOrderInTable(incrementId: string) {
    const row = this.table.getByRole('row', { name: new RegExp(incrementId, 'i') });
    await expect(row).toBeVisible();
  }

  /**
   * Click on first order in table
   */
  async viewFirstOrder() {
    await this.table.locator('tbody tr').first().click();
    await this.waitForPageLoad();
  }
}
