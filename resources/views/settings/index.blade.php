@extends('layouts.app')
@section('title', 'Pengaturan Website')
@section('content')
<div class="settings-page">
<div class="settings-heading"><div><div class="dashboard-eyebrow">SISTEM</div><h2 class="t">Pengaturan Website</h2><p class="hint">Atur tampilan website, identitas sekolah, dan akun admin.</p></div></div>
<section class="card appearance-card">
  <div class="appearance-heading"><div><h3>Tampilan website</h3><p>Preferensi tampilan tersimpan di browser ini dan tidak mengubah tampilan pengguna lain.</p></div><button type="button" class="appearance-reset" id="appearance-reset">Kembalikan bawaan</button></div>
  <div class="appearance-controls">
    <fieldset class="appearance-field">
      <legend>Tema</legend>
      <div class="appearance-options">
        <label><input type="radio" name="theme-mode" value="system"><span class="appearance-choice-icon">◐</span><b>Ikuti perangkat</b><small>Otomatis terang atau gelap</small></label>
        <label><input type="radio" name="theme-mode" value="light"><span class="appearance-choice-icon">☀</span><b>Terang</b><small>Tampilan terang</small></label>
        <label><input type="radio" name="theme-mode" value="dark"><span class="appearance-choice-icon">☾</span><b>Gelap</b><small>Tampilan gelap</small></label>
      </div>
    </fieldset>
    <fieldset class="appearance-field">
      <legend>Kombinasi warna</legend>
      <div class="appearance-options appearance-palettes">
        <label><input type="radio" name="theme-accent" value="blue"><span class="palette-swatch blue"><i></i><i></i><i></i></span><b>Biru</b><small>Biru klasik</small></label>
        <label><input type="radio" name="theme-accent" value="green"><span class="palette-swatch green"><i></i><i></i><i></i></span><b>Hijau</b><small>Hijau segar</small></label>
        <label><input type="radio" name="theme-accent" value="purple"><span class="palette-swatch purple"><i></i><i></i><i></i></span><b>Ungu</b><small>Ungu tenang</small></label>
        <label><input type="radio" name="theme-accent" value="teal"><span class="palette-swatch teal"><i></i><i></i><i></i></span><b>Teal</b><small>Teal modern</small></label>
      </div>
    </fieldset>
  </div>
  <p class="appearance-status" id="appearance-status" aria-live="polite">Perubahan tampilan diterapkan otomatis.</p>
</section>
<h3 class="settings-section-title">Administrasi website</h3>
<p style="color:var(--mut);margin-top:-6px">Identitas sekolah dan logo berlaku untuk semua tahun pelajaran. Nama dan password admin dikelola melalui tombol profil di sidebar. Pengaturan nomor SK, tanggal kelulusan, tanda tangan, dan penomoran dokumen tersedia di menu <a href="{{ route('sk-kelulusan') }}"><b>SK Kelulusan</b></a>.</p>
<form method="POST" action="{{ route('pengaturan.update') }}">@csrf
  @foreach ($groups as $title => $fields)
    <div class="card"><h3 style="margin-top:0">{{ $title }}</h3><div class="grid2">
      @foreach ($fields as $key => $label)<label class="f">{{ $label }}<input name="{{ $key }}" value="{{ old($key, $s[$key] ?? '') }}"></label>@endforeach
    </div></div>
  @endforeach
  <button class="p">Simpan pengaturan</button>
</form>

<div class="card app-logo-card"><h3 style="margin-top:0">Logo aplikasi</h3>
  <p class="hint">Logo ini tampil di samping judul aplikasi pada sidebar. Pengaturan ini terpisah dari logo kop surat.</p>
  <div class="app-logo-controls">
    <div class="app-logo-preview">
      @if ($s['logo_aplikasi'] ?? '')
        <img src="{{ asset('uploads/' . $s['logo_aplikasi']) }}" alt="Pratinjau logo aplikasi">
      @else
        <span class="mk">SKL</span>
      @endif
      <span>SimpelSKL</span>
    </div>
    <div class="app-logo-actions">
      <form method="POST" action="{{ route('pengaturan.logo', 'aplikasi') }}" enctype="multipart/form-data" class="bar">@csrf
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required>
        <button class="p">Unggah logo</button>
      </form>
      <small>PNG, JPG, atau WEBP. Maksimal 2 MB. Logo disesuaikan agar pas dengan ikon sidebar.</small>
      @if ($s['logo_aplikasi'] ?? '')
        <form method="POST" action="{{ route('pengaturan.logo.hapus', 'aplikasi') }}">@csrf @method('DELETE')<button class="d s">Hapus logo aplikasi</button></form>
      @endif
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px"><h3 style="margin-top:0">Logo &amp; kop surat</h3>
  <div class="grid2">
    @foreach (['kiri', 'kanan'] as $side)
      <div><b>Logo {{ $side }}</b>@if ($side === 'kanan') <small style="color:var(--mut)">(opsional)</small>@endif
        <div class="lgbox" style="margin-top:6px">
          @if ($s['logo_' . $side])<img src="{{ asset('uploads/' . $s['logo_' . $side]) }}" alt="">@else<span style="color:var(--mut)">Belum ada</span>@endif
          <form method="POST" action="{{ route('pengaturan.logo', $side) }}" enctype="multipart/form-data" class="bar" style="margin:0">@csrf
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required><button>Unggah</button></form>
          @if ($s['logo_' . $side])<form method="POST" action="{{ route('pengaturan.logo.hapus', $side) }}">@csrf @method('DELETE')<button class="d s">Hapus</button></form>@endif
        </div></div>
    @endforeach
  </div>
  <h4>Pratinjau kop</h4>
  <div class="kopprev">@include('documents._kop')</div>
  <p style="color:var(--mut);margin-bottom:0">Logo PNG/JPG/WEBP maks. 1 MB. Kop ini dipakai di SKL, Transkrip, dan Surat Nilai Rapor.</p>
</div>

</div>
@endsection
