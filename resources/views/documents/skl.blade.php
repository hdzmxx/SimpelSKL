@extends('layouts.print')
@section('title', 'SKL')
@section('docs')
@use('App\Models\Setting')
@use('App\Services\Grading')
@foreach ($students as $st)
<section class="doc">
  @include('documents._kop')
  <h2>SURAT KETERANGAN LULUS</h2>
  <p class="no">NOMOR : {{ Setting::nomor('nomor_skl', $st) }}</p>
  <p>Yang bertanda tangan di bawah ini, Kepala {{ $s['nama_sekolah'] }}, Nomor Pokok Sekolah Nasional {{ $s['npsn'] }} {{ $s['wilayah'] }}, menerangkan bahwa :</p>
  <table class="kv">
    <tr><td>Nama</td><td>:</td><td @class(['nama-siswa' => $boldNama])>{{ $st->nama_cetak }}</td></tr>
    <tr><td>Tempat, tanggal lahir</td><td>:</td><td>{{ $st->tempat_lahir }}, {{ Grading::tanggal($st->tgl_lahir) }}</td></tr>
    <tr><td>Nomor Induk</td><td>:</td><td>{{ $st->nis }}</td></tr>
    <tr><td>Nomor Induk Siswa Nasional</td><td>:</td><td>{{ $st->nisn }}</td></tr>
  </table>
  <p>Berdasarkan Surat Keputusan Kepala {{ $s['nama_sekolah'] }} Nomor {{ $s['sk_nomor'] }} Tahun {{ $s['sk_tahun'] }} tertanggal {{ $s['sk_tanggal'] }}
    tentang kelulusan Peserta Didik {{ $s['nama_sekolah'] }} Tahun Ajaran {{ $s['tahun_ajaran'] }} dinyatakan <b>{{ $st->status_lulus === 'tidak' ? 'TIDAK LULUS' : 'LULUS' }}</b>, dengan nilai sebagai berikut:</p>
  @include('documents._nilai')
  <p>Demikian Surat Keterangan Lulus ini dibuat untuk dipergunakan sebagai persyaratan Sistem Penerimaan Murid Baru dan berlaku sampai diterbitkannya Ijazah.</p>
  @include('documents._ttd', ['tempat' => $s['ttd_skl'], 'role' => 'Kepala Sekolah,'])
</section>
@endforeach
@endsection
