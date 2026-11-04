@use('App\Services\Grading')
@php
  // Angka semester: bulat tanpa desimal, selain itu koma desimal
  $a = fn ($x) => $x === null ? '' : (floor($x) == $x ? (string) (int) $x : str_replace('.', ',', (string) round($x, 2)));
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Cetak Nilai Semester</title>
<link rel="stylesheet" href="{{ asset('css/cetak-semester.css') }}">
<style id="pg-style">@page{size:210mm 297mm;margin:0}</style>
</head>
<body>
<div class="bar noprint">
  <button id="btn-print" type="button">Cetak / Simpan PDF</button>
  <label>Kertas
    <select id="kertas">
      @foreach (['a4' => 'A4 (210 × 297 mm)', 'f4' => 'F4 / Folio (215 × 330 mm)', 'letter' => 'Letter (216 × 279 mm)', 'legal' => 'Legal (216 × 356 mm)'] as $v => $t)
        <option value="{{ $v }}" @selected($kertas === $v)>{{ $t }}</option>
      @endforeach
    </select>
  </label>
  <label>Orientasi
    <select id="orientasi">
      <option value="auto" @selected($orientasi === 'auto')>Otomatis (paling pas)</option>
      <option value="landscape" @selected($orientasi === 'landscape')>Landscape</option>
      <option value="portrait" @selected($orientasi === 'portrait')>Potret</option>
    </select>
  </label>
  <span id="info"></span>
  <small>Di dialog cetak: Margin “Tidak ada”, Skala 100%.</small>
  <span class="sp"></span>
  <a href="javascript:window.close()">Tutup</a>
</div>
<div id="warn" class="warn noprint" hidden></div>

<details class="urut noprint" id="urutan" data-url="{{ route('semester.urutan') }}" data-default="{{ $subjects->pluck('kode')->toJson() }}">
  <summary>Atur urutan kolom mapel untuk cetak (seret untuk memindahkan)</summary>
  <p>Hanya berlaku untuk cetak nilai semester ini: urutan mapel di SKL, rapor, transkrip, dan menu lain <b>tidak berubah</b>. Urutan disimpan per semester dan per tahun pelajaran. Seret kotak mapel ke posisi baru (atau pilih kotaknya lalu tekan panah kiri/kanan), lalu klik <b>Simpan urutan</b>.</p>
  @foreach ($sems as $n)
    <div class="urut-row" data-sem="{{ $n }}">
      <b>Semester {{ $n }}</b>
      <ul class="chips">@foreach ($orders[$n] as $m)<li class="chip" draggable="true" tabindex="0" data-kode="{{ $m->kode }}" title="{{ $m->nama }}">{{ $m->kode }}</li>@endforeach</ul>
      <button type="button" data-act="simpan">Simpan urutan</button>
      <button type="button" data-act="reset" class="s">Kembali ke urutan mapel</button>
      <span class="st"></span>
    </div>
  @endforeach
</details>

<main id="sheets">
@forelse ($sheets as $sh)
  <section class="sheet" data-sem="{{ $sh['sem'] }}">
    <div class="fit">
      <div class="ttl"><span>Kelas {{ $sh['kelas'] }}</span><span>Semester {{ $sh['sem'] }}</span></div>
      <table class="nt">
        <thead><tr><th class="no">No</th><th class="nm">Nama</th>@foreach ($sh['subjects'] as $m)<th class="v" data-kode="{{ $m->kode }}">{{ $m->kode }}</th>@endforeach<th class="v" data-end>Jumlah</th><th class="v">Rerata</th></tr></thead>
        <tbody>
        @foreach ($sh['rows'] as $r)
          @php $ada = collect($r['v'])->contains(fn ($x) => $x !== null); @endphp
          <tr><td class="no">{{ $r['no'] }}</td><td class="nm">{{ $r['nama'] }}</td>@foreach ($r['v'] as $ci => $v)<td class="v" data-kode="{{ $sh['subjects'][$ci]->kode }}">{{ $a($v) }}</td>@endforeach<td class="v" data-end>{{ $ada ? $a($r['jumlah']) : '' }}</td><td class="v"><b>{{ $ada ? Grading::fmt($r['avg']) : '' }}</b></td></tr>
        @endforeach
          <tr class="rata"><td colspan="2" class="nm">Rata-rata kelas</td>@foreach ($sh['rata'] as $ci => $v)<td class="v" data-kode="{{ $sh['subjects'][$ci]->kode }}">{{ $v === null ? '' : Grading::fmt($v) }}</td>@endforeach<td class="v" data-end></td><td class="v"></td></tr>
        </tbody>
      </table>
    </div>
  </section>
@empty
  <p class="noprint" style="padding:20px">Tidak ada siswa pada kelas yang dipilih.</p>
@endforelse
</main>
<script src="{{ asset('js/cetak-semester.js') }}"></script>
</body>
</html>
