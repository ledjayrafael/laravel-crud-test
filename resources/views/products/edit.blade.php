<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk</title>
</head>
<body>
    <h1>Edit Produk</h1>

    <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')

        <label>Nama</label><br>
        <input type="text" name="name" value="{{ old('name', $product->name) }}"><br><br>
        @error('name')
            <p>{{ $message }}</p>
        @enderror

        <label>Deskripsi</label><br>
        <textarea name="description">{{ old('description', $product->description) }}</textarea><br><br>
        @error('description')
            <p>{{ $message }}</p>
        @enderror

        <label>Harga</label><br>
        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}"><br><br>
        @error('price')
            <p>{{ $message }}</p>
        @enderror

        <button type="submit">Simpan</button>
    </form>

    <a href="{{ route('products.index') }}">Kembali</a>
</body>
</html>
