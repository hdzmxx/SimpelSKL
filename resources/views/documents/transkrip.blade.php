@extends('layouts.print')
@section('title', 'Transkrip Nilai')
@section('docs')
@use('App\Models\Setting')
@use('App\Services\Grading')
@foreach ($students as $st)
<section class="doc">
  @include('documents._kop')
  <h2>TRANSKRIP NILAI</h2>
  <p class="no">NOMOR : {{ Setting::nomor('nomor_transkrip', $st) }}</p>
  <table class="kv">
    <tr><td>Satuan Pendidikan</td><td>:</td><td>{{ $s['nama_sekolah'] }}</td></tr>
    <tr><td>Nomor Pokok Sekolah Nasional</td><td>:</td><td>{{ $s['npsn'] }}</td></tr>
    <tr><td>Nama Lengkap</td><td>:</td><td @class(['nama-siswa' => $boldNama])>{{ $st->nama_cetak }}</td></tr>
    <tr><td>Tempat, tanggal lahir</td><td>:</td><td>{{ $st->tempat_lahir }}, {{ Grading::tanggal($st->tgl_lahir) }}</td></tr>
    <tr><td>Nomor Induk Siswa Nasional</td><td>:</td><td>{{ $st->nisn }}</td></tr>
    <tr><td>Nomor Ijazah</td><td>:</td><td>{{ $st->no_ijazah }}</td></tr>
    <tr><td>Tanggal Kelulusan</td><td>:</td><td>{{ $s['tgl_lulus'] }}</td></tr>
  </table>
  @include('documents._nilai')
  @include('documents._ttd', ['tempat' => $s['ttd_transkrip'], 'role' => 'Kepala,'])
</section>
@endforeach
@endsection
