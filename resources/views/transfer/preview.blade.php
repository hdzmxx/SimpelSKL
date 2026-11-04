@extends('layouts.app')
@section('content')
@if (($p['type'] ?? null) === 'attendance')
@php $rows = collect($p['result']['rows']); $unknown = collect($p['result']['unknown']); @endphp
<h2 class="t">Pratinjau impor kehadiran</h2>
<p>File: <b>{{ $p['name'] }}</b></p>
<div class="cards">
  <div class="stat"><b>{{ $rows->count() }}</b><span>Siswa cocok</span></div>
  <div class="stat"><b>{{ $unknown->count() }}</b><span>NISN tidak ditemukan</span></div>
</div>
@if ($unknown->isNotEmpty())
  <div class="warn">NISN berikut tidak ditemukan pada tahun pelajaran yang sedang dipilih dan akan dilewati:</div>
  <div class="tw"><table class="t">
    <thead><tr><th>NISN</th><th>Nama pada file</th></tr></thead>
    <tbody>@foreach ($unknown as $student)<tr><td>{{ $student['nisn'] }}</td><td>{{ $student['nama'] }}</td></tr>@endforeach</tbody>
  </table></div>
@endif
@if ($rows->isNotEmpty())
  <div class="tw"><table class="t">
    <thead><tr><th>NISN</th><th>Nama</th><th>Kelas</th><th>Kehadiran Sem 5 (S/I/A)</th><th>Kehadiran Sem 6 (S/I/A)</th></tr></thead>
    <tbody>@foreach ($rows as $student)
      <tr><td>{{ $student['nisn'] }}</td><td>{{ $student['nama'] }}</td><td>{{ $student['kelas'] }}</td>
        <td>{{ implode(' / ', array_slice($student['absensi'], 0, 3)) }}</td>
        <td>{{ implode(' / ', array_slice($student['absensi'], 3, 3)) }}</td></tr>
    @endforeach</tbody>
  </table></div>
@else
  <div class="flash err">Tidak ada siswa yang cocok untuk diperbarui.</div>
@endif
<div class="bar" style="margin-top:14px">
  @if ($rows->isNotEmpty())
    <form method="POST" action="{{ route('impor.terapkan') }}" onsubmit="return confirm('Simpan data kehadiran ini?')">@csrf<button class="p">Terapkan import</button></form>
  @endif
  <form method="POST" action="{{ route('impor.batal') }}">@csrf<button>Batal</button></form>
</div>
@else
@php $res = $p['result']; $tot = collect($res['subjects']); @endphp
<h2 class="t">Pratinjau impor</h2>
<p>File: <b>{{ $p['name'] }}</b></p>
@foreach ($res['warnings'] as $w)<div class="warn">{{ $w }}</div>@endforeach
@if ($tot->isEmpty())
  <div class="flash err">Tidak ada sheet/mapel yang dapat dibaca dari file ini.</div>
@else
  <div class="cards">
    <div class="stat"><b>{{ $tot->count() }}</b><span>Mapel terbaca</span></div>
    <div class="stat"><b>{{ $tot->sum('nilai') }}</b><span>Nilai terbaca</span></div>
    <div class="stat"><b>{{ $tot->sum('berubah') }}</b><span>Nilai berubah / baru</span></div>
    <div class="stat"><b>{{ $tot->sum(fn ($x) => count($x['unknown'])) }}</b><span>NISN tidak ditemukan</span></div>
  </div>
  <div class="tw"><table class="t">
    <thead><tr><th>Mapel</th><th class="n">Baris</th><th class="n">Siswa cocok</th><th class="n">Nilai terbaca</th><th class="n">Nilai berubah</th><th>NISN tidak ditemukan</th></tr></thead>
    <tbody>
    @foreach ($tot as $x)
      <tr><td>{{ $x['kode'] }} — {{ $x['nama'] }}</td><td class="n">{{ $x['baris'] }}</td><td class="n">{{ count($x['rows']) }}</td><td class="n">{{ $x['nilai'] }}</td><td class="n">{{ $x['berubah'] }}</td>
        <td style="white-space:normal">{{ count($x['unknown']) }}@if ($x['unknown']) <small style="color:var(--mut)">({{ implode(', ', array_slice($x['unknown'], 0, 6)) }}{{ count($x['unknown']) > 6 ? '…' : '' }})</small>@endif</td></tr>
    @endforeach
    </tbody></table></div>
@endif
<div class="bar" style="margin-top:14px">
  @if ($tot->isNotEmpty())
    <form method="POST" action="{{ route('impor.terapkan') }}" onsubmit="return confirm('Simpan nilai ini? Nilai yang ada akan ditimpa.')">@csrf<button class="p">Terapkan import</button></form>
  @endif
  <form method="POST" action="{{ route('impor.batal') }}">@csrf<button>Batal</button></form>
</div>
@endif
@endsection
