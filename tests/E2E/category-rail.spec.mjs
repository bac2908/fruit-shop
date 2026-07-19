import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('quick category rail appears after scrolling and keeps category navigation accessible', async ({ page }, testInfo) => {
    await page.goto('/');

    const rail = page.locator('[data-category-rail]');
    await expect(rail).toBeHidden();

    await page.evaluate(() => window.scrollTo(0, 500));
    await expect(rail).toBeVisible();
    await expect(rail.getByRole('link')).toHaveCount(7);

    const vietnameseFruit = rail.getByRole('link', { name: 'Trái cây Việt Nam' });
    await vietnameseFruit.hover();
    await expect(vietnameseFruit.locator('.tgc-category-rail-label')).toBeVisible();

    const box = await rail.boundingBox();
    expect(box).not.toBeNull();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(60);

    const accessibility = await new AxeBuilder({ page })
        .include('[data-category-rail]')
        .analyze();
    const seriousViolations = accessibility.violations.filter((violation) =>
        ['serious', 'critical'].includes(violation.impact)
    );
    expect(seriousViolations).toEqual([]);

    await page.screenshot({ path: testInfo.outputPath('category-rail-desktop.png') });
    await vietnameseFruit.click();
    await expect(page).toHaveURL(/\/collections\/trai-cay-viet-nam$/);

    await page.evaluate(() => window.scrollTo(0, 500));
    await expect(page.locator('[data-category-rail] [aria-current="page"]')).toHaveAttribute(
        'aria-label',
        'Trái cây Việt Nam'
    );
});

test.describe('mobile category rail', () => {
    test.use({ viewport: { width: 390, height: 844 } });

    test('stays hidden so it does not cover storefront content', async ({ page }) => {
        await page.goto('/');
        await page.evaluate(() => window.scrollTo(0, 500));
        await expect(page.locator('[data-category-rail]')).toBeHidden();
    });
});
