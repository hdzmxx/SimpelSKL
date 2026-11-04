@extends('layouts.app')
@section('content')
<h2 class="t">Kehadiran <span style="color:var(--mut);font-weight:400;font-size:14px">· {{ \App\Support\Year::current()?->nama }}</span></h2>

<div class="tb" style="margin-bottom:14px">
  <form method="GET" style="display:contents">
    <select name="kelas" class="sm" aria-label="Filter kelas">
      <option value="">Semua kelas</option>
      @foreach ($kelasList as $k)<option value="{{ $k }}" @selected($k === $kelas)>{{ $k }}</option>@endforeach
    </select>
    <button>Filter</button>
  </form>
</div>

<div class="card" style="margin-bottom:14px">
  <h3 style="margin-top:0">Impor / Ekspor Kehadiran</h3>
  <p class="hint">Format berisi No, Nama Siswa, NISN, NIS, Kelas, serta Sakit, Izin, dan Alpa untuk Semester 5 dan 6. Impor dicocokkan berdasarkan NISN pada tahun pelajaran yang dipilih.</p>
  <div class="grid2">
    <div>
      <b>Unduh format</b>
      <form method="GET" action="{{ route('transfer.ekspor.kehadiran') }}" class="tb" style="margin-top:8px">
        <select name="kelas" class="sm" aria-label="Kelas untuk format kehadiran"><option value="">Semua kelas</option>
          @foreach ($kelasList as $k)<option value="{{ $k }}" @selected($k === $kelas)>{{ $k }}</option>@endforeach
        </select>
        <button class="p" name="blank" value="1">Template kosong</button>
        <button>Kehadiran terisi</button>
      </form>
    </div>
    <div>
      <b>Unggah untuk impor</b>
      <form method="POST" action="{{ route('transfer.impor.kehadiran') }}" enctype="multipart/form-data" class="tb" style="margin-top:8px">
        @csrf
        <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
        <button class="p">Baca &amp; pratinjau</button>
      </form>
      <p class="hint" style="margin:6px 0 0">Sel kosong dianggap 0. Data baru disimpan setelah pratinjau diterapkan.</p>
    </div>
  </div>
</div>

<div class="tw"><table class="t attendance-table">
  <thead>
    <tr>
      <th rowspan="2">No</th><th rowspan="2">NISN</th><th rowspan="2">Nama</th><th rowspan="2">Kelas</th>
      <th class="n" colspan="3">Semester 5</th><th class="n" colspan="3">Semester 6</th><th rowspan="2">Aksi</th>
    </tr>
    <tr><th class="n">Sakit</th><th class="n">Izin</th><th class="n">Alpa</th><th class="n">Sakit</th><th class="n">Izin</th><th class="n">Alpa</th></tr>
  </thead>
  <tbody>
  @forelse ($students as $student)
    @php $attendance = array_pad(array_values($student->absensi ?? []), 6, 0); @endphp
    <tr>
      <td>{{ $loop->iteration }}</td><td>{{ $student->nisn }}</td><td>{{ $student->nama }}</td><td>{{ $student->kelas }}</td>
      @foreach ($attendance as $count)<td class="n">{{ $count }}</td>@endforeach
      <td><a class="btn" href="{{ route('siswa.edit', $student) }}">Edit</a></td>
    </tr>
  @empty
    <tr><td colspan="11">Tidak ada data siswa untuk kelas ini.</td></tr>
  @endforelse
  </tbody>
</table></div>
@endsection
