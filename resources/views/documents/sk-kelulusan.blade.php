@extends('layouts.print')
@section('title', 'SK Kelulusan')
@section('docs')
@use('App\Services\Grading')
@php
  $school = $s['nama_sekolah'] ?: '........................................';
  $year = $s['tahun_ajaran'] ?: '........................';
  $title = "Kelulusan Peserta Didik Kelas IX {$school} Tahun Ajaran {$year}";
  $place = trim(explode(',', (string) $s['ttd_skl'])[0] ?? '');
  $skLegalKeys = array_keys($legalDefaults);
  $skLegalTexts = array_combine($skLegalKeys, array_map(fn ($key) => strtr(
    ($s[$key] ?? '') !== '' ? $s[$key] : $legalDefaults[$key],
    ['{sekolah}' => $school, '{tahun}' => $year]
  ), $skLegalKeys));
  $menimbangPoints = preg_split('/\r\n|\r|\n/', trim($skLegalTexts['sk_menimbang']));
  $mengingatPoints = preg_split('/\r\n|\r|\n/', trim($skLegalTexts['sk_mengingat']));
@endphp
<style>
@page landscape{size:A4 landscape;margin:8mm}
@page portrait{size:A4 portrait;margin:8mm}
.sk-cover{font:10.5pt/1.28 "Times New Roman",Times,serif}
.sk-cover .kop{margin-bottom:8px}
.sk-cover h2{text-decoration:none;margin:0;font-size:13pt}
.sk-cover h3{text-align:center;margin:2px 0;font-size:12pt}
.sk-head{text-align:center;margin:0 0 10px}
.sk-consider{width:100%;border-collapse:collapse}
.sk-consider td{vertical-align:top;padding:2px 4px}
.sk-consider td:first-child{width:28mm}
.sk-consider td:nth-child(2){width:5mm}
.sk-consider ol{margin:0;padding-left:7mm}
.sk-consider li{padding-left:1mm;margin:0 0 2px;text-align:justify}
.sk-decree{text-align:justify}
.sk-decree .diktum{display:grid;grid-template-columns:22mm 5mm 1fr;gap:3px;margin:5px 0}
.sk-place{text-align:left;margin:12px 0 0 55%}
.sk-sign{margin:0 0 0 55%;width:70mm;text-align:center}
.sk-sign-space{height:18mm}
.sk-copies{font-size:9pt;margin-top:8px}
.sk-annex{page:landscape;width:281mm;min-height:194mm;margin:0 auto 12px;padding:0;font:8pt/1.15 "Times New Roman",Times,serif}
.sk-annex h2,.sk-annex h3{text-align:center;margin:2px 0;font-size:11pt}
.sk-annex .lampiran{font-size:9pt;line-height:1.2}
.sk-annex .sk-table{width:100%;border-collapse:collapse;table-layout:auto;margin-top:5px}
.sk-table th,.sk-table td{border:1px solid #000;padding:2px 3px;text-align:center;vertical-align:middle}
.sk-table th{font-weight:bold}
.sk-table td.nama{text-align:left;min-width:42mm}
.sk-table .small{font-size:7pt}
.sk-table tr.summary td{font-weight:bold}
.sk-annex-sign{margin:8px 8mm 0 auto;width:65mm;text-align:center;font-size:9pt}
.sk-annex-sign .space{height:12mm}
.sk-gender-count{margin:5px 8mm 0 auto;width:65mm;font-size:9pt}
.sk-simple{page:portrait;width:194mm;min-height:265mm;padding:0;font-size:9pt}
.sk-simple .sk-table td.nama{min-width:75mm}
@media print{
  .sk-cover{break-after:page}
  .sk-annex{break-after:page}
  .sk-annex:last-child{break-after:auto}
  .sk-table thead{display:table-header-group}
  .sk-table tr{break-inside:avoid}
}
</style>

<section class="doc sk-cover">
  @include('documents._kop')
  <div class="sk-head">
    <h2>KEPUTUSAN KEPALA {{ strtoupper($school) }}</h2>
    <div>NOMOR: {{ $s['sk_nomor'] }}</div>
    <h3>TENTANG</h3>
    <h3> {{ strtoupper($title) }}</h3>
  </div>
  <table class="sk-consider">
    <tr><td>Menimbang</td><td>:</td><td>
      <ol type="a">
        @foreach ($menimbangPoints as $point)
          @if (trim($point) !== '')<li>{{ trim($point) }}</li>@endif
        @endforeach
      </ol>
    </td></tr>
    <tr><td>Mengingat</td><td>:</td><td>
      <ol>
        @foreach ($mengingatPoints as $point)
          @if (trim($point) !== '')<li>{{ trim($point) }}</li>@endif
        @endforeach
      </ol>
    </td></tr>
    <tr><td>Memperhatikan</td><td>:</td><td>Rapat Dewan Pendidik {{ $school }} tanggal {{ $s['tanggal_rapat'] }}.</td></tr>
  </table>
  <div class="sk-decree">
    <p style="text-align:center;margin:5px 0"><b>MEMUTUSKAN</b></p>
    <div class="diktum"><b>Menetapkan</b><span>:</span><span>KEPUTUSAN KEPALA {{ strtoupper($school) }} TENTANG KELULUSAN PESERTA DIDIK KELAS IX TAHUN AJARAN {{ $year }}.</span></div>
    <div class="diktum"><b>PERTAMA</b><span>:</span><span>Peserta didik kelas IX {{ $school }} Tahun Ajaran {{ $year }} yang berjumlah {{ $students->count() }} orang dinyatakan <b>LULUS</b> dari {{ $school }}.</span></div>
    <div class="diktum"><b>KEDUA</b><span>:</span><span>Peserta didik yang dinyatakan lulus sebagaimana dimaksud pada diktum PERTAMA berhak mendapatkan ijazah dan transkrip nilai yang dikeluarkan oleh {{ $school }}.</span></div>
    <div class="diktum"><b>KETIGA</b><span>:</span><span>Daftar peserta didik sebagaimana dimaksud pada diktum PERTAMA tercantum dalam lampiran yang merupakan bagian tidak terpisahkan dari keputusan ini.</span></div>
    <div class="diktum"><b>KEEMPAT</b><span>:</span><span>Hal-hal yang belum diatur dan/atau perubahan atas keputusan ini akan ditetapkan kemudian oleh Kepala Sekolah.</span></div>
    <div class="diktum"><b>KELIMA</b><span>:</span><span>Keputusan ini mulai berlaku sejak tanggal ditetapkan.</span></div>
  </div>
  <div class="sk-place">Ditetapkan di : {{ $place }}<br>Pada tanggal : {{ $s['sk_tanggal'] }}</div>
  <div class="sk-sign">KEPALA SEKOLAH<div class="sk-sign-space"></div><b class="signature-name">{{ $s['kepsek'] }}</b><br>NIP. {{ $s['nip'] }}</div>
  <div class="sk-copies">Tembusan Yth.:<br>1. Kepala Dinas Pendidikan, Kepemudaan dan Olahraga Kabupaten Kebumen<br>2. Ketua Komite {{ $school }}<br>3. Yang bersangkutan<br>4. Arsip</div>
</section>

@if ($lampiran === 'nilai')
<section class="sk-annex">
  <div class="lampiran">Lampiran : Surat Keputusan Kepala {{ $school }}<br>Nomor : {{ $s['sk_nomor'] }}<br>Tanggal : {{ $s['sk_tanggal'] }}<br>Tentang : {{ $title }}</div>
  <h2>PENETAPAN KELULUSAN PESERTA DIDIK KELAS IX</h2>
  <h3>{{ strtoupper($school) }} · TAHUN AJARAN {{ $year }}</h3>
  <table class="sk-table">
    <thead>
      <tr>
        <th rowspan="3">No</th><th rowspan="3">Kelas</th><th rowspan="3">Nama</th><th rowspan="3">JK</th><th rowspan="3">NIPD</th><th rowspan="3">NISN</th>
        <th colspan="{{ $subjects->count() + 3 }}">Kriteria Kelulusan</th><th rowspan="3">Kelulusan</th>
      </tr>
      <tr><th colspan="{{ $subjects->count() + 2 }}">Nilai</th><th rowspan="2">Kehadiran</th></tr>
      <tr>@foreach ($subjects as $subject)<th>{{ $subject->kode }}</th>@endforeach<th>Jumlah</th><th>Rata-rata</th></tr>
    </thead>
    <tbody>
      <tr class="small"><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td>@for ($i = 1; $i <= $subjects->count() + 3; $i++)<td>{{ $i + 6 }}</td>@endfor<td>{{ $subjects->count() + 10 }}</td></tr>
      @foreach ($students as $index => $student)
        <tr>
          <td>{{ $index + 1 }}</td><td>{{ $student->kelas }}</td><td class="nama">{{ $student->nama_cetak }}</td>
          <td>{{ $student->jenis_kelamin }}</td><td>{{ $student->nis }}</td><td>{{ $student->nisn }}</td>
          @foreach ($subjects as $subject)<td>{{ Grading::fmt($student->fin[$subject->id] ?? 0) }}</td>@endforeach
          <td>{{ Grading::fmt($student->jumlah_nilai) }}</td><td>{{ Grading::fmt($student->avg) }}</td>
          <td>{{ $student->kehadiran }}</td><td>Lulus</td>
        </tr>
      @endforeach
      @foreach (['rata_rata' => 'Rata-rata', 'terendah' => 'Terendah', 'tertinggi' => 'Tertinggi'] as $statKey => $label)
        <tr class="summary"><td colspan="6">{{ $label }}</td>
          @foreach ($subjects as $subject)<td>{{ Grading::fmt($statistik[$subject->id][$statKey]) }}</td>@endforeach
          <td>{{ Grading::fmt($statistik['jumlah'][$statKey]) }}</td><td>{{ Grading::fmt($statistik['rata_rata'][$statKey]) }}</td><td colspan="2"></td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="sk-annex-sign">Kepala Sekolah,<div class="space"></div><b class="signature-name">{{ $s['kepsek'] }}</b><br>NIP. {{ $s['nip'] }}</div>
  <div class="sk-gender-count">Rekap jumlah kelulusan:<br>Laki-laki : {{ $laki }}<br>Perempuan : {{ $perempuan }}<br>Jumlah : {{ $students->count() }}</div>
</section>
@else
<section class="sk-annex sk-simple">
  <div class="lampiran">Lampiran : Surat Keputusan Kepala {{ $school }}<br>Nomor : {{ $s['sk_nomor'] }}<br>Tanggal : {{ $s['sk_tanggal'] }}<br>Tentang : {{ $title }}</div>
  <h2>DAFTAR PESERTA DIDIK KELAS IX YANG DINYATAKAN LULUS</h2>
  <h3>{{ strtoupper($school) }} · TAHUN AJARAN {{ $year }}</h3>
  <table class="sk-table">
    <thead><tr><th>No</th><th>Kelas</th><th>Nama</th><th>JK</th><th>NIPD</th><th>NISN</th><th>Keterangan</th></tr></thead>
    <tbody>
      <tr class="small"><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td></tr>
      @foreach ($students as $index => $student)
        <tr><td>{{ $index + 1 }}</td><td>{{ $student->kelas }}</td><td class="nama">{{ $student->nama_cetak }}</td><td>{{ $student->jenis_kelamin }}</td><td>{{ $student->nis }}</td><td>{{ $student->nisn }}</td><td>Lulus</td></tr>
      @endforeach
    </tbody>
  </table>
  <div class="sk-annex-sign">Mengetahui,<br>Kepala {{ $school }}<div class="space"></div><b class="signature-name">{{ $s['kepsek'] }}</b><br>NIP. {{ $s['nip'] }}</div>
  <div class="sk-gender-count">Rekap jumlah kelulusan:<br>Laki-laki : {{ $laki }}<br>Perempuan : {{ $perempuan }}<br>Jumlah : {{ $students->count() }}</div>
</section>
@endif
@endsection
