<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quran AI Semantic Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center">🔍 Quran AI Semantic Search</h1>

    <div class="card p-4 shadow-sm">
        <div class="input-group mb-3">
            <input type="text" id="query" class="form-control" placeholder="Ketik pertanyaan atau kata kunci...">
            <button class="btn btn-primary" id="btnSearch">Cari</button>
        </div>

        <div id="loading" class="text-center text-muted my-3" style="display:none;">⏳ Sedang mencari...</div>
        <div id="results"></div>
    </div>
</div>

<script>
document.getElementById('btnSearch').addEventListener('click', async () => {
    const q = document.getElementById('query').value.trim();
    if (!q) return alert('Masukkan kata kunci terlebih dahulu.');

    document.getElementById('loading').style.display = 'block';
    document.getElementById('results').innerHTML = '';

    const response = await fetch(`/api/search/semantic?q=${encodeURIComponent(q)}`);
    const data = await response.json();

    document.getElementById('loading').style.display = 'none';

    if (data.results && data.results.length > 0) {
        data.results.forEach(r => {
            const card = document.createElement('div');
            card.className = 'card mb-3 p-3';
            card.innerHTML = `
                <h5 class="mb-2">📖 Surah ${r.surah_id}:${r.ayah_in_surah}</h5>
                <p class="text-success fs-5" dir="rtl">${r.text_ar}</p>
                <p class="text-secondary">${r.text_id}</p>
                <small class="text-muted">Similarity: ${(r.similarity * 100).toFixed(2)}%</small>
            `;
            document.getElementById('results').appendChild(card);
        });
    } else {
        document.getElementById('results').innerHTML = `<p class="text-center text-muted">Tidak ada hasil ditemukan.</p>`;
    }
});
</script>
</body>
</html>
