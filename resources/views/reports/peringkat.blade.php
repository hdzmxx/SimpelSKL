@extends('layouts.app')
@section('content')
@use('App\Services\Grading')
<h2 class="t">Peringkat</h2>
<form class="bar" method="GET">
  <label>Tampilan
    <select name="tampil" onchange="this.form.submit()">
      <option value="paralel" @selected($tampil === 'paralel')>Paralel (semua kelas)</option>
      <option value="kelas" @selected($tampil === 'kelas')>Per kelas</option>
    </select></label>
  <label>Berdasarkan
    <select name="dasar" onchange="this.form.submit()">
      <option value="akhir" @selected($dasar === 'akhir')>Nilai akhir (kelulusan)</option>
      @for ($i = 1; $i <= 6; $i++)<option value="{{ $i }}" @selected($dasar === (string) $i)>Rata-rata semester {{ $i }}</option>@endfor
    </select></label>
  <label>Kelas
    <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach</select></label>
  <label>Tampilkan
    <select name="top" onchange="this.form.submit()">@foreach (['10' => '10 besar', '20' => '20 besar', '50' => '50 besar', 'all' => 'Semua'] as $v => $t)<option value="{{ $v }}" @selected((string) $top === (string) $v)>{{ $t }}</option>@endforeach</select></label>
  <span class="sp"></span>
  <a class="btn p" target="_blank" href="{{ route('peringkat', ['tampil' => 'paralel', 'dasar' => $dasar, 'top' => 'all', 'cetak' => 1]) }}">Cetak global 1–terakhir</a>
  <a class="btn p" target="_blank" href="{{ route('peringkat', array_filter(['tampil' => 'kelas', 'dasar' => $dasar, 'top' => 'all', 'kelas' => $kelas, 'cetak' => 1], fn ($value) => $value !== null && $value !== '')) }}">Cetak per kelas 1–terakhir</a>
  <a class="btn" href="{{ route('peringkat', request()->query() + ['format' => 'xlsx']) }}">Ekspor Excel</a>
  <a class="btn" href="{{ route('peringkat', request()->query() + ['format' => 'csv']) }}">CSV</a>
</form>
<p style="color:var(--mut);margin-top:-4px">
  Peringkat paralel dan peringkat kelas dihitung dari seluruh siswa tahun pelajaran ini; "Kelas" hanya menyaring tampilan. Nilai sama diurutkan menurut nomor urut.
  @if ($dasar !== 'akhir') Rata-rata semester dihitung dari mapel yang sudah terisi. @endif
</p>
@forelse ($groups as $judul => $list)
  @if ($tampil === 'kelas')<h3 class="kls">Kelas {{ $judul }}</h3>@endif
  <div class="tw" style="margin-bottom:12px"><table class="t">
    <thead><tr><th class="n">{{ $tampil === 'kelas' ? 'Peringkat kelas' : 'Peringkat paralel' }}</th><th class="n">{{ $tampil === 'kelas' ? 'Paralel' : 'Di kelas' }}</th><th>Nama</th><th>Kelas</th>@foreach ($subjects as $m)<th class="n" title="{{ $m->nama }}">{{ $m->kode }}</th>@endforeach<th class="n">Rata-rata</th></tr></thead>
    <tbody>
    @forelse ($list as $s)
      <tr><td class="n"><b>{{ $tampil === 'kelas' ? $s->r_kelas : $s->r_paralel }}</b></td><td class="n mut">{{ $tampil === 'kelas' ? $s->r_paralel : $s->r_kelas }}</td>
        <td>{{ $s->nama }}</td><td>{{ $s->kelas }}</td>
        @foreach ($subjects as $m)<td class="n">{{ $s->fin[$m->id] === null ? '' : Grading::fmt($s->fin[$m->id]) }}</td>@endforeach
        <td class="n"><b>{{ Grading::fmt($s->avg) }}</b></td></tr>
    @empty
      <tr><td colspan="{{ 5 + $subjects->count() }}">Tidak ada data.</td></tr>
    @endforelse
    </tbody></table></div>
@empty
  <p>Tidak ada siswa.</p>
@endforelse
@endsection
