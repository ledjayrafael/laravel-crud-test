# CRUD Sederhana Laravel + MySQL menggunakan VPS

Panduan ini berisi implementasi **CRUD sederhana** dengan Laravel dan MySQL yang bisa dijalankan di VPS (Ubuntu + Nginx + PHP-FPM).

## 1) Persiapan VPS

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip unzip git curl
```

Instal Composer:

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## 2) Buat Project Laravel

```bash
cd /var/www
sudo composer create-project laravel/laravel laravel-crud
cd laravel-crud
cp .env.example .env
php artisan key:generate
```

Atur permission:

```bash
sudo chown -R www-data:www-data /var/www/laravel-crud
sudo find /var/www/laravel-crud -type d -exec chmod 755 {} \;
sudo find /var/www/laravel-crud -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/laravel-crud/storage /var/www/laravel-crud/bootstrap/cache
```

## 3) Setup MySQL

Masuk ke MySQL:

```bash
sudo mysql
```

Jalankan query:

```sql
CREATE DATABASE laravel_crud;
CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_AMAN_ANDA';
GRANT ALL PRIVILEGES ON laravel_crud.* TO 'laravel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update `.env`:

```env
APP_NAME="Laravel CRUD"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://IP_VPS_ANDA

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_crud
DB_USERNAME=laravel_user
DB_PASSWORD=GANTI_PASSWORD_AMAN_ANDA
```

> **Penting:** jangan gunakan password contoh. Ganti dengan password unik yang kuat.  
> Saat aplikasi siap dipublikasikan, ubah ke:
>
> ```env
> APP_ENV=production
> APP_DEBUG=false
> ```

## 4) Implementasi CRUD (Products)

Generate model + migration + controller:

```bash
php artisan make:model Product -mcr
```

Isi migration `database/migrations/*_create_products_table.php`:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 12, 2);
    $table->timestamps();
});
```

Isi model `app/Models/Product.php`:

```php
class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price'];
}
```

Isi controller `app/Http/Controllers/ProductController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->paginate(10);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|decimal:2|min:0',
        ]);

        Product::create($validated);
        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|decimal:2|min:0',
        ]);

        $product->update($validated);
        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
```

Isi `routes/web.php`:

```php
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('products.index'));
Route::resource('products', ProductController::class)->except(['show']);
```

Buat view:

```bash
mkdir -p resources/views/products
```

`resources/views/products/index.blade.php`:

```blade
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Produk</title>
</head>
<body>
    <h1>Daftar Produk</h1>
    <a href="{{ route('products.create') }}">Tambah Produk</a>
    @if(session('success')) <p>{{ session('success') }}</p> @endif
    <table border="1" cellpadding="8">
        <thead>
            <tr><th>Nama</th><th>Deskripsi</th><th>Harga</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        @forelse($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->description }}</td>
                <td>Rp {{ number_format($product->price, 2, ',', '.') }}</td>
                <td>
                    <a href="{{ route('products.edit', $product) }}">Edit</a>
                    <form action="{{ route('products.destroy', $product) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">Belum ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $products->links() }}
</body>
</html>
```

`resources/views/products/create.blade.php` dan `edit.blade.php` bisa memakai form yang sama:

```blade
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ isset($product) ? 'Edit' : 'Tambah' }} Produk</title>
</head>
<body>
    <h1>{{ isset($product) ? 'Edit' : 'Tambah' }} Produk</h1>

    <form method="POST" action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}">
        @csrf
        @isset($product) @method('PUT') @endisset

        <label>Nama</label><br>
        <input type="text" name="name" value="{{ old('name', $product?->name) }}"><br><br>

        <label>Deskripsi</label><br>
        <textarea name="description">{{ old('description', $product?->description) }}</textarea><br><br>

        <label>Harga</label><br>
        <input type="number" step="0.01" name="price" value="{{ old('price', $product?->price) }}"><br><br>

        <button type="submit">Simpan</button>
    </form>

    <a href="{{ route('products.index') }}">Kembali</a>
</body>
</html>
```

Jalankan migrasi:

```bash
php artisan migrate
```

## 5) Konfigurasi Nginx

Buat virtual host `/etc/nginx/sites-available/laravel-crud`:

```nginx
server {
    listen 80;
    server_name IP_VPS_ANDA;
    root /var/www/laravel-crud/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Aktifkan site:

```bash
sudo ln -s /etc/nginx/sites-available/laravel-crud /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 6) Jalankan dan Akses Aplikasi

```bash
cd /var/www/laravel-crud
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Buka browser: `http://IP_VPS_ANDA`

Anda sekarang memiliki CRUD sederhana (Create, Read, Update, Delete) untuk data produk dengan Laravel + MySQL di VPS.
