@extends('layouts.app')
@section('title', 'Nilai ' . $subject->kode)
@section('content')
@use('App\Services\Grading')
<div class="score-page">
  <div class="score-heading">
    <div><div class="score-eyebrow">INPUT NILAI PER MAPEL</div><h2 class="t">{{ $subject->nama }} <span>({{ $subject->kode }})</span></h2>
      <p class="meta">Bobot Sem {{ $subject->bobot_semester + 0 }}% · PSAJ tertulis {{ $subject->bobot_tertulis + 0 }}%@if ($subject->ada_praktik) · praktik {{ $subject->bobot_praktik + 0 }}%@endif
        @if ($subject->ujian_dari_sem6) · tertulis menggunakan nilai Sem 6 @endif</p>
    </div>
    <div class="score-progress"><b>{{ $students->count() }}</b><span>siswa kelas {{ $kelas }}</span><div>{{ $filled }} dari {{ $totalFields }} nilai terisi</div></div>
  </div>

  <section class="score-subject-picker" aria-label="Navigasi mata pelajaran">
    <div class="score-subjects-heading"><b>Pilih mata pelajaran</b><span>{{ $subjects->count() }} mapel</span></div>
    <nav class="score-subjects" aria-label="Pilih mata pelajaran">
      @foreach ($subjects as $item)
        <a href="{{ route('nilai.grid', ['subject' => $item, 'kelas' => $kelas]) }}" title="{{ $item->nama }}" @class(['on' => $item->id === $subject->id]) @if ($item->id === $subject->id) aria-current="page" @endif>
          <b>{{ $item->kode }}</b><span>{{ $item->nama }}</span>
        </a>
      @endforeach
    </nav>
  </section>

  <div class="score-toolbar">
    <form method="GET" action="{{ route('nilai.grid', $subject) }}" class="score-class-form">
      <label for="score-class">Kelas</label>
      <select id="score-class" name="kelas" onchange="this.form.submit()">@foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach</select>
    </form>
    <label class="score-search"><span>Cari siswa</span><input id="score-search" type="search" placeholder="Ketik nama siswa…" autocomplete="off"></label>
    <span class="score-filter-count" id="score-filter-count" aria-live="polite"></span>
    <details class="score-tools">
      <summary>Impor / Ekspor</summary>
      <div class="score-tools-panel">
        <div class="score-tools-group"><b>Unduh nilai</b>
          <a class="btn p" href="{{ route('nilai.ekspor', ['subject' => $subject, 'kelas' => $kelas, 'blank' => 1]) }}">Format kosong · {{ $kelas }}</a>
          <a class="btn" href="{{ route('nilai.ekspor', ['subject' => $subject, 'blank' => 1]) }}">Format kosong · semua kelas</a>
          <a class="btn" href="{{ route('nilai.ekspor', ['subject' => $subject, 'kelas' => $kelas]) }}">Ekspor terisi</a>
          <a class="btn" href="{{ route('nilai.ekspor', ['subject' => $subject, 'kelas' => $kelas, 'format' => 'csv']) }}">CSV</a>
        </div>
        <form class="score-import" method="POST" action="{{ route('nilai.impor', $subject) }}" enctype="multipart/form-data">@csrf
          <b>Impor nilai {{ $subject->kode }}</b>
          <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
          <button>Baca &amp; pratinjau</button>
          <span>Isi format kosong dengan nilai, lalu unggah. Kolom: NISN, S1–S6{{ $subject->ujian_dari_sem6 ? '' : ', Tertulis' }}{{ $subject->ada_praktik ? ', Praktik' : '' }}.</span>
        </form>
      </div>
    </details>
  </div>

<form id="gridform" method="POST" action="{{ route('nilai.save', ['subject' => $subject, 'kelas' => $kelas]) }}">@csrf
  <div class="score-savebar">
    <span class="score-shortcut">Enter / ↓ pindah baris <span>·</span> Ctrl+V tempel dari Excel</span>
    <span id="dcount" class="score-dirty" aria-live="polite"></span>
    <button class="p">Simpan nilai</button>
  </div>
  <div class="tw score-table-wrap"><table class="t sg" id="ngrid">
    <thead><tr><th>No</th><th>Nama</th>@for ($i = 1; $i <= 6; $i++)<th>Sem {{ $i }}</th>@endfor<th>{{ $subject->ujian_dari_sem6 ? 'PSAJ tertulis · Sem 6' : 'PSAJ tertulis' }}</th>@if ($subject->ada_praktik)<th>PSAJ praktik</th>@endif<th>Nilai akhir</th></tr></thead>
    <tbody>
    @forelse ($students as $st)
      @php $sc = $scores->get($st->id); $d = Grading::detail($subject, $sc); @endphp
      <tr data-student="{{ mb_strtolower($st->nama) }}"><td>{{ $st->no_urut }}</td><td class="score-student-name">{{ $st->nama }}</td>
        @foreach (['sem1', 'sem2', 'sem3', 'sem4', 'sem5', 'sem6'] as $f)
          <td><input class="ni" type="number" step="any" min="0" max="100" name="scores[{{ $st->id }}][{{ $f }}]" value="{{ $sc?->$f }}" data-orig="{{ $sc?->$f }}"></td>
        @endforeach
        @if ($subject->ujian_dari_sem6)
          <td class="mut" title="Diambil dari Semester 6">= Sem 6</td>
        @else
          <td><input class="ni" type="number" step="any" min="0" max="100" name="scores[{{ $st->id }}][tertulis]" value="{{ $sc?->tertulis }}" data-orig="{{ $sc?->tertulis }}"></td>
        @endif
        @if ($subject->ada_praktik)
          <td><input class="ni" type="number" step="any" min="0" max="100" name="scores[{{ $st->id }}][praktik]" value="{{ $sc?->praktik }}" data-orig="{{ $sc?->praktik }}"></td>
        @endif
        <td><b>{{ Grading::fmt($d['akhir']) }}</b></td></tr>
    @empty
      <tr><td colspan="12" class="score-empty">Belum ada siswa di kelas ini.</td></tr>
    @endforelse
    <tr id="score-no-results" hidden><td colspan="12" class="score-empty">Tidak ada siswa yang cocok dengan pencarian.</td></tr>
    </tbody></table></div>
</form>
</div>
@push('scripts')<script src="{{ asset('js/grid.js') }}"></script>@endpush
@endsection
