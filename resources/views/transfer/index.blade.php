@extends('layouts.app')
@section('content')
<h2 class="t">Import / Ekspor Nilai</h2>
<p class="hint" style="max-width:760px">Pilih format file, unduh template, isi di Excel, lalu impor. Siswa dicocokkan lewat <b>NISN</b> (siswa harus sudah ada di <a href="{{ route('siswa.index') }}">Data Siswa</a>). Impor ditampilkan dalam <b>pratinjau</b> sebelum diterapkan; sel nilai kosong dilewati.</p>

<div class="xtabs" role="tablist">
  <button type="button" data-t="semester">1 sheet · satu semester</button>
  <button type="button" data-t="riwayat">1 sheet · Smt1 – Smt6</button>
  <button type="button" data-t="mapel">Banyak sheet · satu mapel per sheet</button>
</div>

<div class="card xwrap">
  @include('transfer._panel', [
    'id' => 'semester',
    'ket' => 'Kolom: No, Nama, NISN, NIS, lalu satu kolom per mapel (kode atau nama). Tiap sel = nilai untuk satu semester yang Anda pilih. Judul sekolah/kelas di atas tabel dilewati.',
    'ekspor' => route('transfer.ekspor.leger'), 'impor' => route('transfer.impor.leger'),
    'accept' => '.xlsx,.xls,.csv,.txt', 'sem' => true,
    'catatan' => 'Baris “Rata-rata/Jumlah/Total” di bawah tabel diabaikan. Satu file boleh berisi banyak sheet (mis. satu kelas per sheet).',
  ])
  @include('transfer._panel', [
    'id' => 'riwayat',
    'ket' => 'Tiap mapel punya sub-kolom Smt1 … Smt6, seperti leger e-rapor multi-semester. Satu file mengisi semua semester sekaligus; kolom rerata diabaikan.',
    'ekspor' => route('transfer.ekspor.riwayat'), 'impor' => route('transfer.impor.riwayat'),
    'accept' => '.xlsx,.xls,.csv,.txt', 'sem' => false,
    'catatan' => 'Header mapel boleh digabung (merged). Nama lengkap, kode, dan singkatan mapel dikenali.',
  ])
  @include('transfer._panel', [
    'id' => 'mapel',
    'ket' => 'Satu sheet per mapel; judul sheet = kode mapel. Isi: No, NISN, Nama, Kelas, S1–S6, Tertulis, Praktik.',
    'ekspor' => route('transfer.ekspor'), 'impor' => route('transfer.impor'),
    'accept' => '.xlsx,.xls', 'sem' => false, 'mapelLinks' => true,
    'catatan' => 'Judul sheet harus sama dengan kode mapel (mis. PAI, MTK).',
  ])
</div>

<p class="xtip">Nilai tidak masuk? Di pratinjau, perhatikan peringatan kuning: NISN yang tidak ditemukan ditampilkan lengkap dengan namanya, sehingga mudah dicari di file.</p>

@push('scripts')
<script>
(function () {
  var tabs = [].slice.call(document.querySelectorAll('.xtabs button')), ps = [].slice.call(document.querySelectorAll('.xp'));
  function show(id) {
    tabs.forEach(function (b) { b.classList.toggle('on', b.dataset.t === id); });
    ps.forEach(function (p) { p.hidden = p.id !== 'xp-' + id; });
    try { history.replaceState(null, '', '#' + id); } catch (e) {}
  }
  tabs.forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.t); }); });
  var h = location.hash.slice(1);
  show(['semester', 'riwayat', 'mapel'].indexOf(h) > -1 ? h : 'semester');
})();
</script>
@endpush
@endsection
