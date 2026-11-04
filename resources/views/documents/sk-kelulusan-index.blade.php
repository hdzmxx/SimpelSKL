@extends('layouts.app')
@section('content')
@php
  $skLegalDefaults = array_map(fn ($text) => strtr($text, [
    '{sekolah}' => $s['nama_sekolah'] ?: '........................................',
    '{tahun}' => $s['tahun_ajaran'] ?: '........................',
  ]), $legalDefaults);
@endphp
<div class="documents-page">
<div class="documents-heading">
  <div><h2 class="t">SK Kelulusan</h2><p class="hint">Cetak Surat Keputusan dan lampiran peserta didik yang telah ditetapkan <b>Lulus</b>.</p></div>
  <a class="btn" href="{{ route('kelulusan') }}">Kembali ke Kelulusan</a>
</div>

<div class="cards">
  <div class="stat"><b>{{ $total }}</b><span>Peserta didik lulus</span></div>
  <div class="stat"><b>{{ $laki }}</b><span>Laki-laki</span></div>
  <div class="stat"><b>{{ $perempuan }}</b><span>Perempuan</span></div>
</div>

@if ($total)
  <div class="card documents-card">
    <div class="documents-section-title"><div><h3>Pilihan cetak</h3><p>Tentukan format nama dan jenis lampiran yang akan disertakan.</p></div></div>
    <form method="GET" target="_blank" action="{{ route('sk-kelulusan.cetak') }}" class="documents-sk-form">
      <label class="f">Format nama
        <select name="format_nama"><option value="title">Capitalized Each Word</option><option value="upper">UPPERCASE</option></select>
      </label>
      <div class="documents-sk-actions">
        <button class="p" name="lampiran" value="nilai">SK + lampiran nilai <small>Lanskap</small></button>
        <button class="p" name="lampiran" value="keterangan">SK + keterangan lulus <small>Portrait</small></button>
      </div>
    </form>
    <p class="documents-note">Nomor/tanggal SK, tanggal rapat, dan tanda tangan diatur pada bagian pengaturan dokumen di halaman ini.</p>
  </div>
@else
  <div class="warn">Belum ada siswa yang ditetapkan Lulus. Tetapkan hasil kelulusan terlebih dahulu di menu Kelulusan.</div>
@endif

<section class="documents-settings" id="pengaturan-dokumen">
  <div class="documents-section-title">
    <div>
      <h3>Pengaturan dokumen <span>· tahun pelajaran {{ \App\Support\Year::current()?->nama }}</span></h3>
      <p>Nomor SK, tanggal rapat dan kelulusan, poin Menimbang/Mengingat, tanda tangan, serta penomoran dokumen berlaku khusus untuk tahun pelajaran yang dipilih.</p>
    </div>
  </div>
  <details class="card documents-card documents-numbering">
    <summary>Contoh penomoran surat</summary>
    <div class="tw"><table class="t"><thead><tr><th>Dokumen</th><th>Format</th><th>Mulai di</th><th>Siswa no. 1, 2, 3</th></tr></thead><tbody>
      <tr><td>SKL</td><td>{kode}/{skl}/{tahun}</td><td>Nomor SKL siswa no. 1 = 402</td><td>400.3.11/402/2027, …/403/2027, …/404/2027</td></tr>
      <tr><td>Surat Nilai Rapor</td><td>{kode}/{skl}.1/{tahun}</td><td>mengikuti nomor SKL</td><td>400.3.11/402.1/2027, …/403.1/2027, …</td></tr>
      <tr><td>Transkrip</td><td>{kode}/{transkrip}/{tahun}</td><td>Nomor Transkrip siswa no. 1 = 600</td><td>400.3.11/600/2027, …/601/2027, …</td></tr>
    </tbody></table></div>
    <p class="documents-note">Urutan siswa memakai kolom No di data siswa. {kode} adalah kode klasifikasi dari dinas.</p>
  </details>
  <form class="documents-settings-form" method="POST" action="{{ route('cetak.pengaturan') }}">@csrf
    @foreach ($groups as $title => $fields)
      <div class="card documents-card"><h4>{{ $title }}</h4><div class="documents-filters">
        @foreach ($fields as $key => $label)
          <label class="f {{ isset($skLegalDefaults[$key]) ? 'f-sk-legal' : '' }}">{{ $label }}
            @if (isset($skLegalDefaults[$key]))
              <textarea name="{{ $key }}" rows="{{ $key === 'sk_mengingat' ? 14 : 6 }}" placeholder="Satu poin per baris">{{ old($key, ($s[$key] ?? '') !== '' ? $s[$key] : $skLegalDefaults[$key]) }}</textarea>
              <small>Gunakan satu baris untuk setiap poin. Penomoran a/b atau 1/2 dibuat otomatis saat dicetak. Placeholder {sekolah} dan {tahun} dapat digunakan.</small>
            @else
              <input name="{{ $key }}" value="{{ old($key, $s[$key] ?? '') }}">
            @endif
          </label>
        @endforeach
      </div></div>
    @endforeach
    <div class="documents-actions"><button class="p">Simpan pengaturan dokumen</button></div>
  </form>
</section>
</div>
@endsection
