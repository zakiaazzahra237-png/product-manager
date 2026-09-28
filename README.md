# Mini Project: Product Manager (Tugas Akhir)

Aplikasi manajemen produk berbasis web sederhana yang dibangun menggunakan **PHP Native** dan **MySQL (PDO)**. Aplikasi ini dirancang aman terhadap celah keamanan dasar (SQL Injection, XSS, CSRF), mencegah duplikasi data saat *refresh*, serta memiliki tampilan UI responsif dengan tema *dark mode*.

---

## 🚀 Fitur Utama & Validasi

1. **Create (Tambah Produk)**
   * Memasukkan data: Nama Produk, Kategori, Harga, dan Stok.
   * **Validasi Server-side:**
     * Nama produk minimal 3 karakter.
     * Nama produk harus unik (tidak boleh ganda).
     * Harga harus berupa angka positif (> 0).
     * Stok harus berupa angka non-negatif (≥ 0).
2. **Read (Tampil Produk)**
   * Menampilkan daftar produk dalam bentuk *Grid Card* yang responsif.
3. **Update (Edit Produk)**
   * Mengedit data produk berdasarkan ID.
4. **Delete (Hapus Produk)**
   * Menghapuskan produk via metode `POST` yang dilindungi token CSRF.
5. **Keamanan & Kontrol Request**
   * **Anti-SQL Injection:** Menggunakan PDO Prepared Statements (`$pdo->prepare()`).
   * **Anti-XSS:** Sanitasi output menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
   * **Anti-CSRF:** Implementasi token CSRF acak berbasis Session pada setiap form POST.
   * **Pola PRG (Post/Redirect/Get):** Mencegah resubmission/duplikasi data saat halaman di-*refresh*.

---

## 🛠️ Teknologi yang Penggunaan

* **Language:** PHP 8.x (Native)
* **Database:** MySQL / MariaDB
* **Driver:** PHP Data Objects (PDO)
* **Frontend:** HTML5, CSS3 (Flexbox & Grid Responsif)

---

## 💻 Cara Menjalankan Project (Panduan Lokal)

### Persyaratan System:
* XAMPP / Laragon (PHP & MySQL Server)
* Web Browser (Chrome / Edge / Firefox)

### Langkah-langkah Penggunaan:

1. **Clone / Salin Folder Project:**
   Pastikan folder `product-manager` diletakkan di dalam direktori server lokal:
   ```text
   C:\xampp\htdocs\product-manager