@extends('layouts.print')
@section('title', 'Surat Keterangan Nilai Rapor')
@section('docs')
@use('App\Models\Setting')
@use('App\Services\Grading')
@foreach ($students as $st)
@php
  // Nilai kosong ditampilkan '-' tetapi tetap dihitung 0 dalam rata-rata lima semester.
  $sc = $st->scores->keyBy('subject_id');
  $mean = fn (array $a) => Grading::averageIncludingMissing($a);
  $f = fn ($x) => $x === null ? '-' : Grading::fmt($x);
  $val = fn ($m, $k) => $sc->get($m->id)?->{'sem' . ($k + 1)};
  $colAvg = [];
  foreach ([0, 1, 2, 3, 4] as $k) $colAvg[$k] = $mean($raporSubjects->map(fn ($m) => $val($m, $k))->all());
  $total = $mean($raporSubjects->map(fn ($m) => $mean(array_map(fn ($k) => $val($m, $k), [0, 1, 2, 3, 4])))->all());
@endphp
<section class="doc">
  @include('documents._kop')
  <h2>SURAT KETERANGAN NILAI RAPOR</h2>
  <p class="no">NOMOR : {{ Setting::nomor('nomor_rapor', $st) }}</p>
  <p>Yang bertanda tangan di bawah ini :</p>
  <table class="kv">
    <tr><td>Nama</td><td>:</td><td>{{ $s['kepsek'] }}</td></tr>
    <tr><td>NIP</td><td>:</td><td>{{ $s['nip'] }}</td></tr>
    <tr><td>Jabatan</td><td>:</td><td>Kepala Sekolah</td></tr>
    <tr><td>NPSN</td><td>:</td><td>{{ $s['npsn'] }}</td></tr>
  </table>
  <p>Menerangkan Nilai Rapor :</p>
  <table class="kv">
    <tr><td>Nama</td><td>:</td><td @class(['nama-siswa' => $boldNama])>{{ $st->nama_cetak }}</td></tr>
    <tr><td>NISN</td><td>:</td><td>{{ $st->nisn }}</td></tr>
  </table>
  <table class="grid">
    <thead>
      <tr><th rowspan="2" style="width:6%">No</th><th rowspan="2">Mata Pelajaran</th><th colspan="5">Nilai Rapor Semester</th><th rowspan="2" style="width:15%">Rata-rata Semester I-V</th></tr>
      <tr>@foreach (['I', 'II', 'III', 'IV', 'V'] as $r)<th style="width:7%">{{ $r }}</th>@endforeach</tr>
    </thead>
    <tbody>
    @foreach ($raporSubjects as $i => $m)
      @php $v = array_map(fn ($k) => $val($m, $k), [0, 1, 2, 3, 4]); @endphp
      <tr><td class="c">{{ $i + 1 }}</td><td>{{ $m->nama_rapor ?: $m->nama }}</td>
        @foreach ($v as $x)<td class="c">{{ $x === null ? '-' : $x + 0 }}</td>@endforeach
        <td class="c">{{ $f($mean($v)) }}</td></tr>
    @endforeach
    <tr><td colspan="2" class="c"><b>Rerata Total</b></td>@foreach ($colAvg as $x)<td class="c">{{ $f($x) }}</td>@endforeach<td class="c"><b>{{ $f($total) }}</b></td></tr>
    </tbody>
  </table>
  <p>Demikian surat keterangan nilai rapor ini kami buat sebenar-benarnya, untuk dapat dipergunakan sebagaimana mestinya.</p>
  @include('documents._ttd', ['tempat' => $s['ttd_rapor'], 'role' => 'Kepala ' . $s['nama_sekolah']])
</section>
@endforeach
@endsection
