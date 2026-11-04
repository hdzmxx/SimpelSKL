@extends('layouts.app')
@section('content')
@php $edit = $subject->exists; @endphp
<h2 class="t">{{ $edit ? 'Edit' : 'Tambah' }} mapel</h2>
<form class="card" method="POST" action="{{ $edit ? route('mapel.update', $subject) : route('mapel.store') }}">@csrf @if ($edit) @method('PUT') @endif
  <div class="grid2">
    <label class="f">Kode singkat<input name="kode" value="{{ old('kode', $subject->kode) }}" required></label>
    <label class="f">Nama mapel<input name="nama" value="{{ old('nama', $subject->nama) }}" required></label>
    <label class="f">Nama di surat rapor (opsional)<input name="nama_rapor" value="{{ old('nama_rapor', $subject->nama_rapor) }}"></label>
    <label class="f">Urutan<input type="number" name="urut" value="{{ old('urut', $subject->urut) }}"></label>
  </div>
  <div style="display:grid;gap:6px;margin:12px 0">
    <label class="chk"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $subject->exists ? $subject->aktif : true))><b>Aktif</b> — tampil di input nilai, impor/ekspor, laporan, dan cetak</label>
    <label class="chk"><input type="checkbox" name="ada_praktik" value="1" @checked(old('ada_praktik', $subject->ada_praktik))>PSAJ memiliki ujian praktik (tertulis + praktik)</label>
    <label class="chk"><input type="checkbox" name="tampil_di_rapor" value="1" @checked(old('tampil_di_rapor', $subject->tampil_di_rapor))>Tampilkan di surat keterangan nilai rapor (bisa diubah saat cetak)</label>
  </div>
  <p class="hint">Pembobotan diatur sekali di halaman <a href="{{ route('mapel.index') }}">Mapel &amp; Bobot</a> (Pembobotan global), bukan per mapel.</p>
  <div class="warn">Mapel nonaktif tidak muncul di menu input, impor/ekspor, laporan, peringkat, maupun dokumen cetak. Data nilai yang sudah ada tetap tersimpan.</div>
  <button class="p">Simpan</button> <a class="btn" href="{{ route('mapel.index') }}">Batal</a>
</form>
@endsection
