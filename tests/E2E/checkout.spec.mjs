import { expect, test } from '@playwright/test';

const userEmail = 'checkout.e2e@example.test';
const userPassword = 'Checkout#2026';
const productSlug = 'e2e-checkout-product';

test('khách hàng đã xác minh có thể đặt đơn COD hoàn chỉnh', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill(userEmail);
    await page.locator('#password').fill(userPassword);
    await page.getByRole('button', { name: 'Đăng nhập', exact: true }).click();
    await expect(page).toHaveURL(/\/$/);

    await page.goto(`/products/${productSlug}`);
    await expect(page.locator('h1')).toHaveText('Hộp trái cây kiểm thử checkout');
    await page.locator('form[data-cart-form]').getByRole('button', { name: 'Thêm vào giỏ' }).click();

    await page.goto('/cart');
    await expect(page.getByText('Hộp trái cây kiểm thử checkout')).toBeVisible();
    await expect(page.locator('.qty-form input[name="quantity"]')).toHaveValue('1');
    await page.getByRole('link', { name: 'Tiến hành checkout' }).click();
    await expect(page).toHaveURL(/\/checkout$/);

    await expect(page.locator('#customer_name')).toHaveValue('Khách hàng E2E');
    await expect(page.locator('#customer_phone')).toHaveValue('+84901234567');
    await expect(page.locator('#province_code')).toHaveValue('01');
    await expect.poll(async () => page.locator('#ward_code option').count()).toBeGreaterThan(1);
    await expect(page.locator('#ward_code')).toHaveValue('00004');
    await page.getByRole('button', { name: 'Tiếp tục đến phương thức thanh toán' }).click();
    await expect(page).toHaveURL(/\/checkout\/payment$/);

    const cod = page.locator('input[name="payment_method"][value="cod"]');
    const bankTransfer = page.locator('input[name="payment_method"][value="bank_transfer"]');
    const momo = page.locator('input[name="payment_method"][value="momo"]');
    await expect(cod).toBeChecked();
    await expect(bankTransfer).not.toBeChecked();
    await expect(momo).not.toBeChecked();

    await page.getByRole('button', { name: 'Đặt hàng', exact: true }).click();
    await expect(page).toHaveURL(/\/checkout\/thank-you\/DH/);
    await expect(page.getByText('Mã đơn hàng của bạn là')).toBeVisible();
    await expect(page.getByText('Hộp trái cây kiểm thử checkout')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Thanh toán khi nhận hàng' })).toBeVisible();

    await page.goto('/cart');
    await expect(page.getByText('Giỏ hàng đang trống')).toBeVisible();
});
