import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('hộp hỗ trợ trả lời FAQ và điều hướng đúng trên desktop', async ({ page }, testInfo) => {
    await page.goto('/');
    await page.evaluate(() => window.scrollTo(0, 500));

    const toggle = page.getByRole('button', { name: 'Mở hỗ trợ mua hàng' });
    const widget = page.getByRole('dialog', { name: 'Hỗ trợ mua hàng' });

    await expect(toggle).toBeVisible();
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(widget).toBeVisible();

    await widget.getByRole('button', { name: 'Theo dõi đơn' }).click();
    await expect(widget.getByText('Theo dõi đơn hàng', { exact: true })).toBeVisible();
    await expect(widget.getByRole('link', { name: 'Đăng nhập để xem đơn' })).toHaveAttribute('href', /login$/);

    const accessibility = await new AxeBuilder({ page })
        .include('#supportWidget')
        .analyze();
    const seriousViolations = accessibility.violations.filter((violation) =>
        ['serious', 'critical'].includes(violation.impact)
    );
    expect(seriousViolations).toEqual([]);

    await page.screenshot({ path: testInfo.outputPath('support-desktop.png') });
    await page.keyboard.press('Escape');
    await expect(widget).toBeHidden();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
});

test.describe('mobile', () => {
    test.use({ viewport: { width: 390, height: 844 } });

    test('chỉ giữ nút hỗ trợ và hộp thoại nằm trọn trong màn hình', async ({ page }, testInfo) => {
        await page.goto('/');

        const toggle = page.getByRole('button', { name: 'Mở hỗ trợ mua hàng' });
        await expect(toggle).toBeVisible();
        await expect(page.getByRole('link', { name: 'Gọi hotline' })).toBeHidden();

        await toggle.click();
        const widget = page.getByRole('dialog', { name: 'Hỗ trợ mua hàng' });
        await expect(widget).toBeVisible();

        const box = await widget.boundingBox();
        expect(box).not.toBeNull();
        expect(box.x).toBeGreaterThanOrEqual(0);
        expect(box.y).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width).toBeLessThanOrEqual(390);
        expect(box.y + box.height).toBeLessThanOrEqual(844);

        await widget.getByRole('button', { name: 'Phí giao hàng' }).click();
        await expect(widget.getByRole('link', { name: 'Xem chính sách giao hàng' })).toBeVisible();
        await page.screenshot({ path: testInfo.outputPath('support-mobile.png') });
    });
});
