<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - Quran AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9fafb; }
        .ayat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        .arabic {
            font-size: 1.8rem;
            direction: rtl;
            text-align: right;
            line-height: 2.2rem;
            font-family: 'Scheherazade New', serif;
        }
        .translation {
            font-size: 1rem;
            margin-top: 8px;
            color: #333;
        }
        .similarity {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-success">🔎 Hasil Pencarian Semantik</h2>
        <p class="text-muted">Pencarian untuk: <strong>"{{ $query }}"</strong></p>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
    </div>

    @if(count($results) > 0)
        @foreach($results as $r)
            <div class="ayat-card">
                <div class="arabic">{{ $r->arabic }}</div>
                <div class="translation">{{ $r->translation }}</div>
                <div class="similarity">🔹 Nilai kemiripan: {{ number_format($r->similarity * 100, 2) }}%</div>
            </div>
        @endforeach
    @else
        <div class="alert alert-warning text-center">
            Tidak ditemukan ayat yang relevan.
        </div>
    @endif
</div>

</body>
</html>
