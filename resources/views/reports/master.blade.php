@extends('layouts.app')
@section('content')
@use('App\Services\Grading')
<h2 class="t">Master Nilai</h2>
@if (!$subject)<p>Belum ada mapel.</p>@else
<form class="bar" method="GET">
  <select name="mapel" onchange="this.form.submit()">@foreach ($subjects as $x)<option value="{{ $x->id }}" @selected($x->id === $subject->id)>{{ $x->nama }}</option>@endforeach</select>
  <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach</select>
  <span style="color:var(--mut)">Bobot: semester {{ $subject->bobot_semester + 0 }}% · tertulis {{ $subject->bobot_tertulis + 0 }}%@if ($subject->ada_praktik) · praktik {{ $subject->bobot_praktik + 0 }}%@endif</span>
</form>
<div class="tw"><table class="t">
  <thead><tr><th>No</th><th>Nama</th><th>Kelas</th>@for ($i = 1; $i <= 6; $i++)<th class="n">S{{ $i }}</th>@endfor<th class="n">Rata-rata</th><th class="n">× {{ $subject->bobot_semester + 0 }}%</th><th class="n">Tertulis</th><th class="n">× {{ $subject->bobot_tertulis + 0 }}%</th>@if ($subject->ada_praktik)<th class="n">Praktik</th><th class="n">× {{ $subject->bobot_praktik + 0 }}%</th>@endif<th class="n">Nilai akhir</th></tr></thead>
  <tbody>
  @foreach ($students as $s)
    @php $d = $detail[$s->id]; @endphp
    <tr><td>{{ $s->no_urut }}</td><td>{{ $s->nama }}</td><td>{{ $s->kelas }}</td>
      @foreach ($d['sem'] as $v)<td class="n">{{ $v + 0 }}</td>@endforeach
      <td class="n">{{ Grading::fmt($d['rata']) }}</td><td class="n">{{ Grading::fmt($d['wS'] * $d['rata']) }}</td>
      <td class="n">{{ $d['tertulis'] + 0 }}</td><td class="n">{{ Grading::fmt($d['wT'] * $d['tertulis']) }}</td>
      @if ($subject->ada_praktik)<td class="n">{{ $d['praktik'] + 0 }}</td><td class="n">{{ Grading::fmt($d['wP'] * $d['praktik']) }}</td>@endif
      <td class="n"><b>{{ Grading::fmt($d['akhir']) }}</b></td></tr>
  @endforeach
  </tbody></table></div>
@endif
@endsection
