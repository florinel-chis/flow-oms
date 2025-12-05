import { test, expect } from '@playwright/test';

test.use({ storageState: 'tests/e2e/.auth/user.json' });

test('OMS Settings page basic load test', async ({ page }) => {
  await page.goto('http://127.0.0.1:8000/admin/demo/oms-settings');
  await page.waitForLoadState('networkidle');

  // Take a screenshot for debugging
  await page.screenshot({ path: 'test-results/oms-settings-debug.png', fullPage: true });

  // Get the page title
  const title = await page.title();
  console.log('Page title:', title);

  // Get all h1 and h2 elements
  const headings = await page.locator('h1, h2').allTextContents();
  console.log('Headings:', headings);

  // Check if there's an error page
  const errorHeading = await page.locator('h1:has-text("Error"), h1:has-text("Exception")').count();
  if (errorHeading > 0) {
    const errorText = await page.locator('p').first().textContent();
    console.log('ERROR DETECTED:', errorText);
  }

  // Expect no error
  expect(errorHeading).toBe(0);
});
