@extends('layouts.app')
@section('title', 'Ringkasan · SimpelSKL')
@section('content')
<div class="dashboard-page">
  <div class="dashboard-heading">
    <div>
      <div class="dashboard-eyebrow">DASHBOARD</div>
      <h2 class="t">Ringkasan Tahun Pelajaran {{ \App\Support\Year::current()?->nama }}</h2>
      <p class="hint">Pantau data siswa, kelengkapan nilai, dan progres persiapan kelulusan.</p>
    </div>
    <div class="dashboard-actions">
      <a class="btn p" href="{{ $stats->isNotEmpty() ? route('nilai.grid', $stats->first()['m']) : route('mapel.index') }}">Input nilai</a>
      <a class="btn" href="{{ route('transfer') }}">Impor / Ekspor</a>
      <a class="btn" href="{{ route('kelulusan') }}">Kelulusan</a>
    </div>
  </div>

  <section class="dashboard-cards" aria-label="Ringkasan utama">
    <div class="dashboard-card"><span>Total siswa</span><b>{{ number_format($jumlahSiswa) }}</b><small>{{ $gender['L'] }} laki-laki · {{ $gender['P'] }} perempuan</small></div>
    <div class="dashboard-card"><span>Kelas</span><b>{{ $perKelas->count() }}</b><small>Siswa tersebar di {{ $perKelas->count() }} kelas</small></div>
    <div class="dashboard-card"><span>Mapel aktif</span><b>{{ $stats->count() }}</b><small>Mata pelajaran untuk tahun ini</small></div>
    <div class="dashboard-card"><span>Rata-rata nilai sekolah</span><b>{{ \App\Services\Grading::fmt($averageSchool) }}</b><small>Rata-rata nilai akhir seluruh siswa</small></div>
    <div class="dashboard-card dashboard-card-progress"><span>Kelengkapan seluruh nilai</span><b>{{ $overallPct }}%</b><small>{{ number_format($totalFilled) }} dari {{ number_format($totalRequired) }} isian</small><div class="pg"><i style="width:{{ $overallPct }}%"></i></div></div>
  </section>

  <div class="dashboard-columns">
    <section class="dashboard-section dashboard-progress-section">
        <div class="dashboard-section-heading"><div><h3>Progres pengisian nilai</h3><p>Kelengkapan seluruh kolom nilai sesuai mapel aktif.</p></div><a href="{{ route('master') }}">Lihat master nilai</a></div>
        <div class="tw dashboard-table-wrap dashboard-progress-wrap"><table class="t dashboard-progress-table">
          <thead><tr><th>Mata pelajaran</th><th class="n">Rata-rata</th><th class="n">Terisi</th><th class="n">Kosong</th><th>Progres</th><th></th></tr></thead>
          <tbody>
          @forelse ($stats as $x)
            @php $pct = $x['req'] ? round(100 * $x['fill'] / $x['req']) : 0; @endphp
            <tr>
              <td><b>{{ $x['m']->nama }}</b></td>
              <td class="n">{{ \App\Services\Grading::fmt($x['average']) }}</td>
              <td class="n">{{ number_format($x['fill']) }}</td>
              <td class="n">{{ number_format($x['missing']) }}</td>
              <td><div class="dashboard-progress-cell"><div class="pg"><i style="width:{{ $pct }}%"></i></div><span>{{ $pct }}%</span></div></td>
              <td><a class="btn" href="{{ route('nilai.grid', $x['m']) }}">Buka</a></td>
            </tr>
          @empty
            <tr><td colspan="6" class="dashboard-empty">Belum ada mapel aktif. Atur mapel terlebih dahulu.</td></tr>
          @endforelse
          </tbody>
        </table></div>
        <div class="dashboard-section-footer"><span>Total isian yang belum lengkap</span><b>{{ number_format($totalRequired - $totalFilled) }}</b></div>
    </section>

    <section class="dashboard-section dashboard-rank-section">
        <div class="dashboard-section-heading"><div><h3>10 Peringkat Paralel</h3><p>Siswa dengan rata-rata nilai akhir tertinggi tahun pelajaran ini.</p></div><a href="{{ route('peringkat', ['tampil' => 'paralel', 'dasar' => 'akhir', 'top' => '10']) }}">Lihat semua</a></div>
        <div class="tw dashboard-table-wrap dashboard-rank-wrap"><table class="t dashboard-rank-table">
          <thead><tr><th class="n">Peringkat</th><th>Nama siswa</th><th>Kelas</th><th class="n">Rata-rata</th></tr></thead>
          <tbody>
          @isset($topRanked)
            @forelse ($topRanked as $student)
              <tr>
                <td class="n"><span class="dashboard-rank-number">{{ $student->r_paralel }}</span></td>
                <td><b>{{ $student->nama }}</b></td>
                <td>{{ $student->kelas }}</td>
                <td class="n"><b>{{ \App\Services\Grading::fmt($student->avg) }}</b></td>
              </tr>
            @empty
              <tr><td colspan="4" class="dashboard-empty">Belum ada data siswa untuk peringkat.</td></tr>
            @endforelse
          @else
            <tr><td colspan="4" class="dashboard-empty">Data peringkat belum tersedia. Muat ulang halaman.</td></tr>
          @endisset
          </tbody>
        </table></div>
    </section>
    <section class="dashboard-section dashboard-readiness-section">
        <div class="dashboard-section-heading"><div><h3>Persiapan kelulusan</h3><p>Hasil hitung berdasarkan aturan kelulusan saat ini.</p></div><a href="{{ route('kelulusan') }}">Buka</a></div>
        <div class="dashboard-status-list">
          <div><span><i class="dashboard-dot ok"></i>Memenuhi hitungan</span><b>{{ $kesiapan['ok'] }}</b></div>
          <div><span><i class="dashboard-dot pending"></i>Data belum lengkap</span><b>{{ $kesiapan['belum'] }}</b></div>
          <div><span><i class="dashboard-dot no"></i>Belum memenuhi</span><b>{{ $kesiapan['tidak'] }}</b></div>
        </div>
        <div class="dashboard-decision"><b>Penetapan resmi</b>
          <div><span>Ditetapkan lulus</span><strong>{{ $penetapan['lulus'] }}</strong></div>
          <div><span>Ditetapkan tidak lulus</span><strong>{{ $penetapan['tidak'] }}</strong></div>
          <div><span>Belum ditetapkan</span><strong>{{ $penetapan['belum'] }}</strong></div>
        </div>
        <p class="dashboard-note">Hasil hitung hanya bahan pemeriksaan. Keputusan resmi ditetapkan melalui menu Kelulusan.</p>
    </section>

    <section class="dashboard-section dashboard-classes-section">
        <div class="dashboard-section-heading"><div><h3>Data siswa per kelas</h3><p>Jumlah siswa, jenis kelamin, dan total alpa Sem 5–6.</p></div><a href="{{ route('siswa.index') }}">Data siswa</a></div>
        <div class="tw dashboard-table-wrap"><table class="t">
          <thead><tr><th>Kelas</th><th class="n">Siswa</th><th class="n">L</th><th class="n">P</th><th class="n">Alpa</th></tr></thead>
          <tbody>
          @forelse ($perKelas as $kelas => $data)
            <tr><td>{{ $kelas }}</td><td class="n">{{ $data['siswa'] }}</td><td class="n">{{ $data['laki'] }}</td><td class="n">{{ $data['perempuan'] }}</td><td class="n">{{ $data['alpa'] }}</td></tr>
          @empty
            <tr><td colspan="5" class="dashboard-empty">Belum ada data siswa.</td></tr>
          @endforelse
          </tbody>
          @if ($jumlahSiswa)
            <tfoot><tr><th>Total</th><th class="n">{{ $jumlahSiswa }}</th><th class="n">{{ $gender['L'] }}</th><th class="n">{{ $gender['P'] }}</th><th class="n">{{ $perKelas->sum('alpa') }}</th></tr></tfoot>
          @endif
        </table></div>
        @if ($gender['unknown'])
          <a class="dashboard-warning" href="{{ route('siswa.index') }}">{{ $gender['unknown'] }} siswa belum memiliki data jenis kelamin. Periksa Data Siswa.</a>
        @endif
    </section>
  </div>

  <section class="dashboard-workflow">
    <b>Alur kerja</b>
    <span><a href="{{ route('siswa.index') }}">Data Siswa</a></span><i>→</i>
    <span><a href="{{ $stats->isNotEmpty() ? route('nilai.grid', $stats->first()['m']) : route('mapel.index') }}">Input nilai per mapel</a></span><i>→</i>
    <span><a href="{{ route('semester') }}">Cocokkan dengan rapor</a></span><i>→</i>
    <span><a href="{{ route('kelulusan') }}">Periksa &amp; tetapkan kelulusan</a></span><i>→</i>
    <span><a href="{{ route('sk-kelulusan') }}">Cetak SK Kelulusan</a></span>
  </section>
</div>
@endsection
