================================================================
   BIMBEL AYO MENGAJI — FULLSTACK WEBAPP (PHP Native + MySQL/PDO)
================================================================

Platform bimbingan belajar Al-Qur'an untuk anak & remaja (6-17 th)
dengan guru privat yang datang ke rumah di Cianjur. Sistem multi-role
(Client, Manager, Pengajar, Super Admin) lengkap dengan database,
panel administrasi, dan halaman publik dinamis.

----------------------------------------------------------------
CARA INSTALL (XAMPP)
----------------------------------------------------------------
1. Install XAMPP (Apache + MySQL/MariaDB), PHP 8.2+.
2. Buka XAMPP Control Panel -> Start "Apache" dan "MySQL".
3. Copy seluruh folder project ke:
      C:\xampp\htdocs\bimbel-ayo-mengaji\
4. Buka phpMyAdmin: http://localhost/phpmyadmin
5. Import database:
      - Klik tab "Import"
      - Pilih file: database.sql
      - Klik "Go/Kirim"
   (database "bimbel_ayo_mengaji" akan dibuat otomatis + seed data)
6. Akses web:
      http://localhost/bimbel-ayo-mengaji/
      (otomatis membuka index.php)

Catatan: Konfigurasi DB default ada di includes/db.php
   host=localhost | db=bimbel_ayo_mengaji | user=root | password="" (kosong)
   Ubah di sana jika setup MySQL Anda berbeda.

----------------------------------------------------------------
AKUN DEFAULT (sudah ter-seed di database.sql)
----------------------------------------------------------------
   Super Admin : superadmin / admin123
   Manager     : manager1   / manager123
   Pengajar    : aldira     / pengajar123

   Client      : daftar sendiri via tombol "Daftar" / register.php

Login universal di: login.php (redirect otomatis ke dashboard sesuai role).

----------------------------------------------------------------
STRUKTUR HALAMAN
----------------------------------------------------------------
PUBLIK (tanpa login):
   index.php             Landing page dinamis (pengajar & galeri dari DB)
   tentang-kami.php      Profil pengajar dinamis (semua pengajar aktif)
   slide-keunggulan.php  Journey slider 5 tahap
   login.php / register.php / logout.php

PANEL SUPER ADMIN (admin/):
   dashboard.php  Statistik + grafik aktivitas 7 hari
   users.php      CRUD semua user (filter, pagination 15)
   log.php        Log aktivitas (filter role/jenis/tanggal, pagination 20)
   settings.php   Pengaturan web + Backup DB (.sql) + Reset log

PANEL MANAGER (manager/):
   dashboard.php  Ringkasan + grafik pendaftaran 7 hari
   clients.php    Approve/Reject client + detail entitas
   entitas.php    Assign pengajar & jadwal ke santri
   pengajar.php   Tambah/Edit/Nonaktifkan pengajar (+ upload foto)
   galeri.php     Upload/Hapus/Aktifkan/Urutkan foto galeri (rolling)
   tugas.php      Beri tugas ke pengajar + pantau status

PANEL PENGAJAR (pengajar/):
   dashboard.php  Ringkasan santri, tugas, jadwal hari ini
   jadwal.php     Jadwal mengajar (filter per hari)
   santri.php     Daftar santri yang ditugaskan
   progress.php   Update level & status belajar santri

PANEL CLIENT (client/):
   dashboard.php  Ringkasan santri yang didaftarkan
   daftar.php     Form daftar MULTIPLE santri (dinamis tambah baris)
   status.php     Monitoring status + pengajar yang ditugaskan

----------------------------------------------------------------
FITUR UTAMA
----------------------------------------------------------------
- Multi-role authentication dengan RBAC pada setiap halaman.
- Manager menambah pengajar baru -> halaman publik berubah OTOMATIS.
- Manager/Super Admin mengganti foto galeri -> tampil rolling sesuai
  "Urutan Tampil" di index.php & tentang-kami.php.
- Client mendaftarkan banyak santri sekaligus (baris dinamis via JS).
- Dashboard statistik + bar chart CSS murni (tanpa library).
- Backup database (download .sql) dari panel Super Admin.
- Log aktivitas (audit trail) untuk semua aksi penting.

----------------------------------------------------------------
KEAMANAN YANG DITERAPKAN
----------------------------------------------------------------
- password_hash() / password_verify() (BCRYPT) untuk semua password.
- Prepared statements (PDO) pada SEMUA query (anti SQL injection).
- htmlspecialchars() pada output (anti XSS) via helper e().
- CSRF token pada SEMUA form POST (generateCSRF/validateCSRF).
- Validasi upload: hanya jpg/jpeg/png, max 2MB, cek MIME asli.
- File upload disimpan dengan nama unik (timestamp + random).
- Session timeout 2 jam + regenerate session ID setelah login.
- Redirect otomatis: belum login -> login.php; salah role -> dashboard.
- Edit akun staf (manager/pengajar/super_admin) di admin/users.php
  memerlukan verifikasi email berdomain @bimbelayomengaji.my.id
  (privasi developer; domain dapat diubah di admin/settings.php).

----------------------------------------------------------------
CATATAN TEKNIS
----------------------------------------------------------------
- Foto existing (WhatsApp Image ...jpeg) tetap dipakai sebagai seed.
- assets/uploads/  -> foto profil/pengajar hasil upload.
- assets/images/   -> foto galeri hasil upload manager.
- assets/default-pengajar.png & default-avatar.png -> placeholder
  (fallback otomatis bila foto tidak ditemukan).
- Desain (warna, font Amiri/Nunito, emoji floating, dark/light mode,
  animasi) dipertahankan 100% dari versi statis sebelumnya.
- Dark/Light mode tersinkron antar halaman via localStorage "bimbel-theme".

----------------------------------------------------------------
KONTAK
----------------------------------------------------------------
   WhatsApp : 087750275958
   Admin    : Al Dira Achmad Arrazib

(c) 2026 Bimbel Ayo Mengaji — Dibuat dengan untuk Generasi Qurani.
