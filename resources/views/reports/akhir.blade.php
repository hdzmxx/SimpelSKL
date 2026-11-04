@extends('layouts.app')
@section('content')
@use('App\Services\Grading')
<h2 class="t">Nilai Akhir</h2>
<form class="bar" method="GET">
  <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach</select>
  <span class="sp"></span>
  <a class="btn" href="{{ route('akhir', ['kelas' => $kelas, 'format' => 'xlsx']) }}">Ekspor Excel</a><a class="btn" href="{{ route('akhir', ['kelas' => $kelas, 'format' => 'csv']) }}">CSV</a>
</form>
<div class="tw"><table class="t">
  <thead><tr><th>No</th><th>Nama</th><th>Kelas</th>@foreach ($subjects as $m)<th class="n" title="{{ $m->nama }}">{{ $m->kode }}</th>@endforeach<th class="n">Rata-rata</th><th class="n">Kosong</th></tr></thead>
  <tbody>
  @foreach ($students as $s)
    <tr><td>{{ $s->no_urut }}</td><td>{{ $s->nama }}</td><td>{{ $s->kelas }}</td>
      @foreach ($subjects as $m)<td class="n">{{ Grading::fmt($s->fin[$m->id]) }}</td>@endforeach
      <td class="n"><b>{{ Grading::fmt($s->avg) }}</b></td><td class="n">{{ $kosong[$s->id] ?: '' }}</td></tr>
  @endforeach
  </tbody></table></div>
@endsection
