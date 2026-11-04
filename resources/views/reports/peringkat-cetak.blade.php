@extends('layouts.app')
@section('title', 'Cetak Peringkat')
@section('content')
@use('App\Services\Grading')
@php
  $dasarLabel = $dasar === 'akhir' ? 'Nilai akhir' : 'Rata-rata Semester ' . $dasar;
@endphp
<div class="rank-print-toolbar">
  <button class="p" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
  <span>{{ $tampil === 'kelas' ? 'Peringkat per kelas' : 'Peringkat global' }} · {{ $dasarLabel }} · Tahun {{ $tahun }}</span>
  <a href="{{ route('peringkat') }}">Kembali</a>
</div>
<div class="rank-print">
  @if ($tampil !== 'kelas')
    <header class="rank-print-heading">
      <h1>Daftar Peringkat Siswa</h1>
      <p>Tahun Pelajaran {{ $tahun }} · {{ $dasarLabel }} · Peringkat seluruh siswa</p>
    </header>
  @endif

  @forelse ($groups as $judul => $list)
    @php $scale = $tampil === 'kelas' ? min(1, 40 / max(1, $list->count())) : 1; @endphp
    <section class="rank-print-group" style="--rank-scale:{{ $scale }}">
      @if ($tampil === 'kelas')
        <header class="rank-print-heading">
          <h1>Daftar Peringkat Siswa</h1>
          <p>Tahun Pelajaran {{ $tahun }} · {{ $dasarLabel }} · Peringkat kelas</p>
        </header>
        <h2>Kelas {{ $judul }}</h2>
      @endif
      <div class="rank-print-table-wrap">
        <table class="t rank-print-table">
          <thead>
            <tr>
              <th class="n">Peringkat</th><th>NISN</th><th>Nama</th><th>Kelas</th>
              @foreach ($subjects as $subject)<th class="n" title="{{ $subject->nama }}">{{ $subject->kode }}</th>@endforeach
              <th class="n">Jumlah</th><th class="n">Rerata</th>
            </tr>
          </thead>
          <tbody>
          @foreach ($list as $student)
            @php
              $values = collect($student->fin)->filter(fn ($value) => $value !== null);
              $jumlah = $student->jumlah ?? $values->sum();
            @endphp
            <tr>
              <td class="n"><b>{{ $tampil === 'kelas' ? $student->r_kelas : $student->r_paralel }}</b></td>
              <td>{{ $student->nisn }}</td><td>{{ $student->nama }}</td><td>{{ $student->kelas }}</td>
              @foreach ($subjects as $subject)<td class="n">{{ $student->fin[$subject->id] === null ? '-' : Grading::fmt($student->fin[$subject->id]) }}</td>@endforeach
              <td class="n">{{ Grading::fmt((float) $jumlah) }}</td><td class="n"><b>{{ Grading::fmt((float) $student->avg) }}</b></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div class="rank-signature">
        <div>{{ $settings['ttd_skl'] }}</div>
        <div>Kepala {{ $settings['nama_sekolah'] ?: 'Sekolah' }}</div>
        <div class="rank-signature-space"></div>
        @if ($settings['kepsek'])<div><b class="signature-name">{{ $settings['kepsek'] }}</b></div>@endif
        @if ($settings['nip'])<div>NIP {{ $settings['nip'] }}</div>@endif
      </div>
    </section>
  @empty
    <p>Tidak ada siswa untuk dicetak.</p>
  @endforelse
</div>
<style>
  .rank-print-toolbar{position:sticky;top:0;z-index:5;display:flex;align-items:center;gap:12px;padding:10px 14px;background:#152a45;color:#fff}
  .rank-print-toolbar a{margin-left:auto;color:#fff}
  .rank-print{max-width:1500px;margin:18px auto;padding:18px;background:#fff;border:1px solid var(--line);border-radius:12px}
  .rank-print-heading{text-align:center;margin:0 0 18px}
  .rank-print-heading h1{font-size:20px;margin:0}
  .rank-print-heading p{margin:4px 0;color:var(--mut)}
  .rank-print-group{margin:0 0 18px}
  .rank-print-group h2{font-size:16px;margin:12px 0 6px}
  .rank-print-table-wrap{overflow:auto}
  .rank-print-table{font-size:11px}
  .rank-print-table th,.rank-print-table td{padding:5px 6px!important}
  .rank-signature{width:230px;margin:16px 3% 0 auto;text-align:center;line-height:1.35}
  .rank-signature-space{height:48px}
  @page{size:A4 landscape;margin:8mm}
  @media print{
    body{background:#fff}
    .rank-print-toolbar{display:none}
    .rank-print{max-width:none;margin:0;padding:0;border:0;border-radius:0}
    .rank-print-heading{margin-bottom:8px}
    .rank-print-group{break-inside:avoid;page-break-inside:avoid;margin:0;zoom:var(--rank-scale);width:calc(100% / var(--rank-scale))}
    .rank-print-group + .rank-print-group{break-before:page;page-break-before:always}
    .rank-print-table-wrap{overflow:visible}
    .rank-print-group h2{margin:0 0 4px;font-size:12pt}
    .rank-print-table{width:100%;font-size:7.5pt;line-height:1.05}
    .rank-print-table th,.rank-print-table td{padding:2px 3px!important}
    .rank-print-table thead{display:table-header-group}
    .rank-print-table th{position:static!important;background:#eef1f5!important}
    .rank-print-table tr{break-inside:avoid;page-break-inside:avoid}
    .rank-signature{margin-top:8px;font-size:9pt}
    .rank-signature-space{height:30px}
  }
</style>
@endsection
