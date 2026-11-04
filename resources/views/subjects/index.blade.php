@extends('layouts.app')
@section('content')
@php
  $firstNo = $subjects->first(fn ($m) => !$m->ada_praktik);
  $firstYes = $subjects->first(fn ($m) => $m->ada_praktik);
  $sem = old('bobot_semester', $firstNo->bobot_semester ?? $firstYes->bobot_semester ?? 60);
  $tNo = old('bobot_tertulis', $firstNo->bobot_tertulis ?? 40);
  $tYes = old('bobot_tertulis_praktik', $firstYes->bobot_tertulis ?? 12);
  $pYes = old('bobot_praktik', $firstYes->bobot_praktik ?? 28);
  $aktifIds = $subjects->filter(fn ($m) => $m->aktif)->pluck('id')->values();
  $no = 0;
@endphp
<h2 class="t">Mapel &amp; Bobot <span style="color:var(--mut);font-weight:400;font-size:14px">· {{ \App\Support\Year::current()?->nama }}</span></h2>

<form class="import-box" method="POST" action="{{ route('mapel.bobot') }}">@csrf
  <b>Pembobotan</b>
  <label style="display:flex;gap:4px;align-items:center;font-size:12px;color:var(--mut)">Sem
    <input type="number" step="any" name="bobot_semester" value="{{ $sem + 0 }}" required style="width:56px" title="Bobot rata-rata Sem 1–6 (%)">
  </label>
  <span style="color:var(--line)">|</span>
  <label style="display:flex;gap:4px;align-items:center;font-size:12px;color:var(--mut)" title="Mapel tanpa praktik">Tertulis
    <input type="number" step="any" name="bobot_tertulis" value="{{ $tNo + 0 }}" required style="width:56px">
  </label>
  <span style="color:var(--line)">|</span>
  <label style="display:flex;gap:4px;align-items:center;font-size:12px;color:var(--mut)" title="Mapel dengan praktik — tertulis">Tertulis+P
    <input type="number" step="any" name="bobot_tertulis_praktik" value="{{ $tYes + 0 }}" required style="width:56px">
  </label>
  <label style="display:flex;gap:4px;align-items:center;font-size:12px;color:var(--mut)" title="Mapel dengan praktik — praktik">Praktik
    <input type="number" step="any" name="bobot_praktik" value="{{ $pYes + 0 }}" required style="width:56px">
  </label>
  <button class="p">Terapkan</button>
  <span class="help">Sem + tertulis = 100% (tanpa praktik). Sem + tertulis + praktik = 100% (ada praktik).</span>
</form>

<div class="tb">
  <span class="hint" style="margin:0">Mapel nonaktif tetap tampil di sini (warna pudar) dan bisa diaktifkan lagi kapan saja; di menu lain disembunyikan. Pengaturan aktif/nonaktif berlaku per tahun pelajaran.</span>
  <span class="sp"></span>
  <a class="btn p" href="{{ route('mapel.create') }}">+ Tambah mapel</a>
</div>
<div class="tw"><table class="t">
  <thead><tr><th>Urutan</th><th>Status</th><th>Kode</th><th>Nama mapel</th><th>PSAJ</th><th class="n">Sem %</th><th class="n">Tertulis %</th><th class="n">Praktik %</th><th>Di surat rapor</th><th>Aksi</th></tr></thead>
  <tbody>
  @foreach ($subjects as $m)
    <tr @if (!$m->aktif) style="opacity:.55" @endif>
      <td class="nw">@if ($m->aktif){{ ++$no }}@else<span style="color:var(--mut)">–</span>@endif
        <form class="inline" method="POST" action="{{ route('mapel.pindah', ['subject' => $m, 'arah' => 'naik']) }}">@csrf<button title="Geser ke kiri / atas" @disabled(!$m->aktif || $m->id === $aktifIds->first())>▲</button></form>
        <form class="inline" method="POST" action="{{ route('mapel.pindah', ['subject' => $m, 'arah' => 'turun']) }}">@csrf<button title="Geser ke kanan / bawah" @disabled(!$m->aktif || $m->id === $aktifIds->last())>▼</button></form></td>
      <td>@if ($m->aktif)<span style="color:var(--ok);font-weight:600">Aktif</span>@else<span style="color:var(--mut)">Nonaktif</span>@endif</td>
      <td>{{ $m->kode }}</td><td class="wrap">{{ $m->nama }}</td><td>{{ $m->ada_praktik ? 'Tertulis + praktik' : 'Tertulis saja' }}</td>
      <td class="n">{{ $m->bobot_semester + 0 }}</td><td class="n">{{ $m->bobot_tertulis + 0 }}</td><td class="n">{{ $m->ada_praktik ? $m->bobot_praktik + 0 : '-' }}</td>
      <td>{{ $m->tampil_di_rapor ? 'Ya' : '-' }}</td>
      <td class="nw">
        @if ($m->aktif)<a class="btn" href="{{ route('nilai.grid', $m) }}">Nilai</a>@endif
        <form class="inline" method="POST" action="{{ route('mapel.aktif', $m) }}">@csrf<button class="{{ $m->aktif ? '' : 'p' }}">{{ $m->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
        <a class="btn" href="{{ route('mapel.edit', $m) }}">Edit</a>
        <form class="inline" method="POST" action="{{ route('mapel.destroy', $m) }}" onsubmit="return confirm('Hapus mapel {{ addslashes($m->nama) }}? Semua nilai mapel ini ikut terhapus.')">@csrf @method('DELETE')<button class="d">Hapus</button></form>
      </td>
    </tr>
  @endforeach
  </tbody></table></div>
<p class="hint">Nomor urutan hanya menghitung mapel aktif; mapel nonaktif tidak mengambil nomor. Urutan ini dipakai di SKL, rapor, transkrip, dan tabel lain.</p>

<h3 style="margin:22px 0 6px">Urutan kolom per semester <span style="color:var(--mut);font-weight:400;font-size:13px">· khusus cetak nilai semester</span></h3>
<div class="card" id="urutan-sem" data-url="{{ route('semester.urutan') }}" data-token="{{ csrf_token() }}" data-default="{{ $aktif->pluck('kode')->toJson() }}">
  <p class="hint" style="margin-top:0">Urutan mapel di rapor kadang berbeda tiap semester. Seret kotak mapel ke posisi yang diinginkan (atau pilih kotaknya lalu tekan panah kiri/kanan), lalu klik <b>Simpan</b>. Pengaturan ini hanya mengubah kolom di <b>cetak nilai semester</b>; urutan di SKL, rapor, dan transkrip tidak berubah. Berlaku per tahun pelajaran dan hanya untuk mapel aktif.</p>
  @foreach (range(1, 6) as $n)
    <div class="uc-row" data-sem="{{ $n }}">
      <b>Semester {{ $n }}</b>
      <ul class="uc-chips">@foreach ($orders[$n] as $m)<li class="uc-chip" draggable="true" tabindex="0" data-kode="{{ $m->kode }}" title="{{ $m->nama }}">{{ $m->kode }}</li>@endforeach</ul>
      <button type="button" class="p" data-act="simpan">Simpan</button>
      <button type="button" data-act="reset" title="Kembali ke urutan mapel di atas">Urutan standar</button>
      <button type="button" data-act="salin" title="Samakan semua semester dengan urutan semester ini">Terapkan ke semua semester</button>
      <span class="uc-st"></span>
    </div>
  @endforeach
</div>
@push('scripts')<script src="{{ asset('js/urutan-semester.js') }}"></script>@endpush
@endsection
