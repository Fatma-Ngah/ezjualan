# Panduan Ujian Pelayar E2E (Playwright)

Dokumen ini menerangkan cara menyediakan dan menjalankan ujian E2E (End-to-End) menggunakan Playwright bagi sistem EzJualan.

## 1. Keperluan Awal (Prerequisites)

Sebelum menjalankan ujian, pastikan anda telah memasang pakej Playwright dan pelayar (*browser*) Chromium ke dalam projek:

```bash
# Pasang Playwright Test runner
npm install -D @playwright/test

# Pasang pelayar Chromium
npx playwright install chromium
```

---

## 2. Arahan Menjalankan Ujian (Test Commands)

Terdapat beberapa cara untuk menjalankan ujian bergantung kepada keperluan anda:

### A. Menjalankan Ujian Secara Senyap (Headless Mode)
Ujian akan dijalankan di latar belakang tanpa membuka tetingkap pelayar (sangat sesuai untuk CI/CD atau ujian pantas):
```bash
npm run test:e2e
# atau
npx playwright test
```

### B. Menjalankan Ujian Secara Visual (Headed Mode)
Tetingkap pelayar Chromium fizikal akan dibuka pada skrin komputer anda dan anda boleh melihat langkah automasi berlaku secara langsung:
```bash
npx playwright test --headed
```

### C. Menjalankan Ujian Melalui Antaramuka Grafik (UI Mode)
Membuka dashboard interaktif Playwright untuk memilih ujian, melihat sejarah langkah demi langkah (*time travel debugging*), log konsol, dan tangkapan skrin bagi setiap baris kod:
```bash
npx playwright test --ui
```

---

## 3. Fail Ujian & Konfigurasi

* **Fail Ujian:** `tests/Playwright/admin-products.spec.js` - Menguji aliran log masuk admin, navigasi, muat naik gambar produk, pengisian maklumat variasi, dan pengesahan produk berjaya ditambah.
* **Fail Konfigurasi:** `playwright.config.js` - Konfigurasi utama seperti `baseURL` (`http://ezjualan.test`), direktori ujian, dan pelayar sasaran.

---

## 4. Skrip Ujian Manual (Untuk Manusia)

Berikut adalah senario dan langkah-langkah ujian yang boleh diikuti oleh penguji manusia secara manual untuk mengesahkan fungsi yang sama.

### Senario: Tambah Produk Baharu Dengan Gambar & Variasi

| Bil | Langkah Ujian | Jangkaan Hasil (Expected Result) | Status (Lulus/Gagal) |
|---|---|---|---|
| 1 | Buka pelayar dan layari halaman log masuk di `http://ezjualan.test/login`. | Halaman log masuk dipaparkan dengan medan Emel, Kata Laluan, dan butang "Log in". | |
| 2 | Masukkan emel `admin@ezjualan.test` dan kata laluan `password`. Klik butang **"Log in"**. | Halaman dialihkan ke Dashboard Admin dengan paparan ringkasan jualan. | |
| 3 | Klik menu **"Produk & Stok"** pada bar navigasi sebelah kiri. | Halaman dialihkan ke `/admin/produk` dengan tajuk **"Produk & stok"**. | |
| 4 | Isi butiran produk berikut:<br>- **Nama produk:** `Baju Kurung Melur`<br>- **Harga (RM):** `79.90`<br>- **Penerangan:** `Selesa dipasang setiap hari.` | Semua medan diisi dengan betul tanpa ralat paparan. | |
| 5 | Pada bahagian **"Gambar produk"**, klik butang **"Choose file"** dan pilih fail gambar (format JPG/PNG/WebP, saiz < 2MB). | Nama fail yang dipilih terpapar dan tiada mesej ralat merah ditunjukkan. | |
| 6 | Isi butiran pada **"Variasi produk"** pertama:<br>- **Saiz:** `M`<br>- **Warna:** `Hitam`<br>- **SKU:** `BKM-BLK-M`<br>- **Stok:** `12` | Semua maklumat variasi diisi dengan lengkap. | |
| 7 | Klik butang **"Tambah produk"** di bahagian bawah borang. | - Notifikasi hijau **"Produk berjaya ditambah"** muncul di skrin.<br>- Produk baharu disenaraikan dalam **"Senarai produk"** di bahagian bawah halaman. | |
