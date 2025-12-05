import { Page, Locator, expect } from '@playwright/test';

/**
 * Base Page Object
 *
 * Contains common methods and selectors shared across all pages
 */
export class BasePage {
  readonly page: Page;
  readonly navigation: Locator;
  readonly sidebar: Locator;
  readonly userMenu: Locator;

  constructor(page: Page) {
    this.page = page;
    this.navigation = page.locator('nav').first();
    this.sidebar = page.locator('aside').first();
    this.userMenu = page.locator('[x-data]').filter({ hasText: 'Open user menu' });
  }

  /**
   * Navigate to a specific tenant path
   */
  async goto(path: string, tenant: string = 'demo') {
    const fullPath = `/admin/${tenant}${path}`;
    await this.page.goto(fullPath);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Wait for page to be ready
   */
  async waitForPageLoad() {
    await this.page.waitForLoadState('networkidle');
    // Don't check for navigation - it may not be present on all pages (e.g., detail views)
  }

  /**
   * Click a navigation menu item
   */
  async clickNavigationItem(label: string) {
    await this.sidebar.getByRole('link', { name: label }).click();
    await this.waitForPageLoad();
  }

  /**
   * Open table filters panel (Filament 4 hides filters behind icon)
   */
  async openFilters() {
    // Look for filter toggle button (funnel icon) - try multiple selectors
    const filterButton = this.page.getByRole('button', { name: /filter/i })
      .or(this.page.locator('button[title*="Filter"]'))
      .or(this.page.locator('[data-toggle="filters"]'))
      .first();

    await filterButton.click();
    await this.page.waitForTimeout(300); // Wait for filter panel animation
  }

  /**
   * Search in table/list
   */
  async search(query: string) {
    const searchInput = this.page.getByPlaceholder(/search/i).first();
    await searchInput.clear();
    await searchInput.fill(query);
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Click on a table row by text
   */
  async clickTableRow(text: string) {
    await this.page.getByRole('row', { name: new RegExp(text, 'i') }).first().click();
    await this.waitForPageLoad();
  }

  /**
   * Get page heading
   */
  getHeading() {
    return this.page.getByRole('heading', { level: 1 }).first();
  }

  /**
   * Check if notification is visible
   */
  async expectNotification(message: string, type: 'success' | 'error' | 'info' = 'success') {
    const notification = this.page.locator('[role="alert"]', { hasText: message });
    await expect(notification).toBeVisible();
  }

  /**
   * Click a button by label
   */
  async clickButton(label: string) {
    await this.page.getByRole('button', { name: new RegExp(label, 'i') }).first().click();
  }

  /**
   * Fill a form field by label
   */
  async fillField(label: string, value: string) {
    const field = this.page.getByLabel(new RegExp(label, 'i')).first();
    await field.clear();
    await field.fill(value);
  }

  /**
   * Select from dropdown by label
   */
  async selectOption(label: string, option: string) {
    await this.page.getByLabel(new RegExp(label, 'i')).first().selectOption(option);
  }

  /**
   * Take a screenshot for debugging
   */
  async screenshot(name: string) {
    await this.page.screenshot({ path: `test-results/screenshots/${name}.png`, fullPage: true });
  }
}
