# Laporan Praktikum Minggu 9: Pengujian API (SOAP & REST)
**Mata Kuliah:** Pengembangan Perangkat Web (PPW)  
**NIM:** 12S24031  
**Nama:** Amelia Renata Lumbanbatu  
**Proyek:** Sistem Informasi Perpustakaan (SIPUS) - `ppw-2026-week5-12S24031`

---

## 📌 Ringkasan Implementasi

Pada praktikum minggu ke-9 ini, dilakukan pengintegrasian dan pengujian dua jenis arsitektur *Application Programming Interface* (API) pada proyek Laravel 11:
1. **SOAP API (`/soap/sipus`)**: Digunakan untuk pengecekan status clearance (bebas pinjaman/denda) peminjam perpustakaan berbasis XML.
2. **RESTful API (`/api/v1/*`)**: Digunakan untuk autentikasi token, publikasi katalog buku publik dengan dukungan format HATEOAS (`_links`), serta penambahan data buku baru secara privat.

---

## 🧪 Dokumentasi & Hasil Pengujian API

Seluruh pengujian dilakukan menggunakan berkas HTTP Client `test-api.http` di Visual Studio Code.

### 1. Pengujian SOAP Clearance Status Status
* **Endpoint:** `POST http://localhost:8000/soap/sipus`
* **Format:** XML (SOAP Envelope)
* **Status HTTP:** `200 OK`
* **Deskripsi:** Memeriksa status kebebasan denda/pinjaman mahasiswa berdasarkan NIM.

![01 SOAP Clearance Status](docs/screenshots/01-soap-clearance.png)

---

### 2. Pengujian REST API: Authenticated Login
* **Endpoint:** `POST http://localhost:8000/api/v1/auth/login`
* **Format:** JSON
* **Status HTTP:** `200 OK`
* **Deskripsi:** Menghasilkan `access_token` (Bearer Token) untuk digunakan pada endpoint REST API yang membutuhkan otorisasi.

![02 REST Auth Login](docs/screenshots/02-rest-login.png)

---

### 3. Pengujian REST API: Katalog Buku (Public + HATEOAS)
* **Endpoint:** `GET http://localhost:8000/api/v1/bukus?per_page=3`
* **Format:** JSON
* **Status HTTP:** `200 OK`
* **Deskripsi:** Mengembalikan daftar katalog buku publik beserta tautan rujukan navigasi HATEOAS (`_links`).

![03 REST Katalog Buku](docs/screenshots/03-rest-katalog.png)

---

### 4. Pengujian REST API: Tambah Buku Baru (Private)
* **Endpoint:** `POST http://localhost:8000/api/v1/bukus`
* **Header:** `Authorization: Bearer <access_token>`
* **Status HTTP:** `201 Created`
* **Deskripsi:** Menyimpan data buku baru ke dalam katalog menggunakan otorisasi token.

![04 REST Tambah Buku](docs/screenshots/04-rest-tambah-buku.png)

---

## 🚀 Perintah Menjalankan Server

1. **Jalankan Migrasi Database:**
   ```bash
   php artisan migrate