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

DOKUMEN KONDISI OPERASIONAL YANG DIUSULKAN (TO-BE)
SISTEM INFORMASI PEMANCINGAN S
________________________________________
1. OVERVIEW SISTEM MEMBERSHIP
1.1 Klasifikasi Tier Keanggotaan
Sistem membership menggunakan klasifikasi tier berdasarkan akumulasi poin yang diperoleh pelanggan dari seluruh transaksi yang telah dilakukan. Klasifikasi tier keanggotaan adalah sebagai berikut:
Tier	Rentang Poin	Ekuivalen Transaksi	Diskon Tier
REGULAR	0 – 49	Rp0 – Rp490.000	0%
BRONZE	50 – 399	Rp500.000 – Rp3.990.000	1%
SILVER	400 – 799	Rp4.000.000 – Rp7.990.000	3%
GOLD	800+	Rp8.000.000+	5%
Catatan Penting:
•	Diskon tier keanggotaan (Bronze, Silver, Gold) hanya berlaku untuk transaksi pembelian ikan.
•	Diskon tier tidak berlaku untuk pembelian makanan, minuman, maupun sewa alat pancing.
1.2 Benefit Keanggotaan per Tier
•	REGULAR: Status keanggotaan dasar (tanpa diskon)
•	BRONZE: Diskon 1% untuk pembelian ikan + Badge Bronze
•	SILVER: Diskon 3% untuk pembelian ikan + Badge Silver
•	GOLD: Diskon 5% untuk pembelian ikan + Badge Gold 
1.3 Sistem Leaderboard dan Voucher Bulanan
Sistem leaderboard menyusun peringkat pelanggan berdasarkan total berat ikan yang berhasil ditangkap secara akumulatif sejak pelanggan pertama kali menjadi member. Sistem leaderboard memiliki karakteristik sebagai berikut:
•	Akumulasi Berat Ikan: Tidak ada reset; leaderboard terakumulasi sepanjang masa sejak pelanggan pertama kali melakukan transaksi pembelian ikan
•	Pembaruan Peringkat: leaderboard menampilkan peringkat pelanggan secara real-time berdasarkan akumulasi berat ikan dari transaksi yang tercatat di sistem. 
•	Penentuan Pemenang: Dilakukan pada akhir periode bulanan (tanggal terakhir bulan berjalan)
•	Distribusi Hadiah: Voucher potongan harga diberikan pada awal bulan berikutnya kepada pelanggan yang menempati peringkat Top 1, Top 2, dan Top 3 pada akhir periode bulanan
Nilai Voucher Bulanan:
•	Top 1: Voucher potongan harga senilai Rp100.000
•	Top 2: Voucher potongan harga senilai Rp50.000
•	Top 3: Voucher potongan harga senilai Rp20.000
Mekanisme Penggunaan Voucher:
•	Voucher bersifat otomatis terpakai (auto-redeem) pada saat pelanggan melakukan checkout pembelian ikan
•	Apabila nilai transaksi checkout lebih kecil dari nilai voucher, maka seluruh nilai voucher dianggap terpakai dan tidak dapat disimpan atau diakumulasi untuk transaksi berikutnya
•	Apabila nilai transaksi checkout melebihi nilai voucher, maka pelanggan wajib melakukan pembayaran tambahan sebesar selisih dari nilai transaksi tersebut
Catatan:
•	Leaderboard dapat diakses dan dilihat oleh seluruh pengguna (termasuk guest), namun hanya akun pelanggan dengan status member yang dapat tercatat dan mengisi peringkat leaderboard
•	Akun dengan status guest tidak dicatat dalam sistem leaderboard dan tidak memiliki riwayat transaksi jangka panjang
1.4 Mekanisme Perhitungan Poin
Setiap transaksi yang berstatus lunas akan menambahkan poin kepada akun pelanggan. Perhitungan poin dilakukan berdasarkan rumus berikut:
Rumus Perhitungan:
Poin = Harga ASLI (sebelum diskon) ÷ Rp10.000
(dibulatkan ke bawah)
Contoh Perhitungan:
Kasus 1: Member REGULAR (Tanpa Diskon)
•	Harga ikan asli: Rp75.000
•	Diskon tier (0%): Rp0
•	Total bayar: Rp75.000
•	Poin yang didapat: Rp75.000 ÷ Rp10.000 = 7 poin
Kasus 2: Member SILVER (Diskon 3%)
•	Harga ikan asli: Rp100.000
•	Diskon tier (3%): -Rp3.000
•	Total bayar: Rp97.000
•	Poin yang didapat: Rp100.000 ÷ Rp10.000 = 10 poin 
o	(Poin dihitung dari harga ASLI, bukan setelah diskon)
Karakteristik Poin:
•	Poin bersifat akumulatif (tidak hangus kecuali terkena downgrade otomatis)
•	Poin bertambah pada saat transaksi berstatus LUNAS
•	Poin diperoleh dari semua jenis transaksi (ikan + makanan/minuman + sewa alat)
________________________________________
2. ALUR PENDAFTARAN PELANGGAN
2.1 Pendaftaran Mandiri via Website
Alur Pendaftaran:
1.	Pelanggan mengakses website Pemancingan S
2.	Pelanggan memilih menu "Daftar"
3.	Pelanggan mengisi formulir pendaftaran dengan data berikut: 
o	Nama lengkap
o	Nomor HP
o	Email (opsional)
o	Alamat
o	Password
4.	Pelanggan menekan tombol "Daftar"
5.	Sistem melakukan validasi data yang diinput
6.	Sistem mencatat akun pelanggan ke dalam database
Status Akun Setelah Pendaftaran:
•	Akun pelanggan berhasil dibuat dengan status "Menunggu Validasi"
•	Pelanggan belum dianggap sebagai member reguler dan belum memperoleh hak atau benefit keanggotaan
•	Pelanggan dapat melakukan login ke website, namun belum dapat mengakses fitur member (QR Code, poin, tier, voucher)
2.2 Pendaftaran Dibantu Pegawai Operasional di Tempat
Alur Pendaftaran:
1.	Pelanggan datang ke pemancingan dan menyampaikan keinginan untuk mendaftar sebagai member
2.	Pegawai operasional membuka formulir pendaftaran di sistem
3.	Pegawai operasional menanyakan data pelanggan: 
o	Nama lengkap
o	Nomor HP
o	Email (opsional)
o	Alamat
4.	Pegawai operasional melakukan input data ke dalam sistem
5.	Pegawai operasional menekan tombol "Simpan"
Status Akun Setelah Pendaftaran:
•	Sama seperti pendaftaran mandiri via website, akun berstatus "Menunggu Validasi"
•	Pelanggan belum dapat menggunakan benefit keanggotaan hingga pemilik menyetujui akun tersebut
Informasi yang Diberikan Pegawai kepada Pelanggan:
•	Pegawai menjelaskan bahwa akun akan divalidasi oleh pemilik sebelum dapat digunakan
•	Setelah akun disetujui, pelanggan dapat melakukan login ke website untuk mengakses QR Code dan fitur member lainnya
________________________________________
3. ALUR VALIDASI DAN AKTIVASI AKUN PELANGGAN OLEH PEMILIK
3.1 Notifikasi Pendaftaran Akun Baru
Setiap kali terdapat pendaftaran akun baru (baik melalui website maupun dibantu pegawai operasional), sistem secara otomatis:
•	Mencatat akun baru dengan status "Menunggu Validasi"
•	Menampilkan notifikasi kepada pemilik bahwa terdapat akun baru yang perlu divalidasi
•	Menambahkan akun baru ke dalam daftar "Akun Menunggu Validasi" di dashboard pemilik
3.2 Akses Daftar Akun Menunggu Validasi
Alur Proses:
1.	Pemilik melakukan login ke sistem melalui perangkat pribadi (dapat dilakukan dari mana saja, tidak harus berada di lokasi pemancingan)
2.	Pemilik membuka menu "Kelola Akun Pelanggan"
3.	Sistem menampilkan daftar akun yang berstatus "Menunggu Validasi" beserta informasi berikut: 
o	Nama lengkap calon member
o	Nomor HP
o	Email (jika diisi)
o	Alamat
o	Tanggal pendaftaran
3.3 Proses Pengecekan dan Persetujuan Akun
Alur Proses:
1.	Pemilik melakukan pengecekan kelengkapan dan keabsahan data calon member
2.	Pemilik dapat melakukan salah satu dari tiga aksi berikut: 
o	Setujui: Apabila data sudah sesuai dan lengkap
o	Tolak: Apabila data tidak sesuai atau tidak lengkap
Apabila Pemilik Memilih "Setujui": 3. Pemilik menekan tombol "Setujui Akun" 4. Sistem secara otomatis:
•	Mengubah status akun dari "Menunggu Validasi" menjadi "Aktif"
•	Memberikan ID Member unik (contoh: MBR-001)
•	Mengaktifkan QR Code yang dapat diakses melalui menu "Profil Saya" di website
•	Menetapkan tier awal: REGULAR (0 poin)
•	Mengirimkan notifikasi kepada pelanggan bahwa akun telah disetujui dan dapat digunakan
5.	Pelanggan dapat segera menggunakan seluruh fitur member (QR Code, akses leaderboard, benefit keanggotaan)
Apabila Pemilik Memilih "Tolak": 3. Pemilik menekan tombol "Tolak Akun" 4. Pemilik dapat (opsional) memberikan alasan penolakan 5. Sistem secara otomatis:
•	Mengubah status akun menjadi "Ditolak"
•	Mengirimkan notifikasi kepada pelanggan bahwa akun tidak dapat disetujui (beserta alasan, jika ada)
•	Akun tidak dapat login atau menggunakan fitur member
Apabila Pemilik Memilih "Tunda": 3. Pemilik dapat menunda keputusan untuk validasi di waktu yang lebih sesuai 4. Akun tetap berstatus "Menunggu Validasi" 5. Pemilik dapat kembali memproses akun tersebut kapan saja
3.4 Riwayat Validasi Akun
Sistem menyimpan riwayat seluruh proses validasi akun yang dilakukan oleh pemilik, meliputi:
•	Akun yang disetujui beserta tanggal dan waktu persetujuan
•	Akun yang ditolak beserta alasan penolakan (jika ada)
•	Akun yang masih menunggu validasi
Riwayat ini dapat diakses oleh pemilik untuk keperluan audit atau monitoring.
________________________________________
4. ALUR KEDATANGAN PENGUNJUNG
4.1 Alur Kedatangan Pengunjung (Guest)
Kondisi: Pengunjung yang belum terdaftar atau belum memiliki status member yang valid.
Alur Proses:
1.	Pengunjung (guest) menyampaikan kepada pegawai operasional bahwa ingin melakukan aktivitas memancing
2.	Pegawai operasional melakukan pencatatan kedatangan pengunjung dan meminta pengunjung melakukan pembayaran deposit sebesar Rp50.000 sesuai kebijakan yang berlaku
3.	Setelah deposit diterima dan dicatat, pegawai operasional: 
o	Mengaktifkan akun guest berbasis sesi
o	Memberikan kartu pinjam fisik kepada pengunjung
Karakteristik Kartu Pinjam:
•	Setiap kartu pinjam memiliki identitas unik (contoh: guest-01, guest-02, dan seterusnya)
•	Kartu pinjam direpresentasikan dalam bentuk QR Code fisik yang tidak aktif secara default dan hanya aktif selama sesi kunjungan berjalan
•	Identitas QR Code pada kartu pinjam diperbarui secara berkala untuk mengurangi risiko penyalahgunaan akses
Akses Fitur Layanan untuk Guest: 4. Pengunjung menggunakan kartu pinjam tersebut untuk login ke website dengan cara memindai QR Code
Selama sesi aktif, akun guest dapat mengakses fitur layanan dasar, antara lain:
•	Melihat informasi layanan
•	Melihat menu makanan dan minuman
•	Melakukan pemesanan makanan dan minuman selama sesi kunjungan masih aktif
Ketentuan Tambahan:
•	Halaman leaderboard dapat diakses dan dilihat oleh seluruh pengguna, namun hanya akun pelanggan dengan status member yang dapat tercatat dan mengisi peringkat leaderboard
•	Akun dengan status guest tidak dicatat dalam sistem leaderboard dan tidak memiliki riwayat transaksi jangka panjang
Proses Checkout dan Pengembalian Kartu: 5. Pada saat pengunjung selesai memancing dan melakukan checkout, pegawai operasional memproses transaksi akhir:
•	Total transaksi akan dikurangkan terlebih dahulu dari nilai deposit pada akun guest
•	Jika total transaksi melebihi nilai deposit, pengunjung wajib melakukan pembayaran tambahan
•	Jika total transaksi lebih kecil dari nilai deposit, maka sisa deposit dikembalikan kepada pengunjung
6.	Setelah proses checkout selesai: 
o	Sesi akun guest diakhiri dan dinonaktifkan secara otomatis
o	Kartu pinjam dikembalikan kepada pegawai operasional
o	Saldo dan status kartu di-reset, sehingga kartu tersebut tidak dapat digunakan kembali tanpa aktivasi ulang oleh pegawai operasional dan dapat dipakai untuk pengunjung lain pada hari berikutnya
4.2 Alur Kedatangan Pelanggan (Member Tervalidasi)
Kondisi: Pelanggan yang telah terdaftar dan divalidasi oleh pemilik sebagai member reguler.
Alur Proses:
1.	Pelanggan datang ke pemancingan dan menyampaikan keinginan untuk melakukan aktivitas memancing
2.	Pegawai operasional menanyakan status keanggotaan pelanggan
3.	Pelanggan menyatakan bahwa sudah terdaftar sebagai member
Proses Verifikasi Kedatangan (3 Opsi):
Pegawai operasional dapat memverifikasi kedatangan pelanggan dengan salah satu dari tiga cara berikut:
Opsi A: Scan QR Code
•	Pelanggan membuka website → login → menu "Profil Saya"
•	Pelanggan menunjukkan QR Code kepada pegawai operasional
•	Pegawai operasional melakukan scan QR Code menggunakan scanner atau kamera
•	Sistem menampilkan data pelanggan (nama, tier, posisi leaderboard)
Opsi B: Input Nomor HP
•	Pelanggan menyebutkan nomor HP yang terdaftar
•	Pegawai operasional mengetik nomor HP di formulir verifikasi
•	Sistem menampilkan data pelanggan
Opsi C: Input ID Member
•	Pelanggan menyebutkan ID Member (contoh: MBR-001)
•	Pegawai operasional mengetik ID Member di formulir verifikasi
•	Sistem menampilkan data pelanggan
Output Sistem Setelah Verifikasi: Sistem menampilkan konfirmasi kedatangan pelanggan beserta informasi berikut:
•	Nama pelanggan
•	Tier keanggotaan saat ini (dan poin akumulatif)
•	Posisi leaderboard (jika ada)
•	Waktu check-in (timestamp otomatis)
Aksi Sistem:
•	Sistem mencatat timestamp kedatangan pelanggan
•	Sistem menambahkan pelanggan ke dalam daftar "Pelanggan Hadir Hari Ini" untuk memudahkan pencarian saat pemesanan makanan/minuman atau checkout
•	Pelanggan dapat memulai aktivitas memancing
________________________________________
5. ALUR PEMESANAN MAKANAN DAN MINUMAN
5.1 Pemesanan Mandiri via Website (Self-Order)
Alur Proses:
1.	Pelanggan membuka website Pemancingan S melalui perangkat pribadi
2.	Pelanggan melakukan login menggunakan akun yang terdaftar (jika belum login)
3.	Pelanggan memilih menu "Pesan Makanan/Minuman"
4.	Pelanggan memilih item yang diinginkan dari daftar menu yang tersedia: 
o	Contoh: Mie Goreng (Rp15.000) - Qty: 1
o	Contoh: Teh Manis (Rp5.000) - Qty: 1
5.	Sistem menghitung total harga secara otomatis
6.	Pelanggan memilih opsi pembayaran "Bayar Nanti (saat pulang)"
7.	Pelanggan menekan tombol "Pesan"
Proses Sistem: 8. Pesanan masuk ke dalam sistem pegawai operasional 9. Status pesanan: PENDING (Belum Bayar) 10. Pesanan tersimpan dalam daftar "Transaksi Pending" pelanggan yang bersangkutan
Output:
•	Pesanan dikonfirmasi dan disiapkan oleh pegawai operasional
•	Pesanan akan dibayarkan bersamaan dengan transaksi ikan saat checkout akhir
5.2 Pemesanan Langsung ke Pegawai Operasional (Manual)
Alur Proses:
1.	Pelanggan memanggil pegawai operasional untuk melakukan pemesanan makanan/minuman
2.	Pegawai operasional membuka formulir "Tambah Pesanan" di sistem
3.	Pegawai operasional mencari nama pelanggan dari daftar "Pelanggan Hadir Hari Ini": 
o	Pegawai operasional mengetik nama pelanggan pada kolom pencarian
o	Atau pegawai operasional melakukan scroll daftar dan memilih nama pelanggan yang sesuai
4.	Pegawai operasional memilih item yang dipesan: 
o	Contoh: Mie Goreng (Rp15.000) - Qty: 1
o	Contoh: Teh Manis (Rp5.000) - Qty: 1
5.	Sistem menghitung total harga secara otomatis
6.	Pegawai operasional memilih status pembayaran: "Belum Bayar"
7.	Pegawai operasional menekan tombol "Simpan"
Proses Sistem: 8. Transaksi tersimpan dengan status PENDING (Belum Bayar) 9. Transaksi muncul di dalam daftar "Transaksi Pending" pelanggan yang bersangkutan 10. Pegawai operasional menyiapkan pesanan dan menyajikan kepada pelanggan
Output:
•	Pesanan akan dibayarkan bersamaan dengan transaksi ikan saat checkout akhir
________________________________________
6. ALUR PENYEWAAN ALAT PANCING
6.1 Ketentuan Sewa
•	Tarif: Rp10.000 per stik per hari
•	Durasi: Sepuasnya hingga waktu tutup pemancingan
•	Pelanggan yang membawa alat pancing sendiri: Tidak dikenakan biaya sewa, namun tetap dapat menyewa alat tambahan jika menginginkan
6.2 Alur Penyewaan
Alur Proses:
1.	Pelanggan datang ke pemancingan dengan atau tanpa membawa alat pancing sendiri
2.	Apabila pelanggan tidak membawa alat pancing atau ingin menyewa alat tambahan, pelanggan menyampaikan kepada pegawai operasional bahwa ingin menyewa alat pancing
3.	Pegawai operasional mencatat transaksi sewa alat pancing di sistem (sama seperti pencatatan transaksi makanan/minuman dengan status pembayaran: "Belum Bayar")
4.	Pegawai operasional memberikan alat pancing kepada pelanggan
5.	Transaksi sewa alat pancing akan digabungkan dengan transaksi pembelian ikan saat checkout akhir
6.3 Sistem Denda Kerusakan
Apabila terjadi kerusakan pada alat pancing yang disewa, pelanggan dikenakan denda sesuai ketentuan berikut:
•	Kail rusak: Rp2.000
•	Pelampung rusak: Rp5.000
•	Kerusakan total: Rp200.000
Catatan: Denda kerusakan dicatat sebagai item transaksi tambahan pada saat input transaksi atau checkout akhir. Tidak ada fitur khusus terpisah untuk pencatatan denda.
________________________________________
7. ALUR CHECKOUT TRANSAKSI
7.1 Checkout Transaksi untuk Member REGULAR
Kondisi Awal:
•	Nama: Budi Santoso
•	Tier: REGULAR (0 poin)
•	Diskon tier: 0%
•	Posisi Leaderboard: Belum ada
•	Voucher: Tidak ada
•	Transaksi Pending: Mie Goreng (Rp15.000) + Teh Manis (Rp5.000) = Rp20.000
Alur Proses:
1.	Pelanggan membawa hasil tangkapan ikan kepada pegawai operasional untuk ditimbang
2.	Pegawai operasional melakukan penimbangan ikan 
o	Contoh hasil: 5 kg ikan patin
3.	Pegawai operasional membuka formulir "Transaksi Pemancingan" di sistem
4.	Pegawai operasional mencari nama pelanggan dari daftar "Pelanggan Hadir Hari Ini": 
o	Pegawai operasional mengetik nama pelanggan atau memilih dari daftar
o	Contoh: Budi Santoso
Informasi yang Ditampilkan Sistem: Sistem menampilkan data pelanggan beserta transaksi pending (jika ada):
•	Nama: Budi Santoso
•	Tier: REGULAR (0 poin) - Diskon 0%
•	Posisi Leaderboard: Belum ada
•	Transaksi Pending: Mie Goreng + Teh Manis = Rp20.000 (Belum Bayar)
•	Catatan: Transaksi pending akan digabungkan dengan checkout ikan
Input Data Ikan: 5. Pegawai operasional memilih jenis ikan dari daftar, misal: Patin 6. Pegawai operasional memasukkan berat ikan: 5 kg
Kalkulasi Otomatis Sistem: Sistem melakukan perhitungan total transaksi secara otomatis sebagai berikut:
Rincian Transaksi:
•	Ikan Patin: 5 kg × Rp25.000 = Rp125.000
•	Mie Goreng: Rp15.000
•	Teh Manis: Rp5.000
•	Subtotal: Rp145.000
Perhitungan Diskon:
•	Diskon Tier (0%): Rp0 (hanya berlaku untuk ikan)
•	Subtotal setelah diskon: Rp145.000
Voucher: auto redeem jika pelanggan memiliki voucher (sudah dijelaskan pada flow 1.3).
•	Dalam contoh ini: Tidak ada voucher
Tips (Opsional): Sistem menampilkan kolom input tips untuk pelanggan yang ingin memberikan tips kepada pegawai operasional.
•	Contoh: Tips Rp10.000
Catatan Penting tentang Tips:
•	Tips tidak termasuk ke dalam total harga transaksi
•	Tips tercatat terpisah untuk keperluan pegawai operasional
•	Tips TIDAK menambah poin member
Total Pembayaran:
•	Subtotal: Rp145.000
•	Voucher: Rp0
•	Total Bayar: Rp145.000
•	Tips (opsional): Rp10.000
•	Total Bayar + Tips: Rp155.000
Proses Pembayaran: 7. Pelanggan melakukan pembayaran sebesar Rp155.000 (termasuk tips) melalui metode yang dipilih (cash/transfer/QRIS) 8. Pegawai operasional melakukan verifikasi pembayaran secara manual 9. Pegawai operasional menekan tombol "Simpan Transaksi"
Update Data Sistem: Setelah transaksi disimpan, sistem melakukan pembaruan data sebagai berikut:
•	Status transaksi: LUNAS
•	Poin pelanggan: 0 → 14 poin (+14 poin) 
o	Perhitungan: Rp145.000 ÷ Rp10.000 = 14 poin (dibulatkan ke bawah)
o	Poin dihitung dari harga asli sebelum diskon (bukan setelah diskon)
o	Poin dihitung dari semua item transaksi (ikan + makanan/minuman + sewa alat)
•	Berat ikan leaderboard: 0 kg → 5 kg (+5 kg)
•	Stok ikan patin: -5 kg
•	Tips tercatat: Rp10.000 (untuk pegawai operasional, tidak termasuk dalam total transaksi)
Output:
•	Sistem menampilkan konfirmasi transaksi berhasil
•	Pelanggan dapat melihat nota digital pada menu riwayat transaksi
•	Pelanggan selesai dan dapat pulang
7.2 Checkout Transaksi untuk Member SILVER dengan Posisi Leaderboard
Kondisi Awal:
•	Nama: Siti
•	Tier: SILVER (550 poin)
•	Diskon tier: 3%
•	Posisi Leaderboard saat ini: 
o	🥇 Ranking 1: Andi (80 kg)
o	🥈 Ranking 2: Siti (60 kg)
o	🥉 Ranking 3: Budi (40 kg)
•	Voucher: Tidak ada
•	Transaksi Pending: Tidak ada
Alur Proses:
1.	Pelanggan Siti membawa hasil tangkapan ikan kepada pegawai operasional untuk ditimbang
2.	Pegawai operasional melakukan penimbangan ikan 
o	Contoh hasil: 30 kg ikan patin
3.	Pegawai operasional membuka formulir "Transaksi Pemancingan" di sistem
4.	Pegawai operasional mencari nama pelanggan: Siti
Informasi yang Ditampilkan Sistem:
•	Nama: Siti
•	Tier: SILVER (550 poin) - Diskon 3%
•	Posisi Leaderboard: Ranking 2 (60 kg)
•	Transaksi Pending: Tidak ada
Input Data Ikan: 5. Pegawai operasional memilih jenis ikan: Patin 6. Pegawai operasional memasukkan berat ikan: 30 kg
Kalkulasi Otomatis Sistem:
Rincian Transaksi:
•	Ikan Patin: 30 kg × Rp25.000 = Rp750.000
Perhitungan Diskon:
•	Diskon Tier SILVER (3%): -Rp22.500
•	Subtotal setelah diskon: Rp727.500
Total Pembayaran:
•	Total Bayar: Rp727.500
Proses Pembayaran: 7. Pelanggan Siti melakukan pembayaran sebesar Rp727.500 8. Pegawai operasional melakukan verifikasi pembayaran 9. Pegawai operasional menekan tombol "Simpan Transaksi"
Update Data Sistem:
•	Status transaksi: LUNAS
•	Poin pelanggan: 550 → 625 poin (+75 poin) 
o	Perhitungan: Rp750.000 ÷ Rp10.000 = 75 poin
•	Berat ikan leaderboard: 60 kg → 90 kg (+30 kg)
•	Stok ikan patin: -30 kg
Perubahan Posisi Leaderboard: Sistem mendeteksi perubahan posisi leaderboard sebagai berikut:
•	🥇 Ranking 1: Siti (90 kg) ← NAIK dari Ranking 2
•	🥈 Ranking 2: Andi (80 kg) ← TURUN dari Ranking 1
•	🥉 Ranking 3: Budi (40 kg)
Notifikasi Khusus: Sistem menampilkan notifikasi kepada pegawai operasional:
•	"SELAMAT! Pelanggan Siti naik ke Ranking 1 Leaderboard!"
•	"Total berat ikan leaderboard: 90 kg"
Output:
•	Sistem menampilkan konfirmasi transaksi berhasil dan perubahan posisi leaderboard
7.3 Contoh Perhitungan Diskon untuk Member SILVER dengan Transaksi Campuran
Kondisi: Member SILVER membeli ikan, makanan, dan minuman dalam satu transaksi.
Rincian Transaksi:
•	Ikan Patin: 4 kg × Rp25.000 = Rp100.000
•	Mie Goreng: Rp15.000
•	Teh Manis: Rp5.000
•	Subtotal: Rp120.000
Perhitungan Diskon:
•	Diskon tier SILVER (3%) hanya berlaku untuk ikan: -Rp3.000
•	Makanan dan minuman tidak mendapat diskon
Total Pembayaran:
•	Ikan setelah diskon: Rp97.000
•	Makanan/Minuman: Rp20.000
•	Total Bayar: Rp117.000
Perhitungan Poin:
•	Poin dihitung dari harga asli: Rp120.000 ÷ Rp10.000 = 12 poin
________________________________________
8. ALUR SISTEM PEMBAYARAN
8.1 Metode Pembayaran yang Diterima
Sistem menerima pembayaran melalui metode berikut:
•	Cash (tunai)
•	Transfer bank
•	QRIS (e-wallet)
8.2 Verifikasi Pembayaran
Verifikasi pembayaran dilakukan secara manual oleh pegawai operasional setelah pelanggan melakukan pembayaran melalui metode yang dipilih.
8.3 Pemberian Nota Digital
Nota digital dapat diakses melalui website pada menu "Riwayat Transaksi" dengan informasi sebagai berikut:
•	Nomor transaksi
•	Tanggal dan waktu transaksi
•	Rincian item yang dibeli (ikan, makanan/minuman, sewa alat)
•	Berat ikan (jika ada)
•	Harga per item
•	Diskon yang diterapkan (tier, voucher)
•	Total pembayaran
•	Metode pembayaran
•	Tips (jika ada)
•	Poin yang diperoleh dari transaksi
Pelanggan dapat melakukan download atau print nota untuk keperluan pribadi.
________________________________________
9. MEKANISME UPGRADE DAN DOWNGRADE TIER
9.1 Upgrade Tier Otomatis
Kondisi: Sistem melakukan deteksi otomatis terhadap upgrade tier berdasarkan akumulasi poin pelanggan setelah transaksi disimpan.
Contoh Kasus:
•	Transaksi: Rp625.000 → Poin: +62 poin
•	Poin lama: 380 poin (Tier BRONZE)
•	Poin baru: 380 + 62 = 442 poin
Proses Sistem: Sistem mendeteksi bahwa poin pelanggan telah melewati threshold tier berikutnya (SILVER = 400 poin).
Notifikasi Upgrade: Sistem menampilkan notifikasi kepada pegawai operasional:
•	"SELAMAT! Tier pelanggan naik dari BRONZE ke SILVER"
•	"Benefit baru: Diskon tier 3% (naik dari 1%)"
•	"Badge Silver"
•	"Transaksi berikutnya otomatis mendapat diskon 3%"
Output:
•	Tier pelanggan diperbarui: BRONZE → SILVER
•	Benefit baru langsung berlaku untuk transaksi berikutnya
9.2 Downgrade Tier Otomatis
Kondisi Trigger: Sistem melakukan downgrade tier apabila pelanggan tidak melakukan transaksi selama 180 hari berturut-turut.
Mekanisme Downgrade:
Tahap 1: Masa Aman (Hari ke-0 hingga Hari ke-180)
•	Poin tidak berkurang
•	Tier tetap
•	Status: Aman
Tahap 2: Mulai Pengurangan Poin (Hari ke-181 dan seterusnya)
•	Pengurangan poin: -10 poin per hari
•	Proses: Otomatis setiap hari hingga ada transaksi baru
•	Downgrade tier: Otomatis apabila poin turun di bawah threshold tier yang sedang dimiliki
Contoh Kasus: Member GOLD (800 poin)
Transaksi terakhir: 1 Januari 2026
•	Hari 1 – 180 (1 Jan – 29 Jun):
o	Poin: 800 (aman)
o	Tier: GOLD
•	Hari 181 (30 Juni):
o	Poin: 800 - 10 = 790 poin
o	Tier: SILVER (turun, karena poin < 800)
•	Hari 182 (1 Juli):
o	Poin: 790 - 10 = 780 poin
o	Tier: SILVER
•	Dan seterusnya hingga poin mencapai 0
Penghentian Pengurangan Poin: Apabila pelanggan melakukan transaksi di tengah periode downgrade:
•	Pengurangan poin STOP
•	Poin bertambah dari transaksi baru
•	Sistem menghitung ulang periode 180 hari dari tanggal transaksi terakhir
________________________________________
10. ALUR PENGELOLAAN DATA OPERASIONAL OLEH PEMILIK
Pengelolaan data operasional mencakup dua kategori utama: pengelolaan menu makanan dan minuman, serta pengelolaan jenis ikan dan harganya.
10.1 Pengelolaan Menu Makanan dan Minuman
Ruang Lingkup Pengelolaan: Pemilik memiliki kewenangan penuh untuk mengelola menu makanan dan minuman, meliputi:
•	Menambahkan menu baru ke dalam sistem
•	Menghapus menu dari sistem
•	Mengubah informasi menu atau status ketersediaan menu (Tersedia / Tidak Tersedia)
Atribut Menu yang Dikelola:
•	Nama menu
•	Harga
•	Kategori (Makanan / Minuman)
•	Status ketersediaan
Catatan Penting: Status ketersediaan digunakan untuk menandai menu yang tidak dapat dipesan sementara waktu (misalnya karena bahan habis atau tidak diproduksi sementara). Menu yang tidak tersedia akan ditampilkan dengan label khusus dan tidak dapat dipilih oleh pelanggan maupun pegawai operasional.
10.2 Pengelolaan Jenis Ikan dan Harga
Ruang Lingkup Pengelolaan: Pemilik memiliki kewenangan penuh untuk mengelola jenis ikan yang tersedia di sistem, meliputi:
•	Menambahkan jenis ikan baru (misalnya: ikan lele, ikan mas, dll.)
•	Mengubah informasi jenis ikan yang sudah ada (nama, harga per kilogram)
•	Menghapus jenis ikan dari sistem
•	Memperbarui harga per kilogram sesuai kondisi pasar atau kebijakan usaha
Atribut Jenis Ikan yang Dikelola:
•	Nama jenis ikan
•	Harga per kilogram
Catatan Penting: Ketersediaan jenis ikan ditentukan oleh stok fisik yang dikelola melalui sistem pengelolaan stok (Flow 11).
10.3 Dampak Perubahan Data Operasional
Setiap perubahan data operasional yang dilakukan oleh pemilik secara otomatis berdampak pada:
•	pelanggan, yaitu tampilan menu, jenis ikan, dan harga yang ditampilkan di website selalu mencerminkan kondisi terkini; dan
•	pegawai operasional, yaitu data yang digunakan dalam proses pemesanan dan checkout selalu menggunakan data terbaru dari sistem.
Dengan mekanisme ini, sistem menjamin konsistensi antara pengelolaan data oleh pemilik, tampilan informasi kepada pelanggan, dan proses transaksi yang dilakukan oleh pegawai operasional.
________________________________________
11. ALUR PENGELOLAAN STOK IKAN
11.1 Alur Restock Ikan oleh Pemilik
Alur Proses:
1.	Pemilik melakukan login ke sistem
2.	Pemilik membuka menu "Kelola Stok Ikan"
3.	Pemilik memilih opsi "Restock Ikan" dan jenis ikan yang akan direstock (contoh: Patin)
4.	Pemilik memasukkan berat restock (contoh: 150 kg), kemudian “Simpan” 
Proses Sistem: 7. Sistem menambahkan stok ikan patin sebesar +150 kg 8. Sistem mencatat tanggal dan waktu restock secara otomatis (timestamp) 9. Sistem menyimpan log history restock untuk keperluan audit dan analisis
Notifikasi Broadcast ke Member: 10. Sistem secara otomatis mengirimkan notifikasi broadcast kepada semua member terdaftar melalui channel berikut:
•	Notifikasi in-app (jika member login di website)
Contoh Notifikasi: "Ikan Baru Di-Restock! Pemancingan S baru saja restock: Ikan Patin sebanyak 150 kg. Ayo mancing sekarang!"
11.2 Tracking Stok Ikan Otomatis
Mekanisme Tracking: Setiap kali terjadi transaksi pembelian ikan (checkout), sistem secara otomatis:
1.	Mengurangi stok ikan sesuai dengan berat yang dijual
2.	Memperbarui stok real-time di dashboard pemilik
3.	Melakukan pengecekan apakah stok telah mencapai threshold minimum
Contoh:
•	Stok awal Patin: 150 kg
•	Pelanggan A checkout 5 kg → Stok: 145 kg
•	Pelanggan B checkout 10 kg → Stok: 135 kg
•	Stok tersisa: 135 kg
Alert Stok Menipis: Apabila stok ikan mencapai di bawah threshold minimum yang telah ditetapkan, sistem secara otomatis mengirimkan alert kepada pemilik.
Threshold Alert per Jenis Ikan:
Jenis Ikan	Alert Jika Stok Kurang Dari
Patin	50 kg
Gurame	10 kg
Nila	25 kg
Bawal	5 kg
Contoh Alert: "PERINGATAN: Stok ikan Patin saat ini 45 kg (di bawah threshold 50 kg). Segera lakukan restock!"
________________________________________
12. ALUR PENGELOLAAN DAN PENGUMUMAN EVENT / INFORMASI OLEH PEMILIK
12.1 Akses Sistem Pengelolaan Event dan Informasi
Alur Proses:
1.	Pemilik melakukan login
2.	Pemilik membuka menu "Kelola Event dan Informasi"
3.	Sistem menampilkan daftar event dan informasi yang telah dipublikasikan sebelumnya (jika ada)
12.2 Menambahkan Event atau Informasi Baru
Alur Proses:
1.	Pemilik menekan tombol "Tambah Event/Informasi Baru"
2.	Pemilik memilih jenis publikasi: 
o	Event Pemancingan: Informasi terkait acara atau kompetisi pemancingan
o	Informasi Umum: Pengumuman penting terkait operasional pemancingan (selain restock ikan yang sudah otomatis)
3.	Pemilik mengisi formulir dengan informasi berikut: 
o	Judul event/informasi
o	Deskripsi lengkap
o	Tanggal mulai dan tanggal berakhir (untuk event)
o	Kategori (Event / Informasi Umum)
o	Status publikasi (Draft / Dipublikasikan)
4.	Pemilik menekan tombol "Simpan"
Proses Sistem: 5. Sistem menyimpan data event/informasi ke dalam database 6. Apabila status publikasi dipilih "Dipublikasikan", sistem secara otomatis:
•	Menampilkan event/informasi di halaman utama website Pemancingan S
•	Mengirimkan notifikasi broadcast kepada seluruh member terdaftar melalui: 
o	Notifikasi in-app (jika member login di website)
Contoh Notifikasi: "Event Baru! Pemancingan S mengadakan Kompetisi Mancing Ikan Terbesar pada tanggal 20 Januari 2026. Total hadiah Rp5.000.000! Klik di sini untuk info lengkap."
12.3 Mengubah atau Menghapus Event/Informasi
Alur Proses untuk Mengubah:
1.	Pemilik memilih event/informasi yang ingin diubah dari daftar
2.	Pemilik menekan tombol "Edit"
3.	Pemilik melakukan perubahan pada informasi yang diperlukan
4.	Pemilik menekan tombol "Simpan Perubahan"
5.	Sistem memperbarui data event/informasi di database dan di website
Alur Proses untuk Menghapus:
1.	Pemilik memilih event/informasi yang ingin dihapus dari daftar
2.	Pemilik menekan tombol "Hapus"
3.	Sistem meminta konfirmasi penghapusan
4.	Pemilik mengkonfirmasi penghapusan
5.	Sistem menghapus event/informasi dari database dan dari tampilan website
12.4 Akses Event dan Informasi oleh Pelanggan
Alur Proses:
1.	Pelanggan (baik member maupun guest) mengakses website Pemancingan S
2.	Pelanggan membuka halaman "Event dan Informasi" atau melihat pengumuman di halaman utama
3.	Sistem menampilkan daftar event dan informasi yang telah dipublikasikan oleh pemilik, diurutkan berdasarkan tanggal publikasi (terbaru di atas)
4.	Pelanggan dapat memilih event/informasi tertentu untuk melihat detail lengkap
5.	Pelanggan dapat melihat: 
o	Judul event/informasi
o	Deskripsi lengkap
o	Tanggal mulai dan berakhir (untuk event)
o	Kategori (Event / Informasi Umum)
Karakteristik:
•	Informasi dapat diakses oleh seluruh pengguna (member maupun guest) tanpa perlu login
•	Event dan informasi yang sudah melewati tanggal berakhir akan dipindahkan ke arsip (tetap dapat diakses namun tidak ditampilkan di halaman utama)
________________________________________
13. ALUR LAPORAN KEUANGAN PEMILIK
13.1 Fitur Dashboard Laporan Keuangan
Pemilik dapat mengakses dashboard laporan keuangan melalui sistem dengan fitur berikut:
•	Tampilan tabel
•	Filter periode: Harian, Mingguan, Bulanan, Custom Range
•	Export data ke format CSV untuk analisis lebih lanjut
13.2 Isi Laporan Keuangan
Laporan keuangan mencakup informasi sebagai berikut:
•	Total transaksi (jumlah transaksi dan nominal)
•	Total diskon yang diberikan (tier + voucher)
•	Pendapatan bersih
•	Breakdown per kategori (ikan, makanan/minuman, sewa alat)
•	Tips pegawai operasional (terpisah atau dapat ditampilkan terpisah)
•	Jumlah member baru yang mendaftar
•	Stok ikan (stok awal, restock, terjual, stok sisa)
13.3 Format Export CSV
Contoh format export CSV untuk analisis lebih lanjut:
Tanggal, No_Transaksi, Member, Item, Qty, Harga, Diskon, Total, Metode_Bayar, Tips, Poin
10/01/2026, TRX-001, Budi, Patin, 5, 125000, 0, 125000, Cash, 10000, 12
10/01/2026, TRX-002, Siti, Nila, 3, 105000, 3150, 101850, Transfer, 0, 10
Catatan:
•	Data transaksi dapat diekspor untuk keperluan analisis bisnis, audit, atau pelaporan keuangan
•	Pemilik dapat melakukan analisis tren penjualan, pelanggan terbanyak, prediksi stok, dan lain-lain berdasarkan data yang telah diekspor
________________________________________
14. RINGKASAN PERUBAHAN OPERASIONAL (AS-IS vs TO-BE)
14.1 Perubahan dari Sistem Lama (AS-IS)
Sistem Verifikasi Pelanggan:
•	AS-IS: Berbasis hafalan wajah pegawai operasional/pemilik
•	TO-BE: Verifikasi menggunakan QR Code, nomor HP, atau ID Member dengan database pelanggan terintegrasi
Sistem Pencatatan Transaksi:
•	AS-IS: Duplikasi pencatatan (buku tulis manual → input ulang ke Aplikasir)
•	TO-BE: Pencatatan digital langsung (single entry) oleh pegawai operasional dengan sinkronisasi real-time
Pengelolaan Stok Ikan:
•	AS-IS: Estimasi visual tanpa tracking otomatis
•	TO-BE: Tracking stok real-time dengan alert otomatis dan notifikasi restock kepada member
Sistem Membership:
•	AS-IS: Tidak ada sistem membership atau loyalty program
•	TO-BE: Sistem membership dengan tier benefit (REGULAR, BRONZE, SILVER, GOLD) dan leaderboard bulanan dengan voucher
Pemberian Nota:
•	AS-IS: Tidak ada nota (atau hanya jika diminta)
•	TO-BE: Nota digital otomatis tersedia di website dengan riwayat transaksi lengkap
Pemesanan Makanan/Minuman:
•	AS-IS: Harus memanggil pegawai operasional atau meninggalkan spot memancing
•	TO-BE: Pemesanan mandiri via website tanpa harus meninggalkan spot
Monitoring Pemilik:
•	AS-IS: Tidak real-time, delay 1–3 hari setelah input manual
•	TO-BE: Monitoring real-time melalui dashboard dengan akses dari mana saja
Pengelolaan Data Operasional:
•	AS-IS: Tidak ada sistem untuk mengelola menu, harga, event, dan informasi secara terpusat
•	TO-BE: Pemilik dapat mengelola menu, harga ikan, status ketersediaan, event, dan informasi melalui sistem dari mana saja
Validasi Akun Pelanggan:
•	AS-IS: Tidak ada proses validasi formal; setiap pendaftaran langsung aktif
•	TO-BE: Setiap pendaftaran akun wajib melalui proses validasi dan persetujuan oleh pemilik sebelum dapat digunakan
14.2 Benefit Sistem TO-BE
Untuk Pemilik:
•	Monitoring real-time kondisi operasional (transaksi, stok, kunjungan)
•	Laporan keuangan otomatis tanpa input ulang
•	Notifikasi otomatis untuk kondisi kritis (stok menipis)
•	Export data untuk analisis bisnis
•	Pengelolaan menu, harga, event, dan informasi dari mana saja tanpa harus berada di lokasi
•	Validasi akun pelanggan untuk memastikan data member yang terdaftar akurat dan valid
Untuk Pegawai Operasional:
•	Verifikasi pelanggan cepat dan akurat (tidak perlu hafalan wajah)
•	Perhitungan harga otomatis (mengurangi beban mental)
•	Antarmuka sederhana dan cepat saat kondisi ramai
•	Pencatatan transaksi otomatis (tidak perlu tulis manual di buku)
Untuk Pelanggan:
•	Akses informasi terpusat (harga, jenis ikan, jam operasional, event, pengumuman)
•	Informasi restock ikan real-time
•	Pemesanan makanan/minuman tanpa meninggalkan spot
•	Nota digital dan riwayat transaksi
•	Program loyalitas dengan benefit tier dan voucher bulanan
•	Verifikasi status mudah (QR Code, nomor HP, ID Member)
•	Akses informasi event dan pengumuman penting secara real-time


'
