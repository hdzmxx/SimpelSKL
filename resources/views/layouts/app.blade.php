<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'SimpelSKL')</title>
@php
  $applicationLogo = \App\Models\Setting::map()['logo_aplikasi'] ?? '';
  $faviconUrl = $applicationLogo ? asset('uploads/' . $applicationLogo) : asset('favicon.ico');
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $applicationLogo ? 'image/' . (strtolower(pathinfo($applicationLogo, PATHINFO_EXTENSION)) === 'jpg' ? 'jpeg' : strtolower(pathinfo($applicationLogo, PATHINFO_EXTENSION))) : 'image/x-icon' }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
<script>
  (function () {
    var mode = localStorage.getItem('nk-theme-mode') || 'system';
    var accent = localStorage.getItem('nk-theme-accent') || 'blue';
    var dark = mode === 'dark' || (mode === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    document.documentElement.dataset.accent = accent;
  })();
</script>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div id="app">
<aside>
  @php
    $ico = [
      'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
      'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.4"/><path d="M17 14c2.5 0 4 1.8 4 4.5"/>',
      'book' => '<path d="M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3z"/><path d="M5 17a3 3 0 0 1 3-3h11"/>',
      'swap' => '<path d="M7 4v13M7 4 3.5 7.5M7 4l3.5 3.5"/><path d="M17 20V7m0 13-3.5-3.5M17 20l3.5-3.5"/>',
      'table' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16"/>',
      'cal' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
      'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16 9.5"/>',
      'bars' => '<path d="M4 20V11h4v9M10 20V4h4v16M16 20v-6h4v6"/>',
      'print' => '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
      'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
      'sliders' => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
    ];
    $nav = [
      '' => [['dashboard', 'Ringkasan', ['dashboard'], 'grid']],
      'Data' => [['siswa.index', 'Data Siswa', ['siswa*'], 'users'], ['kehadiran', 'Kehadiran', ['kehadiran'], 'cal'], ['mapel.index', 'Mapel & Bobot', ['mapel*'], 'book']],
      'Nilai' => [['peringkat', 'Peringkat', ['peringkat'], 'bars'], ['kelulusan', 'Kelulusan', ['kelulusan*'], 'check'], ['sk-kelulusan', 'SK Kelulusan', ['sk-kelulusan*'], 'print']],
      'Dokumen' => [['cetak', 'Cetak Dokumen', ['cetak*'], 'print']],
      'Sistem' => [['tahun.index', 'Tahun Pelajaran', ['tahun*'], 'layers'], ['pengaturan', 'Pengaturan Website', ['pengaturan*'], 'sliders']],
    ];
    $years = \App\Models\AcademicYear::orderByDesc('nama')->get();
    $cy = \App\Support\Year::current();
    $siteSettings = \App\Models\Setting::map();
    $sekolah = $siteSettings['nama_sekolah'] ?? '';
    $logoAplikasi = $siteSettings['logo_aplikasi'] ?? '';
  @endphp
  <div class="brand">
    @if ($logoAplikasi)
      <img class="brand-logo" src="{{ asset('uploads/' . $logoAplikasi) }}" alt="Logo aplikasi">
    @else
      <span class="mk">SKL</span>
    @endif
    <div><b>SimpelSKL</b>@if ($sekolah)<small>{{ $sekolah }}</small>@endif</div>
  </div>
  <button type="button" class="admin-profile-button" id="admin-profile-open" aria-haspopup="dialog" aria-controls="admin-profile-dialog">
      <span class="me-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}</span>
      <span class="admin-profile-identity"><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></span>
      <span class="admin-profile-edit">Kelola akun</span>
  </button>
  @if ($years->count())
  <form method="POST" action="{{ route('tahun.pilih') }}" class="yrsel">@csrf
    <span class="yrsel-label" id="academic-year-label">Tahun pelajaran</span>
    <div class="yrsel-picker">
      <select name="id" aria-labelledby="academic-year-label">
        @foreach ($years as $y)<option value="{{ $y->id }}" @selected($cy && $cy->id === $y->id)>{{ $y->nama }}{{ $y->aktif ? ' (aktif)' : '' }}</option>@endforeach
      </select>
      <button class="yrsel-trigger" type="button" aria-labelledby="academic-year-label" aria-haspopup="menu" aria-expanded="false">
        <span class="yrsel-current"></span>
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3.5 6 4.5 4 4.5-4"/></svg>
      </button>
      <div class="yrsel-menu" role="menu" aria-labelledby="academic-year-label" hidden>
        @foreach ($years as $y)
          <button type="button" role="menuitemradio" aria-checked="{{ $cy && $cy->id === $y->id ? 'true' : 'false' }}" data-year-id="{{ $y->id }}">{{ $y->nama }}{{ $y->aktif ? ' (aktif)' : '' }}</button>
        @endforeach
      </div>
    </div>
  </form>
  @endif
  @php $inputSubjects = \App\Models\Subject::aktif()->orderBy('urut')->get(); @endphp
  <nav>
    @foreach ($nav as $gname => $items)
      @if ($gname === '')
        @foreach ($items as [$rt, $label, $pat, $ic])
          <a class="nav-home {{ request()->routeIs(...$pat) ? 'on' : '' }}" href="{{ route($rt) }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico[$ic] !!}</svg>{{ $label }}</a>
        @endforeach
      @elseif ($gname === 'Nilai')
        <section class="nav-section">
          <div class="nav-section-title">Nilai</div>
          <a class="{{ request()->routeIs('nilai.*') ? 'on' : '' }}" href="{{ $inputSubjects->isNotEmpty() ? route('nilai.grid', $inputSubjects->first()) : route('mapel.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico['table'] !!}</svg>Input Nilai</a>
          <a class="{{ request()->routeIs('transfer*', 'impor*') ? 'on' : '' }}" href="{{ route('transfer') }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico['swap'] !!}</svg>Impor / Ekspor Nilai</a>
          <div class="nav-subsection-title">Rekap &amp; laporan nilai</div>
          <a class="{{ request()->routeIs('master') ? 'on' : '' }}" href="{{ route('master') }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico['book'] !!}</svg>Master Nilai</a>
          <a class="{{ request()->routeIs('semester*') ? 'on' : '' }}" href="{{ route('semester') }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico['cal'] !!}</svg>Nilai per Semester</a>
          <a class="{{ request()->routeIs('akhir') ? 'on' : '' }}" href="{{ route('akhir') }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico['bars'] !!}</svg>Nilai Akhir</a>
          @foreach ($items as [$rt, $label, $pat, $ic])
            <a class="{{ request()->routeIs(...$pat) ? 'on' : '' }}" href="{{ route($rt) }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico[$ic] !!}</svg>{{ $label }}</a>
          @endforeach
        </section>
      @else
        <section class="nav-section">
          <div class="nav-section-title">{{ $gname }}</div>
          @foreach ($items as [$rt, $label, $pat, $ic])
            <a class="{{ request()->routeIs(...$pat) ? 'on' : '' }}" href="{{ route($rt) }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $ico[$ic] !!}</svg>{{ $label }}</a>
          @endforeach
        </section>
      @endif
    @endforeach
  </nav>
  <div class="me">
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg><span>Keluar</span></button></form>
  </div>
</aside>
<dialog class="admin-profile-dialog" id="admin-profile-dialog" @if ($errors->has('name') || $errors->has('lama') || $errors->has('baru') || $errors->has('baru_confirmation') || in_array(session('ok'), ['Nama admin berhasil diperbarui.', 'Password diganti.'], true)) data-reopen="1" @endif>
  <div class="admin-dialog-heading">
    <div><span class="dashboard-eyebrow">AKUN ADMIN</span><h2>Pengaturan profil</h2><p>{{ auth()->user()->email }}</p></div>
    <button type="button" class="admin-dialog-close" data-admin-dialog-close aria-label="Tutup">×</button>
  </div>
  @if ($errors->any())
    <div class="flash err admin-dialog-errors">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
  @endif
  @if (session('ok') && (session('ok') === 'Nama admin berhasil diperbarui.' || session('ok') === 'Password diganti.'))
    <div class="flash admin-dialog-success">{{ session('ok') }}</div>
  @endif
  <div class="admin-dialog-forms">
    <form method="POST" action="{{ route('pengaturan.admin.nama') }}" class="admin-profile-form">@csrf
      <h3>Nama admin</h3>
      <label>Nama yang ditampilkan<input name="name" value="{{ old('name', auth()->user()->name) }}" maxlength="255" required></label>
      <button class="p">Simpan nama</button>
    </form>
    <form method="POST" action="{{ route('pengaturan.password') }}" class="admin-profile-form">@csrf
      <h3>Ganti password</h3>
      <label>Password saat ini<input type="password" name="lama" autocomplete="current-password" required></label>
      <label>Password baru <small>(minimal 8 karakter)</small><input type="password" name="baru" minlength="8" autocomplete="new-password" required></label>
      <label>Ulangi password baru<input type="password" name="baru_confirmation" autocomplete="new-password" required></label>
      <button>Ganti password</button>
    </form>
  </div>
</dialog>
<main>
  @if (session('ok')) <div class="flash">{{ session('ok') }}</div> @endif
  @if ($errors->any()) <div class="flash err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div> @endif
  @yield('content')
</main>
</div>
<script src="{{ asset('js/cek-skl.js') }}" data-cek="{{ route('cetak.skl.cek') }}" data-kelulusan="{{ route('kelulusan') }}"></script>
<script src="{{ asset('js/appearance.js') }}"></script>
<script src="{{ asset('js/admin-profile.js') }}"></script>
<script src="{{ asset('js/academic-year-select.js') }}"></script>
@stack('scripts')
</body>
</html>
