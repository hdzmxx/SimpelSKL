<!doctype html>
<html lang="id">
@php
  $pengaturan = \App\Models\Setting::map();
  $logoAplikasi = $pengaturan['logo_aplikasi'] ?? '';
  $namaSekolah = $pengaturan['nama_sekolah'] ?? '';
@endphp
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#087e87">
  <title>Masuk · SimpelSKL</title>
  @if ($logoAplikasi)
    <link rel="icon" href="{{ asset('uploads/' . $logoAplikasi) }}">
  @else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
  @endif
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="login-body">
<main class="login-page">
  <section class="login-welcome" aria-labelledby="welcome-title">
    <div class="login-gear-backdrop" aria-hidden="true">
      <svg class="login-gear-source" viewBox="0 0 800 900" preserveAspectRatio="xMidYMid slice">
        <defs>
          <g id="login-gear-shape">
            <path class="gear-outline" d="M354.53 -62.51L357.47 -42.63L465.22 -40.7L465.22 40.7L357.47 42.63L354.53 62.51L354.53 62.51L350.49 82.21L451.09 120.87L423.25 197.36L321.33 162.32L311.77 180L311.77 180L301.24 197.12L382.54 267.86L330.22 330.22L246.44 262.43L231.4 275.78L231.4 275.78L215.65 288.26L267.86 382.54L197.36 423.25L141.82 330.89L123.13 338.29L123.13 338.29L104.05 344.64L120.87 451.09L40.7 465.22L20.1 359.44L0 360L0 360L-20.1 359.44L-40.7 465.22L-120.87 451.09L-104.05 344.64L-123.13 338.29L-123.13 338.29L-141.82 330.89L-197.36 423.25L-267.86 382.54L-215.65 288.26L-231.4 275.78L-231.4 275.78L-246.44 262.43L-330.22 330.22L-382.54 267.86L-301.24 197.12L-311.77 180L-311.77 180L-321.33 162.32L-423.25 197.36L-451.09 120.87L-350.49 82.21L-354.53 62.51L-354.53 62.51L-357.47 42.63L-465.22 40.7L-465.22 -40.7L-357.47 -42.63L-354.53 -62.51L-354.53 -62.51L-350.49 -82.21L-451.09 -120.87L-423.25 -197.36L-321.33 -162.32L-311.77 -180L-311.77 -180L-301.24 -197.12L-382.54 -267.86L-330.22 -330.22L-246.44 -262.43L-231.4 -275.78L-231.4 -275.78L-215.65 -288.26L-267.86 -382.54L-197.36 -423.25L-141.82 -330.89L-123.13 -338.29L-123.13 -338.29L-104.05 -344.64L-120.87 -451.09L-40.7 -465.22L-20.1 -359.44L0 -360L0 -360L20.1 -359.44L40.7 -465.22L120.87 -451.09L104.05 -344.64L123.13 -338.29L123.13 -338.29L141.82 -330.89L197.36 -423.25L267.86 -382.54L215.65 -288.26L231.4 -275.78L231.4 -275.78L246.44 -262.43L330.22 -330.22L382.54 -267.86L301.24 -197.12L311.77 -180L311.77 -180L321.33 -162.32L423.25 -197.36L451.09 -120.87L350.49 -82.21L354.53 -62.51Z"/>
            <circle class="gear-detail" r="285"/>
            <circle class="gear-detail gear-hub" r="98"/>
            <circle class="gear-detail gear-center" r="24"/>
            <path class="gear-spokes" d="M0-105V-278M0 105V278M-91-52L-241-139M91 52L241 139M-91 52L-241 139M91-52L241-139"/>
          </g>
        </defs>
        <g transform="translate(110 450) scale(.7)">
          <g class="gear-wheel gear-wheel-left"><use href="#login-gear-shape"/></g>
        </g>
        <g transform="translate(698 450) rotate(10) scale(.7)">
          <g class="gear-wheel gear-wheel-right"><use href="#login-gear-shape"/></g>
        </g>
      </svg>
      <svg class="login-gear-artwork" viewBox="0 0 800 900" preserveAspectRatio="xMidYMid slice">
        <g transform="translate(400 450) scale(1.5) translate(-440.9 -403.8)">
          <g transform="translate(210 210) rotate(40)">
            <path class="gear-silhouette gear-rotor-large" data-teeth="18" data-hole="46"/>
          </g>
          <g transform="translate(455.134 415.706) rotate(25)">
            <path class="gear-silhouette gear-rotor-medium" data-teeth="12" data-hole="32"/>
          </g>
          <g transform="translate(364.974 609.057) rotate(2.5)">
            <path class="gear-silhouette gear-rotor-small" data-teeth="8" data-hole="22"/>
          </g>
        </g>
      </svg>
    </div>
    <div class="login-welcome-content">
      <div class="login-school-brand">
        @if ($logoAplikasi)
          <img src="{{ asset('uploads/' . $logoAplikasi) }}" alt="Logo SimpelSKL">
        @else
          <span class="login-brand-mark" aria-hidden="true">SKL</span>
        @endif
        <div>
          <strong>{{ $namaSekolah ?: 'SimpelSKL' }}</strong>
          @if ($namaSekolah)<span class="login-kicker">PORTAL PENILAIAN &amp; KELULUSAN</span>@endif
        </div>
      </div>
      <div class="login-welcome-copy">
        <h1 id="welcome-title">Masa Depan Generasi Unggul Dimulai dari Sini.</h1>
        <p>Platform terintegrasi untuk membantu manajemen sekolah memverifikasi data nilai, memantau pencapaian kompetensi lulusan, dan menyiapkan proses kelulusan dengan cepat, akurat, serta transparan.</p>
      </div>
    </div>
    <div class="login-welcome-footer">
      <p>SimpelSKL <span>&bull; Sistem Informasi Manajemen Penilaian Standar Kompetensi Lulusan</span></p>
    </div>
    <span class="login-orbit login-orbit-one" aria-hidden="true"></span>
    <span class="login-orbit login-orbit-two" aria-hidden="true"></span>
  </section>

  <section class="login-form-panel" aria-labelledby="login-title">
    <div class="login-card">
      <header class="login-form-heading">
        <h2 id="login-title">Selamat Datang Kembali</h2>
        <p>Silakan masukkan kredensial akun Anda untuk mengakses panel manajemen.</p>
      </header>

      @if ($errors->any())
        <div class="login-error" role="alert">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ url('/login') }}" class="login-form">
        @csrf
        <div class="input-group-wrapper">
          <label for="login-email">Email / Nama Pengguna</label>
          <div class="login-input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            <input id="login-email" type="text" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="cth: admin@sekolah.sch.id" autofocus required>
          </div>
        </div>

        <div class="input-group-wrapper">
          <label for="login-password">Password</label>
          <div class="login-input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Masukkan password Anda" required>
            <button type="button" class="password-toggle" id="password-toggle" aria-label="Tampilkan password" aria-pressed="false">
              <svg class="password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
              <svg class="password-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.2 0 9.5 6 9.5 6a17 17 0 0 1-3.1 3.8M6.2 6.2C3.8 7.8 2.5 12 2.5 12s3.3 6 9.5 6c.9 0 1.7-.1 2.4-.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
            </button>
          </div>
        </div>

        <div class="login-remember-row">
          <label class="remember-checkbox-label" for="remember">
            <input type="checkbox" name="remember" id="remember" value="1">
            <span>Ingat perangkat ini</span>
          </label>
        </div>

        <p class="capslock-reminder" id="capslock-reminder" role="status" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 17H3L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg>Caps Lock aktif. Password membedakan huruf besar dan kecil.</p>
        <button class="login-submit" type="submit">
          <span>Masuk ke Dashboard</span>
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </button>
      </form>

      <div class="login-security">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        <span>Akses terbatas untuk administrator.</span>
      </div>
    </div>
    <footer class="login-copyright">© {{ date('Y') }} SimpelSKL - hdzmxx</footer>
  </section>
</main>
<script src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}"></script>
</body>
</html>
