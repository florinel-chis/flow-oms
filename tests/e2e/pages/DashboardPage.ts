import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

/**
 * Dashboard Page Object
 */
export class DashboardPage extends BasePage {
  readonly heading: Locator;
  readonly statsCards: Locator;
  readonly widgets: Locator;

  constructor(page: Page) {
    super(page);
    this.heading = this.getHeading();
    this.statsCards = page.locator('[class*="fi-stats-card"]');
    // Filament 4 widgets use fi-wi-* classes or look for section elements
    this.widgets = page.locator('[class*="fi-section"]').or(page.locator('[class*="fi-wi"]'));
  }

  /**
   * Navigate to dashboard
   */
  async navigate(tenant: string = 'demo') {
    await this.goto('/', tenant);
  }

  /**
   * Verify page is loaded
   */
  async verify() {
    await expect(this.heading).toContainText(/dashboard/i);
    await this.waitForPageLoad();
  }

  /**
   * Get stat card count
   */
  async getStatCardCount(): Promise<number> {
    return await this.statsCards.count();
  }

  /**
   * Verify stat card exists with value
   */
  async expectStatCard(label: string) {
    const card = this.page.locator('[class*="fi-stats-card"]', { hasText: label });
    await expect(card).toBeVisible();
  }

  /**
   * Get widget count
   */
  async getWidgetCount(): Promise<number> {
    return await this.widgets.count();
  }
}
