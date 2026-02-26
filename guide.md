'
```markdown
# PANDUAN PENGEMBANGAN BACK-END & API SISTEM INFORMASI PEMANCINGAN S

**Konteks Proyek:**
Dokumen ini adalah panduan teknis bagi AI Agent / Developer untuk membangun *back-end* Sistem Informasi Pemancingan S. Sistem ini mendigitalisasi operasional transaksi, pengelolaan stok, sistem keanggotaan (membership), dan pelaporan keuangan. Pengembangan menggunakan metodologi *Rapid Application Development* (RAD).

**Tech Stack yang Diwajibkan:**
*   **Framework:** Laravel (menggunakan arsitektur MVC dan REST API).
*   **Database:** MySQL (Relational Database Management System).
*   **ORM:** Eloquent ORM (untuk interaksi *database* berorientasi objek).

---

## 1. STRUKTUR DATABASE & ENTITAS (MIGRATIONS)
Buatkan *migration* dan *schema database* untuk mengakomodasi entitas berikut:
*   `users`: Manajemen akun dengan *role* (Owner, Pegawai, Member). Atribut: Nama, No HP, Email, Alamat, Status Validasi (Menunggu, Aktif, Ditolak), Member ID.
*   `guest_sessions`: Manajemen sesi pengunjung (*Guest*). Atribut: `guest_id`, `deposit_amount` (default 50.000), `session_start`, `session_end`, `status` (aktif/non-aktif).
*   `memberships`: Mencatat tier, total poin, dan ID *user*.
*   `leaderboards`: Mencatat akumulasi berat ikan sepanjang masa (tidak di-reset).
*   `vouchers`: Mencatat perolehan voucher bulanan Top 1, 2, 3.
*   `transactions` & `transaction_details`: Menyimpan master transaksi dan detail item (Ikan, F&B, Sewa Alat). Atribut tambahan di master: Subtotal, Diskon Tier, Diskon Voucher, Total Bayar, Tips, Status Pembayaran (PENDING/LUNAS).
*   `fish_stocks` & `fish_types`: Mencatat jenis ikan, harga per kg, dan jumlah stok fisik (kg).
*   `menus`: Mencatat daftar makanan/minuman, harga, dan ketersediaan.
*   `events`: Menyimpan data pengumuman/event (Judul, deskripsi, tanggal, status publikasi).

---

## 2. LOGIKA BISNIS UTAMA (CORE BUSINESS LOGIC)

### 2.1 Sistem Membership, Diskon & Poin
*   **Tier & Diskon (HANYA BERLAKU UNTUK IKAN):**
    *   **REGULAR:** 0–49 Poin (Diskon 0%).
    *   **BRONZE:** 50–399 Poin (Diskon 1%).
    *   **SILVER:** 400–799 Poin (Diskon 3%).
    *   **GOLD:** 800+ Poin (Diskon 5%).
    *   *Pengecualian:* Makanan, minuman, dan sewa alat tidak mendapat diskon tier.
*   **Perhitungan Poin:** `Poin = floor(Total Harga Asli Sebelum Diskon / 10.000)`. Poin dihitung dari SEMUA item (Ikan + F&B + Sewa) saat status menjadi LUNAS.
*   **Upgrade & Downgrade Tier:**
    *   *Upgrade:* Otomatis naik tier jika poin mencapai *threshold*.
    *   *Downgrade:* Jika tidak ada transaksi selama 180 hari berturut-turut, kurangi 10 poin per hari (dimulai pada hari ke-181) menggunakan *Cron Job*.

### 2.2 Sistem Leaderboard & Voucher Bulanan
*   *Leaderboard* diurutkan berdasarkan akumulasi berat ikan (kg) tertinggi sepanjang masa.
*   **Voucher Bulanan:** Pada akhir bulan, berikan voucher diskon (Top 1: Rp100.000, Top 2: Rp50.000, Top 3: Rp20.000).
*   Voucher otomatis terpakai (*auto-redeem*) saat pelanggan *checkout*.

---

## 3. ALUR OPERASIONAL (ENDPOINTS API YANG DIBUTUHKAN)

### 3.1 Registrasi & Validasi Member
*   Pendaftaran member baru berstatus **"Menunggu Validasi"**.
*   Buat API untuk Owner melakukan *Approval*. Jika disetujui -> Status **"Aktif"**, *generate* Member ID, berikan QR Code, set tier REGULAR, dan kirim notifikasi. Jika ditolak -> berikan alasan.

### 3.2 Kedatangan Pengunjung (Guest Flow)
*   **Endpoint Aktivasi Guest:** Saat *guest* datang, catat pembayaran deposit Rp50.000. Aktifkan ID kartu QR fisik menjadi sesi aktif untuk login ke web.
*   Selama sesi aktif, *guest* dapat memesan F&B. *Guest* tidak dicatat di *leaderboard*.

### 3.3 Pemesanan F&B & Sewa Alat
*   Buat API `create_order` untuk pesanan F&B mandiri (via web) atau dibantu pegawai. Status transaksi harus **"PENDING"** (Belum Bayar).
*   Sewa alat pancing: Rp10.000/hari. Denda: Kail (Rp2.000), Pelampung (Rp5.000), Total (Rp200.000). Denda masuk sebagai item transaksi tambahan.

### 3.4 Proses Checkout Utama (Krusial - Wajib Database Transaction)
Buat API `process_checkout` yang menangani kalkulasi kompleks secara atomik (ACID):
1.  Ambil berat ikan, kalikan dengan harga per kg.
2.  Tarik transaksi **PENDING** (F&B, Sewa).
3.  Hitung diskon tier **HANYA** dari subtotal ikan.
4.  Gunakan nilai Voucher secara *auto-redeem* (jika punya).
5.  *Khusus Guest:* Hitung `Total Tagihan - Deposit Rp50.000`. Jika tagihan < deposit, set nilai *refund* (kembalian uang). Jika > deposit, set nilai kurang bayar.
6.  *Update Database:* Ubah status -> **LUNAS**.
7.  Hitung dan tambahkan poin *member* berdasarkan harga asli.
8.  Tambahkan berat ikan ke *Leaderboard* dan cek perubahan *ranking*.
9.  Kurangi stok ikan.
10. Selesaikan sesi QR *guest*, kembalikan status QR menjadi tidak aktif.

### 3.5 Pengelolaan Stok & Notifikasi
*   Setiap *checkout* mengurangi stok. Jika stok turun di bawah *threshold* (Patin <50kg, Gurame <10kg, Nila <25kg, Bawal <5kg), kirim *Alert* ke Owner.
*   Buat API `restock_fish` untuk Owner. Saat *restock* berhasil, otomatis *broadcast* notifikasi ke semua member (contoh: "Ikan Baru Di-Restock!").
*   Buat API `publish_event` yang jika statusnya "Dipublikasikan", otomatis memicu notifikasi *broadcast*.

### 3.6 Laporan Keuangan
*   Buat API `get_financial_report` yang menggabungkan total transaksi, diskon, pendapatan bersih, kategori item, dan *tips* pegawai.
*   Sediakan opsi ekspor ke CSV dengan format kolom: `Tanggal, No_Transaksi, Member, Item, Qty, Harga, Diskon, Total, Metode_Bayar, Tips, Poin`.

---

## 4. INSTRUKSI PENGUJIAN OTOMATIS (TESTING)
AI Agent wajib membuat *script Black Box Testing* (PHPUnit / Laravel Feature Test) untuk memvalidasi alur berikut:
1.  **Test Kalkulasi Diskon:** Pastikan diskon tier hanya mengurangi harga ikan, sementara F&B tetap harga normal.
2.  **Test Kalkulasi Poin:** Pastikan hitungan poin berbasis fungsi `floor(Total Harga Asli / 10000)` dan tidak terpengaruh diskon.
3.  **Test Guest Checkout:** Pastikan logika deposit guest mengurangi total bayar dan sesi *guest* otomatis tertutup.
4.  **Test Pengurangan Stok & Alert:** Pastikan *checkout* ikan mengurangi stok dan fungsi *alert* terpanggil saat stok di bawah batas minimal.
5.  **Test Cron Job Downgrade Point:** Simulasikan *user* tidak bertransaksi 181 hari, pastikan poin berkurang 10 dan tier turun jika poin di bawah ambang batas.

**Prioritaskan *clean code*, penggunaan *Form Requests* untuk validasi *input*, dan implementasi REST API *Resources* untuk *formatting response* JSON.**
```
'