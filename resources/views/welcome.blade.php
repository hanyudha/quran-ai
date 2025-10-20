<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quran AI - Pencarian Semantik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold text-success">🕋 Quran AI</h1>
            <p class="text-muted">Pencarian Semantik Ayat Al-Qur'an berdasarkan makna</p>
        </div>

        @if (session('error'))
            <div class="alert alert-danger text-center">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('semantic.search') }}" class="card shadow p-4 mx-auto" style="max-width: 600px;">
            @csrf
            <div class="mb-3">
                <label for="query" class="form-label fw-semibold">Masukkan pertanyaan atau topik:</label>
                <input type="text" name="query" id="query" class="form-control form-control-lg" placeholder="contoh: tentang puasa, shalat, atau sabar..." required>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg">
                    🔍 Cari Ayat
                </button>
            </div>
        </form>
    </div>

</body>
</html>
