<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Cetak')</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body style="background:#cfd5de">
<div class="pbar"><button class="p" onclick="window.print()">Cetak / Simpan PDF</button><span>{{ $students->count() }} dokumen</span><span class="sp"></span><a href="javascript:window.close()">Tutup</a></div>
@if (!empty($blokir))
<div class="pwarn" style="background:#fdecea;border-color:#f1c9c4;color:#8a1f17"><b>SKL tidak dicetak:</b> {{ count($blokir) }} siswa belum ditetapkan lulus / tidak lulus — {{ implode('; ', array_slice($blokir, 0, 6)) }}{{ count($blokir) > 6 ? '…' : '' }}. Tetapkan dulu di menu Nilai → Kelulusan.</div>
@endif
@if (!empty($peringatan))
<div class="pwarn"><b>Perhatian:</b> SKL bertuliskan TIDAK LULUS untuk {{ count($peringatan) }} siswa — {{ implode('; ', array_slice($peringatan, 0, 6)) }}{{ count($peringatan) > 6 ? '…' : '' }}.</div>
@endif
<div class="prev">
  @yield('docs')
  @if ($students->isEmpty())<p>{{ !empty($blokir) ? 'Tidak ada SKL yang bisa dicetak.' : 'Tidak ada siswa yang cocok dengan filter.' }}</p>@endif
</div>
</body>
</html>
