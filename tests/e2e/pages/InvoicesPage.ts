import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

/**
 * Invoices Page Object
 */
export class InvoicesPage extends BasePage {
  readonly heading: Locator;
  readonly table: Locator;
  readonly stateFilter: Locator;
  readonly amountFilter: Locator;

  constructor(page: Page) {
    super(page);
    this.heading = this.getHeading();
    this.table = page.locator('table').first();
    this.stateFilter = page.getByLabel('State', { exact: true }).first();
    this.amountFilter = page.getByLabel(/amount/i).first();
  }

  /**
   * Navigate to invoices list
   */
  async navigate(tenant: string = 'demo') {
    await this.goto('/invoices', tenant);
  }

  /**
   * Verify page is loaded
   */
  async verify() {
    await expect(this.heading).toContainText(/invoices/i);
    await expect(this.table).toBeVisible();
  }

  /**
   * Filter by state
   */
  async filterByState(state: 'paid' | 'open' | 'canceled') {
    // Open filters panel first (Filament 4 may hide filters behind toggle)
    try {
      await this.openFilters();
      await this.page.waitForTimeout(300);
    } catch (e) {
      // Filters might already be open or not toggleable
    }

    // Filament 4 SelectFilter renders as native <select> by default
    // Look for select element with name containing 'state' or within a field wrapper labeled 'State'
    const stateSelect = this.page.locator('select').filter({
      has: this.page.locator('option', { hasText: /paid|open|canceled/i })
    }).first();

    await stateSelect.waitFor({ state: 'visible', timeout: 5000 });
    await stateSelect.selectOption({ label: state.charAt(0).toUpperCase() + state.slice(1) });
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Search for invoice
   */
  async searchInvoice(query: string) {
    await this.search(query);
  }

  /**
   * View invoice details
   */
  async viewInvoice(incrementId: string) {
    await this.clickTableRow(incrementId);
  }

  /**
   * Verify invoice exists
   */
  async expectInvoiceInTable(incrementId: string) {
    const row = this.table.getByRole('row', { name: new RegExp(incrementId, 'i') });
    await expect(row).toBeVisible();
  }

  /**
   * Get invoice count
   */
  async getInvoiceCount(): Promise<number> {
    const rows = await this.table.locator('tbody tr').count();
    return rows;
  }

  /**
   * View first invoice
   */
  async viewFirstInvoice() {
    await this.table.locator('tbody tr').first().click();
    await this.waitForPageLoad();
  }
}
