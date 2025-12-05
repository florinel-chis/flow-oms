import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

/**
 * Magento Stores Page Object
 */
export class MagentoStoresPage extends BasePage {
  readonly heading: Locator;
  readonly table: Locator;
  readonly newStoreButton: Locator;
  readonly activeFilter: Locator;
  readonly syncFilter: Locator;

  constructor(page: Page) {
    super(page);
    this.heading = this.getHeading();
    this.table = page.locator('table').first();
    this.newStoreButton = page.getByRole('link', { name: /new magento store/i }).first();
    this.activeFilter = page.getByLabel('Active', { exact: true }).first();
    this.syncFilter = page.getByLabel('Sync Enabled', { exact: true }).first();
  }

  /**
   * Navigate to magento stores list
   */
  async navigate(tenant: string = 'demo') {
    await this.goto('/magento-stores', tenant);
  }

  /**
   * Verify page is loaded
   */
  async verify() {
    await expect(this.heading).toContainText(/magento stores/i);
    await expect(this.table).toBeVisible();
  }

  /**
   * Click test connection button for a store
   */
  async testConnection(storeName: string) {
    const row = this.table.getByRole('row', { name: new RegExp(storeName, 'i') });
    await row.getByRole('button', { name: /test/i }).first().click();

    // Wait for confirmation modal
    await this.page.waitForTimeout(500);

    // Confirm
    await this.page.getByRole('button', { name: /confirm/i }).first().click();

    // Wait for result notification
    await this.page.waitForTimeout(1000);
  }

  /**
   * Click sync button for a store
   */
  async syncStore(storeName: string, days: number = 1, pageSize: number = 10) {
    const row = this.table.getByRole('row', { name: new RegExp(storeName, 'i') });
    await row.getByRole('button', { name: /sync/i }).first().click();

    // Fill form
    await this.fillField('days', days.toString());
    await this.fillField('page size', pageSize.toString());

    // Submit
    await this.clickButton('sync');

    // Wait for notification
    await this.expectNotification('queued', 'success');
  }

  /**
   * Edit store
   */
  async editStore(storeName: string) {
    const row = this.table.getByRole('row', { name: new RegExp(storeName, 'i') });
    await row.getByRole('button', { name: /edit/i }).first().click();
    await this.waitForPageLoad();
  }

  /**
   * Filter active stores
   */
  async filterActive(active: boolean) {
    // Open filters panel first (Filament 4 may hide filters behind toggle)
    try {
      await this.openFilters();
      await this.page.waitForTimeout(300);
    } catch (e) {
      // Filters might already be open or not toggleable
    }

    // Filament 4 SelectFilter renders as native <select> by default
    // Look for select element with Yes/No options (boolean filter)
    const activeSelect = this.page.locator('select').filter({
      has: this.page.locator('option', { hasText: /yes|no/i })
    }).first();

    await activeSelect.waitFor({ state: 'visible', timeout: 5000 });
    const optionText = active ? 'Yes' : 'No';
    await activeSelect.selectOption({ label: optionText });
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Get store count
   */
  async getStoreCount(): Promise<number> {
    const rows = await this.table.locator('tbody tr').count();
    return rows;
  }

  /**
   * Verify store exists
   */
  async expectStoreInTable(storeName: string) {
    const row = this.table.getByRole('row', { name: new RegExp(storeName, 'i') });
    await expect(row).toBeVisible();
  }
}
