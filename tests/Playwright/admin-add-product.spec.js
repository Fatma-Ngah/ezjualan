import { expect, test } from '@playwright/test';

test('admin can add an active product with variant stock', async ({ page }) => {
    const adminEmail = process.env.PLAYWRIGHT_ADMIN_EMAIL;
    const adminPassword = process.env.PLAYWRIGHT_ADMIN_PASSWORD;

    test.skip(!adminEmail || !adminPassword, 'Admin credentials are required.');

    const uniqueSuffix = `${Date.now()}-${test.info().parallelIndex}`;
    const productName = `Kemeja Playwright ${uniqueSuffix}`;
    const productSku = `PWMCP-${uniqueSuffix}`;
    const consoleErrors = [];

    page.on('console', (message) => {
        if (message.type() === 'error') {
            consoleErrors.push(message.text());
        }
    });

    await page.goto('/admin/produk');
    await expect(page).toHaveURL(/\/login$/);

    await page.getByRole('textbox', { name: 'Email address' }).fill(adminEmail);
    await page.getByRole('textbox', { name: 'Password' }).fill(adminPassword);
    await page.getByRole('button', { name: 'Log in' }).click();

    await expect(page).toHaveURL(/\/admin\/produk$/);
    await expect(page).toHaveTitle(/Urus Produk/);

    await page.getByRole('textbox', { name: 'Nama produk' }).fill(productName);
    await page.getByRole('spinbutton', { name: 'Harga (RM)' }).fill('79.90');
    await page.getByRole('textbox', { name: 'Penerangan' }).fill('Produk ujian automasi Playwright.');
    await page.getByRole('textbox', { name: 'Saiz' }).fill('M');
    await page.getByRole('textbox', { name: 'Warna' }).fill('Biru');
    await page.getByRole('textbox', { name: 'SKU' }).fill(productSku);
    await page.getByRole('spinbutton', { name: 'Stok' }).fill('25');
    await page.getByRole('button', { name: 'Tambah produk' }).click();

    const productCard = page
        .locator('div')
        .filter({ has: page.getByText(productName, { exact: true }) })
        .filter({ has: page.getByText('M / Biru: 25', { exact: true }) })
        .last();

    await expect(productCard).toContainText(productName);
    await expect(productCard).toContainText('Aktif');
    await expect(productCard).toContainText('RM 79.90');
    await expect(productCard).toContainText('25 jumlah stok · 1 variasi');
    await expect(productCard).toContainText('M / Biru: 25');
    expect(consoleErrors).toEqual([]);
});
