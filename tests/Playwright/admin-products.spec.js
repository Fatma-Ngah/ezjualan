import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const dummyImagePath = path.join(__dirname, 'dummy-product.png');

test.describe('Pengurusan Produk (E2E)', () => {
    // Bina fail gambar dummy sebelum ujian dijalankan
    test.beforeAll(() => {
        fs.writeFileSync(dummyImagePath, 'PNG dummy content');
    });

    // Padam fail gambar dummy selepas ujian selesai
    test.afterAll(() => {
        if (fs.existsSync(dummyImagePath)) {
            fs.unlinkSync(dummyImagePath);
        }
    });

    test('boleh log masuk dan menambah produk baharu dengan gambar dan variasi', async ({ page }) => {
        // 1. Pergi ke halaman log masuk
        await page.goto('/login');
        await expect(page).toHaveTitle(/Log in/);

        // 2. Isi maklumat log masuk
        await page.getByRole('textbox', { name: 'Email address' }).fill('admin@ezjualan.test');
        await page.getByRole('textbox', { name: 'Password' }).fill('password');
        await page.getByRole('button', { name: 'Log in' }).click();

        // 3. Pastikan berjaya masuk ke Dashboard
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.locator('body')).toContainText('Ringkasan jualan');

        // 4. Pergi ke halaman Urus Produk
        await page.getByRole('link', { name: 'Produk & Stok' }).click();
        await expect(page).toHaveTitle(/Urus Produk/);
        await expect(page.locator('body')).toContainText('Produk & stok');

        // 5. Isi maklumat produk baharu
        const randomSuffix = Math.floor(Math.random() * 1000000);
        const productName = `T-Shirt Playwright Test ${randomSuffix}`;
        const productSku = `TS-PLW-RED-${randomSuffix}`;

        await page.getByRole('textbox', { name: 'Nama produk' }).fill(productName);
        await page.getByRole('spinbutton', { name: 'Harga (RM)' }).fill('45.00');
        await page.getByRole('textbox', { name: 'Penerangan' }).fill('Ini adalah produk demo untuk ujian muat naik gambar menggunakan Playwright.');

        // 6. Muat naik gambar produk
        await page.setInputFiles('input[type="file"]', dummyImagePath);

        // Pastikan nama fail terpapar pada input
        await expect(page.locator('body')).toContainText('dummy-product.png');

        // 7. Isi variasi produk
        await page.getByRole('textbox', { name: 'Saiz' }).fill('L');
        await page.getByRole('textbox', { name: 'Warna' }).fill('Merah');
        await page.getByRole('textbox', { name: 'SKU' }).fill(productSku);
        await page.getByRole('spinbutton', { name: 'Stok' }).fill('50');

        // 8. Hantar borang tambah produk
        await page.getByRole('button', { name: 'Tambah produk' }).click();

        // 9. Sahkan mesej kejayaan dan produk tersenarai
        await expect(page.locator('body')).toContainText('Produk berjaya ditambah.');
        await expect(page.locator('body')).toContainText(productName);
    });
});
